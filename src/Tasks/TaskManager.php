<?php

/**
 * Delayed tasks manager
 */

namespace Clickio\Tasks;

use Clickio\Logger\LoggerAccess;
use Clickio\Tasks\Interfaces\ISyncTask;

/**
 * Delayed task manager
 *
 * @package Tasks
 */
class TaskManager
{
    /**
     * Logger functions
     */
    use LoggerAccess;

    /**
     * Execution percent
     *
     * @var int
     */
    protected static $exec_percent = 15;

    /**
     * Transient option key
     *
     * @var string
     */
    protected static $queue_name = 'clickio_task_queue';

    /**
     * Add new task
     *
     * @param string $task task name e.g. MyTask::NAME
     * @param array $arguments arguments for task constructor
     * @param int $interval number of seconds after which the event will be fired
     *
     * @return string
     */
    public static function scheduleSync(string $task, array $arguments, int $interval): string
    {
        $queue = static::getTaskQueue();
        $key = static::createTaskId($task, $arguments);
        $task_cls = TaskFactory::getTaskClassByName($task);
        $task_arr = [
            "task" => $task_cls,
            "args" => $arguments,
            "time_to_start" => time() + $interval
        ];
        $queue[$key] = $task_arr;

        set_transient(static::$queue_name, $queue);
        static::logDebug("New task", $task_arr);
        return $key;
    }

    /**
     * Cancel task
     *
     * @param string $id task id
     *
     * @return void
     */
    public static function cancel(string $id)
    {
        $queue = static::getTaskQueue();
        static::logDebug("Cancel task", ["id" => $id, "exist" => array_key_exists($id, $queue)]);
        if (array_key_exists($id, $queue)) {
            unset($queue[$id]);
        }

        set_transient(static::$queue_name, $queue);
    }

    /**
     * Check and execute tasks in queue
     *
     * @return void
     */
    public static function runTaskQueue()
    {
        $queue = static::getTaskQueue();
        $next_q = [];
        while (!empty($queue)) {
            list($task_id, $q_obj) = [array_key_last($queue), array_pop($queue)];
            $tts = $q_obj['time_to_start'];
            if ($tts > time()) {
                $next_q[$task_id] = $q_obj;
                continue ;
            }

            static::logDebug("Run task", ["id" => $task_id, "data" => $q_obj]);
            $task = $q_obj['task'];
            if (is_a($task, ISyncTask::class, true)) {
                $task_obj = new $task(...array_values($q_obj['args']));
                $task_obj->run();
            }
        }

        set_transient(static::$queue_name, $next_q);
    }

    /**
     * Get task by id
     *
     * @param string $id task id
     *
     * @return array
     */
    public static function getTask(string $id): array
    {
        $queue = static::getTaskQueue();
        if (array_key_exists($id, $queue)) {
            return $queue[$id];
        }

        return [];
    }

    /**
     * Get all tasks
     *
     * @return array
     */
    public static function getTaskQueue(): array
    {
        $queue = get_transient(static::$queue_name);
        if (empty($queue)) {
            $queue = [];
        }
        return $queue;
    }

    /**
     * Create task id
     *
     * @param string $task task name e.g. MyTask::NAME
     * @param array $args task arguments
     *
     * @return string
     */
    protected static function createTaskId(string $task, array $args): string
    {
        $hashable = sprintf("%s|%s", $task, json_encode($args));
        return md5($hashable);
    }

    /**
     * Estimate the probability of starting a task queue
     *
     * @return void
     */
    public static function maybeRunTask()
    {
        $percent = apply_filters('_clickio_task_exec_percent', static::$exec_percent);
        $rnd = random_int(0, 100);
        if ($rnd <= $percent) {
            static::runTaskQueue();
        }
    }

}
