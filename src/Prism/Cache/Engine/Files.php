<?php

/**
 * File cache
 */

namespace Clickio\Prism\Cache\Engine;

use Clickio\Prism\Cache\Interfaces\ICacheEngine;
use Clickio\Utils\FileSystem;

/**
 * Cache engine. File cache.
 *
 * @package Prism\Cache\Engine
 */
class Files extends AbstractCacheService implements ICacheEngine
{
    /**
     * Path to cache dir
     *
     * @var string
     */
    protected $cache_dir = 'wp-content/cache/clickio';

    /**
     * Cache lifetime
     *
     * @var int
     */
    protected $cache_lifetime = ICacheEngine::CACHE_FILE_EXPIRE_MAX;

    /**
     * Blog id if multisite
     *
     * @var int
     */
    protected $blog_id = 0;

    /**
     * Constructor
     *
     * @param int $lifetime cache lifetime
     */
    public function __construct(int $lifetime = 0)
    {
        if (!empty($lifetime) && $lifetime > 0 && $lifetime <= ICacheEngine::CACHE_FILE_EXPIRE_MAX) {
            $this->cache_lifetime = $lifetime;
        }

        $this->blog_id = get_current_blog_id();
    }

    /**
     * Adds data
     *
     * @param string $key cache key
     * @param mixed $value data to be cached
     * @param ?int $lifetime custom cache lifetime
     *
     * @return boolean
     */
    public function add(string $key, $value, $lifetime = null): bool
    {
        if (empty($this->get($key))) {
            return $this->set($key, $value, $lifetime);
        }

        return false;
    }

    /**
     * Sets data
     *
     * @param string $key cache key
     * @param mixed $value cache value
     * @param ?int $lifetime custom cache lifetime
     *
     * @return boolean
     */
    protected function set(string $key, $value, $lifetime = null): bool
    {
        $fp = $this->openFileOnWrite($key, 'wb');
        if (!$fp) {
            return false;
        }

        $expires_at = time() + (!empty($lifetime)? $lifetime : $this->cache_lifetime);
        $created_at = time();
        @flock($fp, LOCK_EX);
        @fputs($fp, pack('L', $expires_at));
        @fputs($fp, pack('L', $created_at));
        @fputs($fp, '<?php exit; ?>');
        @fputs($fp, @serialize($value));
        @flock($fp, LOCK_UN);
        @fclose($fp);

        return true;
    }

    /**
     * Get cached value
     *
     * @param string $key cache key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        $data = $this->readCacheFile($key);
        if (!empty($data)) {
            $data_unserialized = @unserialize($data);
        } else {
            $data_unserialized = $data;
        }

        if (empty($data_unserialized)) {
            $data_unserialized = '';
        }
        return $data_unserialized;
    }

    /**
     * Read cached data
     *
     * @param string $key cache $key
     *
     * @return string
     */
    protected function readCacheFile(string $key): string
    {
        $path = $this->cache_dir . DIRECTORY_SEPARATOR . $this->getPath($key);
        if (!@is_readable($path)) {
            return '';
        }

        $fp = @fopen($path, 'rb');
        if (!$fp) {
            return '';
        }

        @flock($fp, LOCK_SH);

        $expires_at = @fread($fp, 4);
        list(, $created_at) = @unpack('L', @fread($fp, 4));
        $data = '';

        if ($expires_at !== false) {
            list(, $expires_at) = @unpack('L', $expires_at);

            if (time() > $expires_at) {
                $this->purge($key);
            } else {
                while (!@feof($fp)) {
                    $data .= @fread($fp, 4096);
                }
                $data = substr($data, 14);
            }
        }

        @flock($fp, LOCK_UN);
        @fclose($fp);
        return $data;
    }

    /**
     * Purge data
     *
     * @param string $key cache key
     *
     * @return boolean
     */
    public function purge(string $key): bool
    {
        $path = ABSPATH . $this->cache_dir . DIRECTORY_SEPARATOR . $this->getPath($key);

        static::logDebug("Purge cache", ["purge" => $key, "path" => $path, "exists" => file_exists($path)]);
        if (!file_exists($path)) {
            return true;
        }

        return @unlink($path);
    }

    /**
     * Purge all cache
     *
     * @return bool
     */
    public function purgeAll():bool
    {
        $path = ABSPATH . $this->cache_dir;
        if (!is_dir($path)) {
            return true;
        }

        return FileSystem::rrmdir($path);
    }

    /**
     * Returns file path for key
     *
     * @param string  $key requested url
     *
     * @return string
     */
    protected function getPath($key): string
    {
        $hash = md5($key);
        $path = sprintf('%s/%s/%s/%s.php', $this->blog_id, substr($hash, -1, 1), substr($hash, -3, 2), $hash);
        return $path;
    }

    /**
     * Open cache file for writing
     * Create a cache directory if doesn't exist
     *
     * @param string $key cache key
     * @param string $mode fopen mode
     *
     * @return mixed
     */
    protected function openFileOnWrite(string $key, string $mode)
    {
        $sub_path = $this->getPath($key);
        $path = $this->cache_dir . DIRECTORY_SEPARATOR . $sub_path;

        $fp = FileSystem::openFileOnWrite($path, $mode);
        if (empty($fp)) {
            static::logError("Unable to create cache directory");
            return ;
        }

        return $fp;
    }

    /**
     * Get cache size
     *
     * @return int
     */
    public function getSize(): int
    {
        $dir = ABSPATH.$this->cache_dir;
        return FileSystem::calcFolderSize($dir);
    }
}
