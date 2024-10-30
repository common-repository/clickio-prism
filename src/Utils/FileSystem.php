<?php

/**
 * File system utils
 */

namespace Clickio\Utils;

use Clickio\Logger\LoggerAccess;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * File system utils
 *
 * @package Utils
 */
class FileSystem
{
    use LoggerAccess;

    /**
     * Recursively create the full path
     *
     * @param string $dir path to directory
     *
     * @return bool
     */
    public static function makeDir(string $dir): bool
    {
        if (is_dir($dir)) {
            return true;
        }

        mkdir($dir, 0777, true);
        return is_dir($dir);
    }

    /**
     * Open file write mode
     * Recursively create the full path if it doesn't exist
     *
     * @param string $path full file path
     * @param string $mode write mode
     *
     * @return mixed
     */
    public static function openFileOnWrite(string $path, string $mode)
    {
        $path = sprintf("%s/%s", ABSPATH, $path);

        $dir = dirname($path);

        if (static::makeDir($dir)) {
            $fp = fopen($path, $mode);
            return $fp;
        }

        return null;
    }

    /**
     * Recursive remove dir
     *
     * @param string $src path to be removed
     *
     * @return bool
     */
    public static function rrmdir($src): bool
    {
        $dir = opendir($src);
        if (!$dir) {
            return false;
        }

        while (false !== ($file = readdir($dir))) {
            if (in_array($file, ['..', '.'])) {
                continue;
            }

            $full = implode(DIRECTORY_SEPARATOR, [$src, $file]);
            if (is_dir($full)) {
                static::rrmdir($full);
            } else {
                unlink($full);
            }
        }
        closedir($dir);
        rmdir($src);
        return true;
    }

    /**
     * Create symlink
     * Works like "/bin/bash ln -s /path/to/source /path/to/dest"
     *
     * @param string $target symlink points to
     * @param string $name symlink location
     *
     * @return bool
     */
    public static function createSymlink(string $target, string $name): bool
    {
        if (!is_readable($target)) {
            return false;
        }

        if (@readlink($name)) {
            return true;
        }

        if (static::_functionDisabled("symlink")) {
            static::logWarning("The function \"symlink\" is disabled by php configuration.");
            return false;
        }
        $symlink = @symlink($target, $name);
        if (empty($symlink)) {
            $symlink = false;
        }
        return $symlink;
    }

    /**
     * Check if the function is disabled
     *
     * @param string $func_name function name
     *
     * @return bool
     */
    private static function _functionDisabled(string $func_name): bool
    {
        $disabled_functions = ini_get('disable_functions');
        $disabled_list = preg_split('/,\s*/', $disabled_functions);
        return in_array($func_name, $disabled_list);
    }

    /**
     * Remove file
     *
     * @param string $path file path
     *
     * @return bool
     */
    public static function removeFile(string $path): bool
    {
        if (!@is_readable($path)) {
            return false;
        }

        if (!@is_file($path)) {
            return false;
        }

        return @unlink($path);
    }

    /**
     * Rename file
     *
     * @param string $old source. Full path
     * @param string $new destination. Full path
     *
     * @return bool
     */
    public static function renameFile(string $old, string $new): bool
    {
        if (!@file_exists($old) || !@is_readable($old)) {
            return false;
        }

        if (@file_exists($new) && !@is_writable($new)) {
            return false;
        }

        return @rename($old, $new);
    }

    /**
     * Copy file
     *
     * @param string $src source. Full path
     * @param string $dst destination. Full path
     *
     * @return bool
     */
    public static function copyFile(string $src, string $dst): bool
    {
        if (!@file_exists($src) || !@is_readable($src)) {
            return false;
        }

        if (@file_exists($dst) && !@is_writable($dst)) {
            return false;
        }

        return @copy($src, $dst);
    }

    /**
     * Calc folder size in bytes
     *
     * @param string $dir absolute path
     *
     * @return int
     */
    public static function calcFolderSize(string $dir): int
    {

        if (!@is_dir($dir)) {
            return -1;
        }

        $size = 0;
        $flags = FilesystemIterator::KEY_AS_PATHNAME
                 | FilesystemIterator::CURRENT_AS_FILEINFO
                 | FilesystemIterator::SKIP_DOTS;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, $flags));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->isReadable()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Get disk free space in bytes
     *
     * @return int
     */
    public static function getDiskFreeSpace(): int
    {
        $disk_free = disk_free_space(ABSPATH);
        if (empty($disk_free)) {
            $disk_free = 0;
        }
        return $disk_free;
    }

    /**
     * Get disk free space percent
     *
     * @return int
     */
    public static function getDiskFreeSpacePercent(): int
    {
        $total = static::getDiskTotalSpace();
        $free = static::getDiskFreeSpace();
        if (empty($total) || empty($free)) {
            return 0;
        }
        return sprintf("%.2f", $free / $total * 100);
    }

    /**
     * Get disk total space in bytes
     *
     * @return int
     */
    public static function getDiskTotalSpace(): int
    {
        $disk_total = disk_total_space(ABSPATH);
        if (empty($disk_total)) {
            $disk_total = 0;
        }
        return $disk_total;
    }

    /**
     * Get used space percent
     *
     * @return int
     */
    public static function getDiskUsagePercent(): int
    {
        $total = static::getDiskTotalSpace();
        $free = static::getDiskFreeSpace();
        if (empty($total) || empty($free)) {
            return 0;
        }

        $used = $total - $free;
        $percent = sprintf("%.2f", $used / $total * 100);
        return $percent;
    }

    /**
     * Get used space in bytes
     *
     * @return int
     */
    public static function getDiskUsage(): int
    {
        $total = static::getDiskTotalSpace();
        $free = static::getDiskFreeSpace();
        if (empty($total) || empty($free)) {
            return 0;
        }

        $used = $total - $free;
        return $used;
    }

    /**
     * Scan dir for files and exclude paths starting with '.'(dot)
     *
     * @param string $path path to scan
     *
     * @return array
     */
    public static function scandir(string $path): array
    {
        $files = scandir($path);
        $files = array_filter(
            $files,
            function ($item) {
                return !empty($item) && !in_array($item, ['.', '..']) && substr($item, 0, 1) != '.';
            }
        );

        if (empty($files)) {
            $files = [];
        }
        return $files;
    }
}
