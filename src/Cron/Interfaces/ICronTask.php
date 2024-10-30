<?php

/**
 * Cron task
 */

namespace Clickio\Cron\Interfaces;

/**
 * Cron task interface
 *
 * @package Cron\Interfaces
 */
interface ICronTask
{
    /**
     * Entry point
     *
     * @return void
     */
    public function run();
}