<?php
/**
 * Logger
 */

namespace Clickio\Logger;

use Clickio\Logger\Interfaces\ILogger;
use Clickio\Options;
use Clickio\Utils\SafeAccess;

/**
 * Plugin logger
 *
 * @package Logger
 */
class Logger implements ILogger
{
    /**
     * Logger level
     *
     * @var int
     */
    protected $level = ILogger::LOGGER_OFF;

    /**
     * Logger level map
     *
     * @var array
     */
    protected $level_map = [
        ILogger::LOGGER_OFF => 'Off',
        ILogger::LOGGER_ON => 'On',
        ILogger::LOGGER_DEBUG => 'Debug'
    ];

    /**
     * Logger name
     *
     * @var string
     */
    protected $name = 'default';

    /**
     * Absolute path to log dir
     *
     * @var string
     */
    protected $log_path = ABSPATH.'clickio_logs';

    /**
     * Multitone pool
     *
     * @var array
     */
    protected static $pool = [];

    /**
     * Constructor
     *
     * @param string $name logger name
     * @param int $level log level
     */
    protected function __construct(string $name, int $level)
    {
        $this->name = $name;
        $this->level = $level;
    }

    /**
     * Get or create singletone instance
     *
     * @param string $name logger name
     *
     * @return ILogger
     */
    public static function getLogger(string $name): ILogger
    {
        if (!array_key_exists($name, static::$pool)) {
            $level = Options::get("log_level", ILogger::LOGGER_ON);
            $cl_debug = SafeAccess::fromArray($_REQUEST, 'cl_debug_mode', 'mixed', 0);
            if (Options::get('is_debug', false) || !empty($cl_debug)) {
                $level = ILogger::LOGGER_DEBUG;
            }
            static::$pool[$name] = new static($name, $level);
        }

        return static::$pool[$name];
    }

    /**
     * Setter.
     * Set logger level
     *
     * @param int $level log level
     *
     * @return void
     */
    public function setLevel(int $level)
    {
        $this->level = $level;
    }

    /**
     * Getter.
     * Get logger level
     *
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Get current log level
     *
     * @return array
     */
    public function getCurrentLevel(): array
    {
        $label = $this->level_map[$this->level];
        return [$this->level => $label];
    }

    /**
     * Getter.
     * Get lavels map
     *
     * @return array
     */
    public function getLevelMap(): array
    {
        return $this->level_map;
    }

    /**
     * Loggin info messages e.g. "Starting application" or "Settings changed"
     * A few words about application status
     *
     * @param string $msg log message
     *
     * @return void
     */
    public function info(string $msg)
    {
        if ($this->level >= ILogger::LOGGER_ON) {
            $msg = sprintf("Info: %s", $msg);
            $this->_writeLog($msg);
        }
    }

    /**
     * Loggin notices e.g. "WP Cron is disable" or "Integration through CMS and Mobile is disable"
     * A notices about current process
     *
     * @param string $msg log message
     *
     * @return void
     */
    public function notice(string $msg)
    {
        if ($this->level >= ILogger::LOGGER_ON) {
            $msg = sprintf("Notice: %s", $msg);
            $this->_writeLog($msg);
        }
    }

    /**
     * Loggin warnings e.g. "Missed parametr A for action B, using default C" or "Callback rejected as not safe"
     * When something goes not exactly as should
     *
     * @param string $msg log message
     *
     * @return void
     */
    public function warning(string $msg)
    {
        if ($this->level >= ILogger::LOGGER_ON) {
            $msg = sprintf("Warning: %s", $msg);
            $this->_writeLog($msg);
        }
    }

    /**
     * Loggin errors e.g. "Daemon responded with error - '...'" or "WP version is incompatible."
     * When further work is not possible.
     *
     * @param string $msg log message
     *
     * @return void
     */
    public function error(string $msg)
    {
        if ($this->level >= ILogger::LOGGER_ON) {
            $msg = sprintf("Error: %s", $msg);
            $this->_writeLog($msg);
        }
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
    public function debug(string $msg, array $debugInfo = [])
    {
        if ($this->level == ILogger::LOGGER_DEBUG) {
            $msg = sprintf(
                "Debug: %s\nDebug info:\n%s\n",
                $msg,
                json_encode($debugInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->_writeLog($msg);
        }
    }

    /**
     * Write message to file
     *
     * @param string $msg message to be writen
     *
     * @return void
     */
    private function _writeLog(string $msg)
    {
        $path = $this->getAbsPath();

        if (!is_dir($this->log_path)) {
            mkdir($this->log_path);
            chmod($this->log_path, 0777);
        }

        $dt = date("Y-m-d H:i:s P");

        $msg = sprintf("[%s] %s\n", $dt, $msg);

        @file_put_contents($path, $msg, FILE_APPEND);
    }

    /**
     * Getter.
     * Absolute path to logs
     *
     * @return string
     */
    public function getLogDir(): string
    {
        return $this->log_path;
    }

    /**
     * Get log file absolute path
     *
     * @param string $dt logfile date e.g. 2020_09_17
     *
     * @return string
     */
    public function getAbsPath(string $dt = ''): string
    {
        if (empty($dt)) {
            $dt = date("Y_m_d");
        }
        $filename = sprintf("%s_%s.%s", $this->name, $dt, "log");
        $path = implode(DIRECTORY_SEPARATOR, [$this->log_path, $filename]);
        return $path;
    }
}
