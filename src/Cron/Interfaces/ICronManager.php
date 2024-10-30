<?php

/**
 * Cron manager interface
 */

namespace Clickio\Cron\Interfaces;

/**
 * Cron manager interface
 *
 * @package Cron\Interfaces
 */
interface ICronManager
{
    /**
     * Shedule hourly tasks
     *
     * @return void
     */
    public function runHourlyTasks();
}