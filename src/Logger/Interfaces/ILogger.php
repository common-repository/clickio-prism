<?php
/**
 * Logger interface
 */

namespace Clickio\Logger\Interfaces;

/**
 * Main logger interface
 *
 * @package Logger\Interfaces
 */
interface ILogger
{
    /**
     * Logger disabled
     *
     * @var int
     */
    const LOGGER_OFF = 0;

    /**
     * Logger enabled
     *
     * @var int
     */
    const LOGGER_ON = 2;

    /**
     * Logger in debug mode
     *
     * @var int
     */
    const LOGGER_DEBUG = 128;

    /**
     * Set logger level
     *
     * @param int $level log level
     *
     * @return void
     */
    public function setLevel(int $level);

    /**
     * Loggin info messages e.g. "Starting application" or "Settings changed"
     * A few words about application status
     *
     * @param string $msg log message
     *
     * @return void
     */
    public function info(string $msg);

    /**
     * Loggin notices e.g. "WP Cron is disabled"
     * A notices about current process
     *
     * @param string $msg log message
     *
     * @return void
     */
    public function notice(string $msg);

    /**
     * Loggin warnings e.g. "Missed parametr A for action B, using default C" or "Callback rejected as not safe"
     * When something goes not exactly as should
     *
     * @param string $msg log message
     *
     * @return void
     */
    public function warning(string $msg);

    /**
     * Loggin errors e.g. "Daemon responded with error - '...'" or "WP version is incompatible."
     * When further work is not possible.
     *
     * @param string $msg log message
     *
     * @return void
     */
    public function error(string $msg);

    /**
     * Detailed messages about what actualy happining.
     * Use this for debug purpose only.
     *
     * @param string $msg log message
     * @param array $debugInfo debug information
     *
     * @return void
     */
    public function debug(string $msg, array $debugInfo = []);

    /**
     * Getter.
     * Absolute path to logs
     *
     * @return string
     */
    public function getLogDir(): string;

    /**
     * Get current log level
     *
     * @return array
     */
    public function getCurrentLevel(): array;

    /**
     * Getter.
     * Get lavels map
     *
     * @return array
     */
    public function getLevelMap(): array;

    /**
     * Getter.
     * Get logger level
     *
     * @return int
     */
    public function getLevel(): int;

    /**
     * Get log file absolute path
     *
     * @param string $dt logfile date e.g. 2020_09_17
     *
     * @return string
     */
    public function getAbsPath(string $dt = ''): string;
}
