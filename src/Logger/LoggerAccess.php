<?php

/**
 * Logger static interface
 */

namespace Clickio\Logger;

use Clickio\Logger\Interfaces\ILogger;
use Clickio\Utils\SafeAccess;

/**
 * Simple logging
 * This trait only supports the default logger
 *
 * @package Logger
 */
trait LoggerAccess
{
    /**
     * Logger container
     *
     * @var ILogger
     */
    protected static $log = null;

    /**
     * A few words about application status
     *
     * @param string $msg log message
     *
     * @return void
     */
    protected static function logInfo(string $msg)
    {
        $log = static::_getLogger();
        $msg = sprintf("%s: %s", static::class, $msg);
        $log->info($msg);
    }

    /**
     * A notices about current process
     *
     * @param string $msg log message
     *
     * @return void
     */
    public static function logNotice(string $msg)
    {
        $log = static::_getLogger();
        $msg = sprintf("%s: %s", static::class, $msg);
        $log->notice($msg);
    }

    /**
     * When something goes not exactly as it should
     *
     * @param string $msg log message
     *
     * @return void
     */
    public static function logWarning(string $msg)
    {
        $log = static::_getLogger();
        $msg = sprintf("%s: %s", static::class, $msg);
        $log->warning($msg);
    }

    /**
     * When further work is not possible.
     *
     * @param string $msg log message
     *
     * @return void
     */
    public static function logError(string $msg)
    {
        $log = static::_getLogger();
        $msg = sprintf("%s: %s", static::class, $msg);
        $log->error($msg);
    }

    /**
     * Detailed messages about what actually happening.
     * Use this for debug purpose only.
     *
     * @param string $msg log message
     * @param array $debugInfo debug information
     *
     * @return void
     */
    public static function logDebug(string $msg, array $debugInfo = [])
    {
        $log = static::_getLogger();
        $msg = sprintf("%s: %s", static::class, $msg);
        $log->debug($msg, $debugInfo);
    }

    /**
     * Get logger instance
     *
     * @return ILogger
     */
    private static function _getLogger(): ILogger
    {
        if (empty(static::$log)) {
            $log_name = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
            $logger = Logger::getLogger($log_name);
            static::$log = $logger;
        }
        return static::$log;
    }
}
