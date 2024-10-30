<?php

/**
 * Cron manager
 */

namespace Clickio\Cron;

use Clickio\Cron as cron;
use Clickio\Logger as log;

/**
 * Default cron task manager
 *
 * @package Cron
 */
class CronTaskManager implements Interfaces\ICronManager
{

    protected static $lock_dir = ABSPATH.'wp-content';

    /**
     * Constructor
     *
     * @param ILogger $log logger instance
     */
    public function __construct(log\Interfaces\ILogger $log)
    {
        $this->log = $log;
        // add_action(cron\Events::HOURLY_EVENT, [$this, 'runHourlyTasks']);
    }

    /**
     * Shedule hourly tasks
     *
     * @return void
     */
    public function runHourlyTasks()
    {
        $post_err = CronTaskFactory::getTask(cron\Tasks\MarkPostsWithErrors::class, [$this->log]);
        $post_err->run();
    }

    /**
     * Logger method wrapper
     *
     * @param string $msg log message
     * @param array $params debug info
     *
     * @return void
     */
    protected function debug(string $msg, array $params)
    {
        $msg = sprintf("Cron Manager: %s\n", $msg);
        $this->log->debug($msg, $params);
    }

    /**
     * Logger method wrapper
     *
     * @param string $msg log message
     *
     * @return void
     */
    protected function info(string $msg)
    {
        $msg = sprintf("Cron Manager: %s\n", $msg);
        $this->log->info($msg);
    }

    /**
     * Prevent multiple times execution
     *
     * @param string $task event name
     *
     * @return bool
     */
    public static function lockTask(string $task): bool
    {
        if (static::isLocked($task)) {
            return false;
        }

        $file = static::getLockFilePath($task);

        if (!file_exists(dirname($file))) {
            try {
                @mkdir(dirname($file), 0777, true);
            } catch (\Exception $e) {
                return false;
            }
        }

        try {
            @touch($file);
        } catch(\Exception $e) {
            return false;
        }
        return static::isLocked($task);
    }

    /**
     * Release task
     *
     * @param string $task event name
     *
     * @return bool
     */
    public static function releaseTask(string $task): bool
    {
        if (!static::isLocked($task)) {
            return false;
        }

        $file = static::getLockFilePath($task);
        try {
            @unlink($file);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Check if task already runing
     *
     * @param string $task event name
     *
     * @return bool
     */
    public static function isLocked(string $task): bool
    {
        $file = static::getLockFilePath($task);
        return is_readable($file);
    }

    /**
     * Formath absolute path to lock file
     *
     * @param string $task event name
     *
     * @return string
     */
    public static function getLockFilePath(string $task): string
    {
        return sprintf('%s/.%s.clickio.lock', static::$lock_dir, $task);
    }
}
