<?php

/**
 * Cache utils
 */

namespace Clickio\Utils;

use Clickio\Options;

/**
 * Cache utils
 *
 * @package Utils
 */
class CacheUtils
{
    /**
     * Ignore urls
     *
     * @var array
     */
    protected static $ignore_urls = [
        "wp-json",
        "wp-admin",
        "wp-login",
        "login",
        "register",
        "forgot-password",
        "\.js[\?]{0,1}",
        "\.cljs[\?]{0,1}",
        "\.cljson[\?]{0,1}",
        "\.css[\?]{0,1}",
        "\.xml[\?]{0,1}",
        "\.map[\?]{0,1}",
        "\.ico[\?]{0,1}",
        ".*cl_widget.*",
        "clab=[10]",
        "clickio_ignore_me"
    ];

    /**
     * Ignore url with params
     *
     * @var array
     */
    protected static $ignore_params = [
        "get_id",
        "lx_nocache",
        "lx_force_nocache",
        "lx_debug"
    ];

    /**
     * Caching plugins
     *
     * @var array
     */
    protected static $plugin_blacklist = [
        "litespeed-cache/litespeed-cache.php",
        // "w3-total-cache/w3-total-cache.php",
        "wp-fastest-cache/wpFastestCache.php",
        "wp-optimize/wp-optimize.php",
        "cache-enabler/cache-enabler.php",
        "hummingbird-performance/wp-hummingbird.php",
//        "wp-super-cache/wp-cache.php",
        "autoptimize/autoptimize.php",
        "sg-cachepress/sg-cachepress.php"
    ];

    /**
     * Minimum disk free space in bytes
     *
     * @var int
     */
    protected static $min_disk_size = 100 * 1024 * 1024; // 100 MB

    /**
     * Test if url is blacklitsed for caching
     *
     * @param string $url requested uri
     *
     * @return bool
     */
    public static function isIgnoredUrl(string $url): bool
    {
        /**
         * Filter urls to be ignored
         *
         * @param array $ignore_urls list of regexp
         *
         * @return array
         */
        $urls = apply_filters("clickio_cache_ignore_urls", static::$ignore_urls);
        if (empty($urls)) {
            return false;
        }

        foreach ($urls as $pattern) {
            if (preg_match("/".$pattern."/", $url)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test if there is caching plugin
     *
     * @return bool
     */
    public static function hasCachingPlugin(): bool
    {
        foreach (static::$plugin_blacklist as $id) {
            if (Plugins::pluginIsActive($id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the request contains ignore parameters
     *
     * @param array $params params list
     *
     * @return bool
     */
    public static function hasIgnoreParam(array $params): bool
    {
        $ignore = apply_filters("clickio_cache_ignore_params", static::$ignore_params);

        foreach ($ignore as $param) {
            if (SafeAccess::arrayKeyExists($param, $params)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cache status for current page
     *
     * @return bool
     */
    public static function getCacheStatus(): bool
    {
        $perms = static::getCacheStatusArray();
        foreach ($perms as $value) {
            if (!$value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Cache permissions for current page
     *
     * @return array
     */
    public static function getCacheStatusArray(): array
    {
        $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '/clickio_ignore_me');
        $ignore_url = static::isIgnoredUrl($url);
        $ignore_params = static::hasIgnoreParam($_REQUEST);
        $plugin = static::hasCachingPlugin();
        $cache_opt = Options::get('cache');
        $integration_opt = Options::get('integration_scheme');
        $request_method = SafeAccess::fromArray($_SERVER, 'REQUEST_METHOD', 'string', 'POST');

        return [
            "cache_enabled" => !empty($cache_opt),
            "integration_allowed" => $integration_opt == 'cms',
            "url_not_blacklisted" => !$ignore_url,
            "params_not_blacklisted" => !$ignore_params,
            "has_no_cache_plugin" => !$plugin,
            "http_method_allowed" => $request_method == 'GET',
            "is_anonym_user" => !is_user_logged_in(),
            "not_admin_page" => !is_admin()
        ];
    }

    /**
     * Add cache status headers
     *
     * @param bool $status cache status e.g. true - HIT, false - MISS
     *
     * @return void
     */
    public static function setCacheStatusHeader(bool $status)
    {
        if (headers_sent()) {
            return ;
        }

        $header = 'MISS';
        if ($status) {
            $header = 'HIT';
        }
        header('x-clickio-cache-status: ' . $header);
    }

    /**
     * Set chaching http headers
     * Cache-Control, Last-Modified
     *
     * @param int $created timestamp
     * @param int $expires timestamp
     *
     * @return void
     */
    public static function setCacheTtlHeaders(int $created, int $expires)
    {
        if (empty($expires) || $expires <= 0) {
            $expires = time() + 60;
        }

        $max_age = $expires - time();
        if (empty($max_age) || $max_age <= 0) {
            $max_age = 60;
        }

        if (empty($created) || $created <= 0) {
            $created = $expires - 60;
        }

        header("Cache-Control: max-age=$max_age", true);
        header('Last-Modified: '.date('D, j M Y H:i:s \G\M\T', $created), true);
    }

    /**
     * Check disk free space to store caches
     *
     * @return bool
     */
    public static function hasFreeSpace(): bool
    {
        $free = FileSystem::getDiskFreeSpace();
        return $free >= static::$min_disk_size;
    }
}
