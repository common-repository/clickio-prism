<?php

/**
 * Task factory
 */

namespace Clickio\Tasks;

use Clickio\Tasks\Interfaces\ISyncTask;
use Clickio\Utils\FileSystem;
use Exception;

/**
 * Task factory
 *
 * @package Tasks
 */
class TaskFactory
{
    /**
     * Tasks folder
     *
     * @var string
     */
    protected static $dir = __DIR__.'/Tasks';

    /**
     * Task found
     *
     * @var bool
     */
    protected static $discovered = false;

    /**
     * Tasks map e.g. "my_task" => "Namespace\Level2\Level3\MyTask"
     *
     * @var array
     */
    protected static $task_map = [];

    /**
     * Find tasks and build tasks map
     *
     * @return void
     */
    protected static function discover()
    {
        foreach (FileSystem::scandir(static::$dir) as $file) {
            $service_class = sprintf("%s\Tasks\%s", __NAMESPACE__, basename($file, '.php'));
            if (is_a($service_class, ISyncTask::class, true)) {
                $name = $service_class::getName();
                static::$task_map[$name] = $service_class;
            }
        }
        static::$discovered = true;
    }

    /**
     * Get task class by name
     *
     * @param string $name task name e.g. MyTask::NAME
     *
     * @return string
     */
    public static function getTaskClassByName(string $name): string
    {
        if (!static::$discovered) {
            static::discover();
        }

        if (array_key_exists($name, static::$task_map)) {
            return static::$task_map[$name];
        }

        throw new Exception("Task $name not found.");
    }
}
