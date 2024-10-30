<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\CacheControl\Services\ClickIoCDN;
use Clickio\Logger\Logger;
use Clickio\Prism\Utils\StatWarmup;
use Clickio\RestApi as rest;
use Clickio\Utils\SafeAccess;

/**
 * Read logfiles
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/log_reader?date=YYYY-MM-DD&offset=50&type=all&page=1&pretty_print=0
 *
 * @package RestApi\Actions
 */
class LogReader extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Log date
     *
     * @var string
     */
    protected $date = "";

    /**
     * Amount of lines
     *
     * @var int
     */
    protected $offset = 50;

    /**
     * Log types
     *
     * Available: all, php, debug, transient, plugin
     *
     * @var array
     */
    protected $type = ['all'];

    /**
     * Logs page
     *
     * @var int
     */
    protected $page = 1;

    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $type = $this->request->get_param('type');
        if (!empty($type)) {
            $type = explode(',', $type);
            $this->type = array_map(
                function ($item) {
                    return trim($item);
                },
                $type
            );
        }

        $date = $this->request->get_param('date');
        if (!empty($date)) {
            $dt = date("Y_m_d", strtotime($date));
            $this->date = $dt;
        }

        $offset = $this->request->get_param('offset');
        if (!empty($offset) && $offset >= 1) {
            if ($offset > 5000) {
                $offset = 5000;
            }
            $this->offset = $offset;
        }

        $page = $this->request->get_param('page');
        if (!empty($page) && $page >= 1) {
            $this->page = $page;
        }

        $container = [];
        if (array_intersect($this->type, ['all', 'plugin'])) {
            $container['plugin'] = $this->getClickioLogs();
        }

        if (array_intersect($this->type, ['all', 'transient'])) {
            $container['transient'][ClickIoCDN::TRANSIENT_KEY] = get_transient(ClickIoCDN::TRANSIENT_KEY);
            $container['transient'][StatWarmup::TRANSIENT_KEY] = get_transient(StatWarmup::TRANSIENT_KEY);
        }

        if (array_intersect($this->type, ['all', 'debug'])) {
            $container['debug'] = $this->getDebugLogs();
        }

        if (array_intersect($this->type, ['all', 'php'])) {
            $container['php'] = $this->getPhpLogs();
        }

        $pretty_print = $this->request->get_param('pretty_print');
        if (!empty($pretty_print)) {
            $content = var_export($container, true);
            $content = htmlspecialchars($content, ENT_IGNORE);
            header("Content-type: text/html", true);
            echo "<pre>";
            echo $content;
            echo "</pre>";
            die();
        }

        return $container;
    }

    /**
     * Get plugin logs with offset
     *
     * @return array
     */
    protected function getClickioLogs(): string
    {
        $log_name = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $logger = Logger::getLogger($log_name);
        $log_file = $logger->getAbsPath($this->date);
        return $this->_getFileOffset($log_file);
    }

    /**
     * Get debug logs with offset
     *
     * @return array
     */
    protected function getDebugLogs(): string
    {
        $log_file = sprintf("%s/debug.log", WP_CONTENT_DIR);
        return $this->_getFileOffset($log_file);
    }

    /**
     * Get php logs with offset
     *
     * @return array
     */
    protected function getPhpLogs(): string
    {
        $log_file = ini_get('error_log');
        return $this->_getFileOffset($log_file);
    }

    /**
     * Get file content with offset
     *
     * @param string $log_file pathh to file
     *
     * @return string
     */
    private function _getFileOffset(string $log_file): string
    {
        if (!is_readable($log_file)) {
            return '';
        }
        $fp = fopen($log_file, 'r');

        $pos = -1;

        $lines = [];
        $currentLine = '';
        $current_line_num = 0;

        while (-1 !== fseek($fp, $pos, SEEK_END) && count($lines) < $this->offset) {
            $char = fgetc($fp);
            if (PHP_EOL == $char) {
                if ($current_line_num >= ($this->offset * ($this->page - 1))) {
                    $lines[] = $currentLine;
                } else {
                    $current_line_num++;
                }
                $currentLine = '';
            } else {
                $currentLine = $char . $currentLine;
            }
            $pos--;
        }
        if ($current_line_num >= ($this->offset * ($this->page - 1))) {
            $lines[] = $currentLine;
        }
        fclose($fp);
        return implode("\n", array_reverse($lines));
    }
}
