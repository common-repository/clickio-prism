<?php

/**
 * Cache engine repository
 */

namespace Clickio\Prism\Cache;

use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Clickio\Prism\Cache\Engine\Files;
use Clickio\Prism\Cache\Engine\MetaInfo;
use Clickio\Prism\Cache\Interfaces\ICacheEngine;
use Clickio\Prism\Cache\Interfaces\ICacheRepo;
use Clickio\Utils\DeviceType;
use Clickio\Utils\SafeAccess;
use DateTime;
use DateTimeZone;

/**
 * Cache engine repository
 *
 * @package Prism\Cache
 */
final class CacheRepo implements ICacheRepo
{
    /**
     * Logger trait
     */
    use LoggerAccess;

    /**
     * Cache engine instance
     *
     * @var ICacheEngine
     */
    protected $engine = null;

    /**
     * Cache metainfo instance
     *
     * @var ICacheEngine
     */
    protected $meta = null;

    /**
     * Single tone container
     *
     * @var self
     */
    protected static $inst = null;

    /**
     * Constructor
     *
     * @param ICacheEngine $cache cache engine
     */
    public function __construct(ICacheEngine $cache)
    {
        $this->engine = $cache;
        $this->meta = CacheFactory::make(MetaInfo::class);
    }

    /**
     * Singletone constructor
     *
     * @return self
     */
    public static function getInstance()
    {
        if (!static::$inst) {
            $lx_nocache = SafeAccess::fromArray($_GET, 'lx_nocache', 'string', '0');
            if (!empty(Options::get("cache")) && empty($lx_nocache)) {
                $lifetime = Options::get('cache_lifetime');
                $cache = CacheFactory::make(Files::class, [$lifetime]);
            } else {
                $cache = CacheFactory::make(Dummy::class);
            }
            static::$inst = new static($cache);
        }
        return static::$inst;
    }

    /**
     * Get value from cache
     *
     * @param string $url cache key
     *
     * @return string
     */
    public function get(string $url): string
    {
        $lx_nocache = SafeAccess::fromArray($_GET, 'lx_nocache', 'string', '0');
        if (!empty($lx_nocache)) {
            return '';
        }

        $meta = $this->getCacheMeta($url);
        if (!empty($meta)) {
            return '';
        }

        $key = $this->getCacheKey($url);
        return $this->engine->get($key);
    }

    /**
     * Put data into storage
     *
     * @param string $url cache key
     * @param mixed $value data to be cached
     * @param ?int $lifetime custom cache lifetime
     *
     * @return bool
     */
    public function set(string $url, $value, $lifetime = null): bool
    {
        $lx_nocache = SafeAccess::fromArray($_GET, 'lx_nocache', 'string', '0');
        if (!empty($lx_nocache)) {
            return false;
        }

        $key = $this->getCacheKey($url);
        static::logDebug("Cached", ["cache_key" => $key, 'url' => $url]);
        return $this->engine->add($key, $value, $lifetime);
    }

    /**
     * Put data into storage
     *
     * @param string $url cache key
     * @param array $values meta data
     *
     * @return bool
     */
    public function updateMeta(string $url, array $values)
    {
        $key = $this->getCacheKey($url);
        static::logDebug("Update cache meta", ["cache_key" => $key, 'url' => $url, 'data' => $values]);
        $this->meta->purge($key);
        return $this->meta->add($key, $values);
    }

    /**
     * Get cache meta info
     *
     * @param string $url page url
     *
     * @return string
     */
    public function getCacheMeta(string $url): array
    {
        $key = $this->getCacheKey($url);
        $meta_info = $this->meta->get($key);
        if (empty($meta_info) || !is_array($meta_info)) {
            $meta_info = [];
        }
        return $meta_info;
    }

    /**
     * Remove cache meta info
     *
     * @param string $url page url
     *
     * @return void
     */
    public function purgeCacheMeta(string $url)
    {
        $key = $this->getCacheKey($url);
        $this->meta->purge($key);
    }

    /**
     * Purge cache for a key
     *
     * @param string $url cache key
     * @param bool $raw_url use $url or add key to $url
     * @param bool $force force purge cache
     *
     * @return bool
     */
    public function purge(string $url, bool $raw_url = false, bool $force = false): bool
    {
        if (!$raw_url) {
            $key = $this->getCacheKey($url);
        } else {
            $key = $url;
        }

        if ($this->isPrismCache($key) && empty($force)) {
            $dt = new DateTime('now', new DateTimeZone('utc'));
            $this->meta->purge($key);
            return $this->meta->add($key, ["key" => $key, "last-modified" => $dt->format('D, d M Y H:i:s T')]);
        } else {
            $this->meta->purge($key);
            return $this->engine->purge($key);
        }
    }

    /**
     * Purge all
     *
     * @return bool
     */
    public function purgeAll(): bool
    {
        static::logDebug("Purge all", ["engine" => get_class($this->engine)]);
        return $this->engine->purgeAll();
    }

    /**
     * Split prism, mobile, desktop caches
     *
     * @param string $url page url
     *
     * @return string
     */
    protected function getCacheKey(string $url): string
    {
        $isMobile = DeviceType::isMobile();
        $isPrism = false;

        $mobile_opt = Options::get('mobile');
        $cl_debug = SafeAccess::fromArray($_REQUEST, 'cl_debug', 'string', '');

        if ((!empty($mobile_opt) && $isMobile) || !empty($cl_debug)) {
            $isPrism = true;
        }
        $key = sprintf("%s:%d:%d", $url, $isPrism, $isMobile);
        return $key;
    }

    /**
     * Check that key is for prism cache
     *
     * @param string $key cache key
     *
     * @return bool
     */
    protected function isPrismCache(string $key): bool
    {
        return (bool)preg_match("/\:1\:[01]$/", $key);
    }

    /**
     * Get cache size
     *
     * @return int
     */
    public function getCacheSize(): int
    {
        $size = $this->engine->getSize();
        if ($size < 0) {
            $size = 0;
        }
        return $size;
    }
}
