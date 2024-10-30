<?php

/**
 * Plugins utils
 */

namespace Clickio\Utils;

use Clickio\Integration\IntegrationManager;

/**
 * Plugins utils
 *
 * @package Utils
 */
class Plugins
{

    protected static $black_list = [
        'lazyload_videos_frontend',
        'redirect_canonical'
    ];
    /**
     * Get plugin info where callback is defined
     *
     * @param mixed $callback callback entity
     *
     * @return array
     */
    public static function getPluginByCallback($callback): array
    {
        if (!function_exists('get_plugins')) {
            include_once ABSPATH.'wp-admin/includes/plugin.php';
        }

        $method = static::_getMethodCallback($callback);

        if (!$method) {
            return [];
        }


        $file_path = $method->getFileName();

        if (strstr($file_path, WP_PLUGIN_DIR)) {
            foreach (get_plugins() as $plugin_file => $plugin_data) {
                if (!is_plugin_active($plugin_file)) {
                    continue ;
                }

                $plugin_dir = explode(DIRECTORY_SEPARATOR, plugin_basename($file_path))[0];
                if (strstr($plugin_file, $plugin_dir)) {
                    $plugin_data['PluginFile'] = $plugin_file;
                    return $plugin_data;
                }
            }
        }
        return [];
    }

    /**
     * Get reflected callback
     *
     * @param mixed $callback callback entity
     *
     * @return mixed
     */
    private static function _getMethodCallback($callback)
    {
        $callable = $callback;
        if (is_string($callable)) {
            $callable = explode("::", $callback);
        }


        try{
            if (count($callable) > 1) {
                $cls = SafeAccess::fromArray($callable, 0, 'mixed', null);
                $meth = SafeAccess::fromArray($callable, 1, 'mixed', null);
                if (!empty($cls) && !empty($meth)) {
                    $ref_cls = new \ReflectionClass($cls);
                    $method = $ref_cls->getMethod($meth);
                } else {
                    $method = null;
                }
            } else {
                $func = SafeAccess::fromArray($callable, 0, 'mixed', null);
                if (!empty($func)) {
                    $method = new \ReflectionFunction($func);
                } else {
                    $method = null;
                }
            }
        } catch (\Exception $e) {
            $method = null;
        }
        return $method;
    }

    /**
     * Check if callback is safe
     * This not gurantied that callback will not trigger some error
     *
     * @param mixed $callback callback entity
     *
     * @return bool
     */
    public static function pluginCallbackIsSafe($callback): bool
    {
        if ($callback instanceof \Closure) {
            return false;
        }

        $method = static::_getMethodCallback($callback);
        if (!$method || in_array($method->getName(), static::$black_list)) {
            return false;
        }

        $props = $method->getParameters();
        foreach ($props as $param) {
            if ($param->hasType()) {
                return false;
            }

            if (!$param->isOptional()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Safe check is plugin  active
     *
     * @param string $id plugin id
     *
     * @return bool
     */
    public static function pluginIsActive(string $id): bool
    {
        $plugins = static::getPlugins();
        return array_key_exists($id, $plugins) && \is_plugin_active($id);
    }

    /**
     * Get available plugins
     *
     * @return array
     */
    public static function getPlugins(): array
    {
        if (!function_exists('get_plugins') || !function_exists('is_plugin_active')) {
            include_once ABSPATH.'wp-admin/includes/plugin.php';
        }
        $plugins = \get_plugins();
        return $plugins;
    }

    /**
     * Get plugin by url to plugin resource
     * e.g. https://example.com/wp-content/plugins/my-plugin/static/my-script.js
     * In this case the MyPlugin returns
     *
     * @param string $path path to resource
     *
     * @return array
     */
    public static function getPluginByPath(string $path)
    {
        $plugins = static::getPlugins();
        $src_path = array_filter(explode('/', $path));
        $plugins_path = array_filter(explode('/', WP_PLUGIN_DIR));
        $plugin_dir = '';
        foreach ($plugins_path as $path_dir) {
            $idx = array_search($path_dir, $src_path);
            if ($idx === false) {
                continue ;
            }
            $plugin_dir = $src_path[($idx + 1)];
        }

        foreach ($plugins as $plugin_id => $plugin_data) {
            if (preg_match("/".$plugin_dir."/", $plugin_id)) {
                $data = $plugin_data;
                $data['id'] = $plugin_id;
                return $data;
            }
        }
        return [];
    }

    /**
     * Get callback name from callback array
     *
     * @param mixed $callback callback arra e.g. [$this, 'someMethod'] or 'myFunc'
     *
     * @return string
     */
    public static function getCallbackMethodName($callback): string
    {
        if (empty($callback)) {
            return "";
        }

        $ref_method = static::_getMethodCallback($callback);
        if (!$ref_method) {
            return "";
        }

        return $ref_method->name;
    }

    /**
     * Get plugin data by plugin ID
     *
     * @param string $plugin_id relative path to file e.g. my-plugin/main_file.php
     *
     * @return array
     */
    public static function getPluginById(string $plugin_id): array
    {
        $plugins = static::getPlugins();
        $plugin_data = [];
        if (array_key_exists($plugin_id, $plugins)) {
            $plugin_data = $plugins[$plugin_id];
        }
        return $plugin_data;
    }
}