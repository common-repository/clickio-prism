<?php

/**
 * Cache meta info
 */

namespace Clickio\Prism\Cache\Engine;

use Clickio\Prism\Cache\Interfaces\ICacheEngine;

/**
 * Cache meta info
 *
 * @package Prism\Cache\Engine
 */
class MetaInfo extends Files
{
    /**
     * Path to cache dir
     *
     * @var string
     */
    protected $cache_dir = 'wp-content/cache/clickio/meta';

    /**
     * Constructor
     *
     * @param int $lifetime cache lifetime
     */
    public function __construct(int $lifetime = 0)
    {
        $this->blog_id = get_current_blog_id();
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

        @flock($fp, LOCK_EX);
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
     * @return array
     */
    public function get(string $key)
    {
        $data = $this->readCacheFile($key);
        if (!empty($data)) {
            $data_unserialized = @unserialize($data);
        }

        if (empty($data_unserialized) || !is_array($data_unserialized)) {
            $data_unserialized = [];
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

        $data = '';

        while (!@feof($fp)) {
            $data .= @fread($fp, 4096);
        }
        $data = substr($data, 14);

        @flock($fp, LOCK_UN);
        @fclose($fp);
        return $data;
    }
}
