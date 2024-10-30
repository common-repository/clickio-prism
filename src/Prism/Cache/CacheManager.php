<?php
/**
 * Cache manager
 */

namespace Clickio\Prism\Cache;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Clickio\Utils\CacheUtils;
use Clickio\Utils\FileSystem;
use Clickio\Utils\SafeAccess;

/**
 * Cache manager
 *
 * @package Prism\Cache
 */
class CacheManager
{
    use LoggerAccess;

    /**
     * Flag to stop caching
     *
     * @var bool
     */
    protected static $force_stop_caching = false;

    /**
     * Cache container
     *
     * @var string
     */
    private static $_cached_out = '';

    /**
     * Singletone container
     *
     * @var self
     */
    private static $_inst = null;

    /**
     * Customize cache lifetime
     *
     * @var ?int
     */
    protected static $custom_cache_lifetime = null;

    /**
     * Drop-in source
     *
     * @var string
     */
    protected static $adv_cache_src = CLICKIO_PLUGIN_DIR.'/src/Prism/Cache/WpContent/advanced-cache.php';

    /**
     * Drop-in destination
     *
     * @var string
     */
    protected static $adv_cache_dst = WP_CONTENT_DIR.'/advanced-cache.php';

    /**
     * Advanced cache fallback
     *
     * @var string
     */
    protected static $adv_cache_fallback = WP_CONTENT_DIR.'/fallback-advanced-cache.php';

    /**
     * Where to export the config
     *
     * @var string
     */
    protected static $cfg_file = WP_CONTENT_DIR.'/uploads/clickio/.config.php';

    /**
     * Advanced cache version
     *
     * @var string
     */
    const ADV_CACHE_VER = '1.2.1';

    /**
     * Cache callback
     *
     * @param string $buffer output buffer
     *
     * @return void
     */
    public static function cacheOutputBuffer($buffer)
    {
        if (static::$force_stop_caching) {
            return $buffer;
        }

        static::$_cached_out .= $buffer;
        return $buffer;
    }

    /**
     * Flush buffer into cache repo
     *
     * @return void
     */
    public static function saveBuffer()
    {
        $cache_status = CacheUtils::getCacheStatus();
        if (empty(static::$_cached_out) || !$cache_status) {
            return ;
        }

        $debug = [
            "buffer_not_empty" => !empty(static::$_cached_out),
            "buffer_length" => strlen(static::$_cached_out),
            "cache_opt" => $cache_status,
        ];
        static::logDebug("Save cache buffer", $debug);

        $repo = CacheRepo::getInstance();
        $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        $repo->set($url, static::$_cached_out, static::$custom_cache_lifetime);
    }

    /**
     * Start caching
     *
     * @return void
     */
    public function startCache()
    {
        static::$force_stop_caching = false;

        if (CacheUtils::getCacheStatus() && CacheUtils::hasFreeSpace()) {
            ob_start([static::class, 'cacheOutputBuffer']);
        }

        CacheUtils::setCacheStatusHeader(false);
    }

    /**
     * Stop caching
     *
     * @return void
     */
    public function stopCache()
    {
        static::$force_stop_caching = true;
        static::$_cached_out = '';
    }

    /**
     * Get output buffering status
     *
     * @return bool
     */
    public static function getBufferingStatus(): bool
    {
        return !static::$force_stop_caching;
    }

    /**
     * Factory method
     *
     * @param bool $force force to recreate manager
     *
     * @return self
     */
    public static function make(bool $force = false): self
    {
        if (!static::$_inst || $force) {
            $mngr = new static();
            static::$_inst = $mngr;
        }

        return static::$_inst;
    }

    /**
     * Customize page lifetime
     *
     * @param int $seconds number of seconds
     *
     * @return void
     */
    public static function setCustomLifetime(int $seconds)
    {
        static::$custom_cache_lifetime = intval($seconds, 10);
    }

    /**
     * Getter.
     * Get custom ttl
     *
     * @return ?int
     */
    public static function getCustomLifetime()
    {
        return static::$custom_cache_lifetime;
    }

    /**
     * Copy advanced-cache.php to wp-content folder
     *
     * @return bool
     */
    public static function setupAdvancedCache(): bool
    {
        $w3tc = IntegrationServiceFactory::getService('w3total');
        $w3tc::clearRemoveDropinStatus();
        $export_status = static::exportConfig();

        if (!$export_status) {
            static::logError("Unable to export config");
            return false;
        }

        // update advanced cache if already installed
        if (static::isAdvCacheInstaled()) {
            $cp_result = FileSystem::copyFile(static::$adv_cache_src, static::$adv_cache_dst);
            if (!$cp_result) {
                static::logError("Fail to copy advanced-cache.php file into wp-content");
                return false;
            }
            return true;
        }

        if (@file_exists(static::$adv_cache_dst)) {
            $rename_status = FileSystem::renameFile(static::$adv_cache_dst, static::$adv_cache_fallback);

            if (!$rename_status) {
                static::logError("Fail to rename existed advanced-cache.php file");
                return false;
            }
        }

        $cp_result = FileSystem::copyFile(static::$adv_cache_src, static::$adv_cache_dst);
        if (!$cp_result) {
            static::logError("Fail to copy advanced-cache.php file into wp-content");
            return false;
        }

        // if (!defined('WP_CACHE') || !WP_CACHE) {
        //     $cfg_updated = static::updateWpConfig(true);
        //     if (!$cfg_updated) {
        //         static::logError("Fail to update wp-config.php");
        //         return false;
        //     }
        // }
        return true;
    }

    /**
     * Export plugin config for drop-in usage
     *
     * @return bool
     */
    public static function exportConfig(): bool
    {
        $dir = dirname(static::$cfg_file);
        if (!@is_dir($dir)) {
            FileSystem::makeDir($dir);
        }

        if (!@is_writeable($dir)) {
            return false;
        }

        $opt = Options::getOptions();
        $opt['login_url'] = wp_parse_url(wp_login_url(), PHP_URL_PATH);
        $cfg = sprintf('<?php return;?>%s', wp_json_encode($opt));
        $status = file_put_contents(static::$cfg_file, $cfg);
        return !empty($status);
    }

    // public static function updateWpConfig(bool $status)
    // {

    //     $path = ABSPATH.'wp-config.php';
    //     if (!@is_readable($path)) {
    //         static::logError("File wp-config.php isn't readable or doesn't exists");
    //         return false;
    //     }

    //     $backup = ABSPATH.'backup-wp-config.php';
    //     if (!@is_readable($backup)) {
    //         $res = FileSystem::copyFile($path, $backup);
    //         if (!$res) {
    //             static::logError("Unable to make backup-wp-config.php");
    //             return false;
    //         }
    //     }

    //     $cfg = @file_get_contents($path);
    //     if (empty($cfg)) {
    //         static::logError("Unable to read wp-config.php");
    //         return false;
    //     }

    //     // var_dump(defined('WP_CACHE'), WP_CACHE);die();
    //     if ($status) {
    //         if (!defined('WP_CACHE') || !WP_CACHE) {
    //             $cfg = static::removeDirective($cfg);
    //             $cfg = static::addDirective($cfg);
    //         }
    //     } else {
    //         $cfg = static::removeDirective($cfg);
    //     }

    //     file_put_contents(ABSPATH.'/wp-config.php', $cfg);
    // }

    // protected static function addDirective(string $cfg): string
    // {
    //     $pattern_start = "//CLICKIO PRISM START";
    //     $pattern_end = "//CLICKIO PRISM END";
    //     $cfg .= "\n$pattern_start\ndefine('WP_CACHE', true);\n$pattern_end";
    //     return $cfg;
    // }

    // protected static function removeDirective(string $cfg): string
    // {
    //     $pattern_start = "\/\/CLICKIO PRISM START";
    //     $pattern_end = "\/\/CLICKIO PRISM END";
    //     $new_cfg = preg_replace("/\n$pattern_start.*$pattern_end/s", '', $cfg);
    //     $new_cfg = preg_replace("/(\\/\\/\\s*)?define\\s*\\(\\s*['\"]?WP_CACHE['\"]?\\s*,.*?\\)\\s*;+\\r?\\n?/is", '', $new_cfg);
    //     if (empty($new_cfg)) {
    //         return $cfg;
    //     }
    //     return $new_cfg;
    // }

    /**
     * Uninstall advanced-cache drop-in
     *
     * @return bool
     */
    public static function uninstallAdvancedCache(): bool
    {
        // static::updateWpConfig(false);
        if (!static::isAdvCacheInstaled()) {
            // static::logWarning("Constant CLICKIO_ADV_CACHE is not defined");
            return true;
        }


        if (@file_exists(static::$adv_cache_fallback)) {
            $rename_status = FileSystem::renameFile(static::$adv_cache_fallback, static::$adv_cache_dst);
            if (!$rename_status) {
                static::logError("Fail to replace original file with fallback");
                static::logInfo("Trying to delete advanced-cache.php");
                $rm_status = FileSystem::removeFile(static::$adv_cache_dst);
                if (!$rm_status) {
                    static::logError("Fail to remove advanced-cache.php");
                    return false;
                }
            }
        } else {
            $rm_status = FileSystem::removeFile(static::$adv_cache_dst);
            if (!$rm_status) {
                static::logError("Fallback not found. Fail to remove advanced-cache.php");
                return false;
            }
        }

        return true;
    }

    /**
     * Check that advanced-cache.php is installed
     *
     * @return bool
     */
    public static function isAdvCacheInstaled(): bool
    {
        if (defined('CLICKIO_ADV_CACHE')) {
            return true;
        }

        $file = WP_CONTENT_DIR.'/advanced-cache.php';
        if (!@is_file($file) || !@is_readable($file)) {
            return false;
        }

        $content = @file_get_contents($file);
        if (preg_match("/CLICKIO_ADV_CACHE/", $content)) {
            return true;
        }

        return false;
    }
}
