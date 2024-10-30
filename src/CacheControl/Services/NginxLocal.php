<?php
/**
 * Manage local nginx cache
 */

namespace Clickio\CacheControl\Services;

use Clickio as org;
use Clickio\CacheControl as cc;
use Clickio\Utils\SafeAccess;

/**
 *  Nginx cache cleaner
 *
 * @package CacheControl\Services
 */
class NginxLocal extends cc\ServiceBase
{
    /**
     * Label
     *
     * @var string
     */
    protected $label = "Nginx local cache";

    /**
     * Description
     *
     * @var string
     */
    protected $desc = "Targeted purge local cache";

    /**
     * Interface method
     * For more information see method defenition
     *
     * @param array $urllist list of urls
     *
     * @return void
     */
    public function clear(array $urllist)
    {
        foreach ($urllist as $url) {
            $parsed_url = wp_parse_url($url);
            $_server_host = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'default');
            $domain = SafeAccess::fromArray($parsed_url, 'host', 'string', $_server_host);
            $path = SafeAccess::fromArray($parsed_url, 'path', 'string', '/');
            $query = SafeAccess::fromArray($parsed_url, 'query', 'string', '');

            $path = str_replace("\\", "", $path);

            $cache_paths = org\Options::get('local_cache', []);
            if (empty($cache_paths)) {
                static::logDebug("Empty cache locations", ["cache_paths" => $cache_paths]);
                return ;
            }

            $cache_list = preg_split("/\\r\\n|\\r|\\n/", $cache_paths);
            if (empty($cache_list)) {
                static::logDebug("Empty cache locations", ["cache_list" => $cache_list]);
                return;
            }

            foreach ($cache_list as $file_path) {
                if (empty($file_path)) {
                    static::logDebug("Empty path", ["path" => $file_path]);
                    continue;
                }

                foreach (['mobile', 'desktop'] as $device) {
                    if ($query == 'purge_all') {
                        $path = implode(DIRECTORY_SEPARATOR, [$file_path, $device]);
                        if (is_readable($path)) {
                            $this->rrmdir($path);
                            static::logDebug("Purge all", ["path" => $path]);
                        } else {
                            static::logDebug("Purge all. Unable to remove", ["path" => $path]);
                        }
                    } else {
                        if (substr($path, -1)=== '/') {
                            $without_tariling_slash = substr($path, 0, (strlen($path) - 1));
                            $key = implode(':', [$domain, 'GET', $without_tariling_slash , '0', '1']);
                            $this->removeCacheFile($key, $file_path, $device);

                            $key = implode(':', [$domain, 'GET', $without_tariling_slash , '0', '0']);
                            $this->removeCacheFile($key, $file_path, $device);

                            $key = implode(':', [$domain, 'GET', $without_tariling_slash , '1', '1']);
                            $this->removeCacheFile($key, $file_path, $device);

                            $key = implode(':', [$domain, 'GET', $without_tariling_slash."?get_id=1" , '1', '1']);
                            $this->removeCacheFile($key, $file_path, $device);
                        }

                        $post_link = get_permalink();
                        $post_uri = wp_parse_url($post_link);
                        if ($post_uri['path'] == $path) {
                            $key = implode(':', [$domain, 'GET', "/amp".$path , '0', '1']);
                            $this->removeCacheFile($key, $file_path, $device);

                            $key = implode(':', [$domain, 'GET', "/amp".$path , '0', '0']);
                            $this->removeCacheFile($key, $file_path, $device);

                            $key = implode(':', [$domain, 'GET', "/amp".$path , '1', '1']);
                            $this->removeCacheFile($key, $file_path, $device);

                            $key = implode(':', [$domain, 'GET', "/amp".$path.'?get_id=1', '1', '1']);
                            $this->removeCacheFile($key, $file_path, $device);

                            if (substr($path, -1)=== '/') {
                                $without_tariling_slash = substr($path, 0, (strlen($path) - 1));
                                $key = implode(':', [$domain, 'GET', "/amp".$without_tariling_slash , '0', '1']);
                                $this->removeCacheFile($key, $file_path, $device);

                                $key = implode(':', [$domain, 'GET', "/amp".$without_tariling_slash , '0', '0']);
                                $this->removeCacheFile($key, $file_path, $device);

                                $key = implode(':', [$domain, 'GET', "/amp".$without_tariling_slash , '1', '1']);
                                $this->removeCacheFile($key, $file_path, $device);

                                $key = implode(':', [$domain, 'GET', "/amp".$without_tariling_slash."?get_id=1" , '1', '1']);
                                $this->removeCacheFile($key, $file_path, $device);
                            }
                        }

                        if ($device == 'mobile') {
                            $key = implode(':', [$domain, 'GET', $path , '0', '1']);
                            $this->removeCacheFile($key, $file_path, $device);

                            $key = implode(':', [$domain, 'GET', $path , '0', '0']);
                            $this->removeCacheFile($key, $file_path, $device);
                        }
                        $key = implode(':', [$domain, 'GET', $path, '1', '1']);
                        $this->removeCacheFile($key, $file_path, $device);

                        $key = implode(':', [$domain, 'GET', $path.'?get_id=1', '1', '1']);
                        $this->removeCacheFile($key, $file_path, $device);

                    }
                }
            }
        }
    }

    /**
     * Remove cache file
     *
     * @param string $key string to be hashed
     * @param string $path path to file
     * @param string $device mobile or desktop cache
     *
     * @return void
     */
    protected function removeCacheFile(string $key, string $path, string $device)
    {
        $name = md5($key);
        $first_level = substr($name, -1, 1);
        $second_level = substr($name, -3, 1).substr($name, -2, 1);
        $file = implode(DIRECTORY_SEPARATOR, [$path, $device, $first_level, $second_level, $name]);
        if (is_readable($file)) {
            @unlink($file);
            static::logDebug('Targeted purge local cache', ["key" => $key, "name" => $name, "path" => $path, "file" => $file]);
        } else {
            static::logDebug('Unable to remove', ["key" => $key, "name" => $name, "path" => $path, "file" => $file]);
        }
    }
}