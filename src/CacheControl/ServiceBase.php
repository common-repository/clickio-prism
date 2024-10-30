<?php
/**
 * Cache service
 */

namespace Clickio\CacheControl;

use Clickio\Logger\LoggerAccess;

/**
 * Abstract cache service
 *
 * @package CacheControl
 */
abstract class ServiceBase implements Interfaces\ICacheService
{
    use LoggerAccess;

    /**
     * Interface method
     * For more information see method defenition
     *
     * @param array $urllist list of urls
     *
     * @return void
     */
    abstract public function clear(array $urllist);

    /**
     * Getter
     * Get human-readable label
     *
     * @return void
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Getter
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->desc;
    }

    /**
     * Recursive remove dir
     *
     * @param string $src path to be removed
     *
     * @return void
     */
    protected function rrmdir($src)
    {
        $dir = opendir($src);
        if (!$dir) {
            return ;
        }
        while (false !== ($file = readdir($dir))) {
            if (in_array($file, ['..', '.'])) {
                continue;
            }

            $full = implode(DIRECTORY_SEPARATOR, [$src, $file]);
            if (is_dir($full)) {
                $this->rrmdir($full);
            } else {
                unlink($full);
            }
        }
        closedir($dir);
        rmdir($src);
    }
}