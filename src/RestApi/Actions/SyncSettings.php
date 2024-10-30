<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio as org;
use Clickio\ClickioPlugin;
use Clickio\Options;
use Clickio\Prism\Cache\CacheManager;
use Clickio\RestApi as rest;

/**
 * Syncronize remote settings with local
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/sync_settings/
 *
 * @package RestApi\Actions
 */
class SyncSettings extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        org\Options::loadRemoteOptions();
        org\Options::save();

        $cache_opt = org\Options::get('cache');
        if (!CacheManager::isAdvCacheInstaled() && $cache_opt) {
            CacheManager::setupAdvancedCache();
        } else if (empty($cache_opt) && CacheManager::isAdvCacheInstaled()) {
            CacheManager::uninstallAdvancedCache();
        }

        return org\Options::getOptions();
    }

    /**
     * Handle http post method
     *
     * @return mixed
     */
    public function post()
    {
        $opt = $this->get();
        $key = Options::getApplicationKey();
        Options::setApplicationKey($key);
        return $opt;
    }
}
