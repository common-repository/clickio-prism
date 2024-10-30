<?php

/**
 * Cron task factory
 */

namespace Clickio\Cron;

/**
 * Cron task factory
 *
 * @package Cron
 */
class CronTaskFactory
{

    /**
     * Factory method
     * Create new cron task
     *
     * @param string $name task name
     * @param array $args task extra params
     *
     * @return ICronTask
     */
    public static function getTask(string $name, array $args = []): Interfaces\ICronTask
    {
        return new $name(...$args);
    }
}