<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Prism\Cache\CacheRepo;
use Clickio\RestApi as rest;
use Clickio\Utils\FileSystem;

/**
 * Wp status
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/protected/status
 *
 * @package RestApi\Actions
 */
class Status extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        global $wp_version;
        $status = [
            "plugin" => CLICKIO_PRISM_VERSION,
            "php" => PHP_VERSION,
            "wp" => $wp_version,
            "open_basedir" => ini_get('open_basedir'),
            "disabled" => array_filter(explode(',', ini_get('disable_functions'))),
            "sapi" => PHP_SAPI,
            "disk_cache" => [
                "disk_free_bytes" => FileSystem::getDiskFreeSpace(),
                "disk_free_percent" => FileSystem::getDiskFreeSpacePercent(),
                "disk_usage_bytes" => FileSystem::getDiskUsage(),
                "disk_usage_percent" => FileSystem::getDiskUsagePercent(),
                "disk_total_bytes" => FileSystem::getDiskTotalSpace(),
                "cache_size_bytes" => (CacheRepo::getInstance())->getCacheSize()
            ],
            "autoload_options_size" => $this->getOptionsSize()
        ];
        return $status;
    }

    /**
     * Get the size of automatically loaded options
     *
     * @return int
     */
    protected function getOptionsSize(): int
    {
        global $wpdb;
        $q = "SELECT sum(
                ifnull(char_length(option_name), 0) +
                ifnull(char_length(option_value), 0)
            ) as bytes
            FROM wp_options
            WHERE autoload = 'yes'";
        $res = $wpdb->get_row($q, \ARRAY_A);
        if (empty($res)) {
            $res = ['bytes' => 0];
        }
        return $res['bytes'];
    }
}
