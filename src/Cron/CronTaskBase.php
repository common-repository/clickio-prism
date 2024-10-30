<?php

/**
 * Base cron task
 */

namespace Clickio\Cron;

use Clickio\Logger as log;

/**
 * Abstract cron task
 *
 * @package Cron
 */
abstract class CronTaskBase
{
    /**
     * Constructor
     *
     * @param ILogger $log logger instance
     */
    public function __construct(log\Interfaces\ILogger $log)
    {
        $this->log = $log;
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
        $_msg = $this->_formatLogMessage($msg);
        $this->log->info($_msg);
    }

    /**
     * Logger method wrapper
     *
     * @param string $msg log message
     * @param array $debug debug info
     *
     * @return void
     */
    protected function debug(string $msg, array $debug = [])
    {
        $_msg = $this->_formatLogMessage($msg);
        $this->log->debug($_msg, $debug);
    }

    /**
     * Format message
     *
     * @param string $msg string to be formated
     *
     * @return string
     */
    private function _formatLogMessage(string $msg): string
    {
        return sprintf("%s: %s", static::class, $msg);
    }
}