<?php

/**
 * Synchronous task
 */

namespace Clickio\Tasks\Interfaces;

/**
 * Synchronous task
 *
 * @package Tasks\Interfaces
 */
interface ISyncTask
{
    /**
     * Task entrypoint
     *
     * @return void
     */
    public function run();
}
