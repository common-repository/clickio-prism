<?php

/**
 * Addons factory
 */

namespace Clickio\Addons;

use Clickio\Utils\FileSystem;
use Exception;

/**
 * Addon factory
 *
 * @package Addons
 */
final class AddonFactory
{
    /**
     * Addons map
     *
     * @var array
     */
    protected static $addon = [];

    /**
     * Addons name map
     *
     * @var array
     */
    protected static $addon_name_map = [];

    /**
     * Addons discovered
     *
     * @var bool
     */
    protected static $discovered = false;

    /**
     * Discover addons
     *
     * @return void
     */
    protected static function discover()
    {
        static::$discovered = true;
        $_upload_src = wp_upload_dir()['basedir'];
        $path = sprintf("%s/clickio", $_upload_src);
        if (!is_readable($path) || !is_dir($path)) {
            return ;
        }

        foreach (FileSystem::scandir($path) as $file) {
            $cls = sprintf("ClickioAddon\\%s", basename($file, ".php"));
            $file_path = sprintf("%s/%s", $path, $file);
            if (is_readable($file_path)) {
                if (!class_exists($cls)) {
                    include $file_path;
                }

                static::$addon[$cls] = [
                    "file" => $file_path,
                    "class" => $cls,
                    "name" => $cls::getName()
                ];
            }
        }
    }

    /**
     * Instantiate addon
     *
     * @param string $cls full class name
     * @param array $params addon extra params
     *
     * @return mixed
     */
    public static function createAddon(string $cls, array $params = [])
    {
        if (!static::$discovered) {
            static::discover();
        }

        if (array_key_exists($cls, static::$addon)) {
            if (!class_exists($cls)) {
                $file = static::$addon[$cls]['file'];
                include $file;
            }
            return new $cls(...$params);
        }
        throw new Exception("Unable to create addon $cls");
    }

    /**
     * Locate addon file path
     *
     * @param string $cls_or_name class or name
     *
     * @return string
     */
    public static function getAddonInfo(string $cls_or_name): array
    {
        if (!static::$discovered) {
            static::discover();
        }

        foreach (static::$addon as $a_info) {
            if (in_array($cls_or_name, [$a_info['class'], $a_info['name']])) {
                return $a_info;
            }
        }

        return [];
    }

    /**
     * Get all addons
     *
     * @return array
     */
    public static function getAddons(): array
    {
        if (!static::$discovered) {
            static::discover();
        }

        return static::$addon;
    }
}
