<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Addons\AddonManager;
use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Listings\FilterFacade;
use Clickio\Options;
use Clickio\Prism\Cache\CacheFactory;
use Clickio\Prism\Cache\CacheRepo;
use Clickio\Prism\Cache\Interfaces\ICacheRepo;
use Clickio\RestApi as rest;
use Clickio\Utils\CacheUtils;
use Clickio\Utils\SafeAccess;
use Clickio\Prism\Cache\Engine\Internal;
use Clickio\Utils\Permalink;

/**
 * List posts by params
 *
 * Example:
 *      POST http://domain.name/wp-json/clickio/lists/
 *      [{
 *          id: 1,
 *          order: DESC,
 *          orderby: some_field
 *      }]
 *
 * @package RestApi\Actions
 */
class GetLists extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{

    /**
     * Cache lifetime
     *
     * @var int
     */
    protected $cache_ttl = 3600;

    /**
     * Cache repository
     *
     * @var ICacheRepo
     */
    protected static $cache;

    /**
     * Store cache in different place
     *
     * @var string
     */
    protected $cache_folder = 'wp_json_lists';

    /**
     * Handle http post method
     *
     * @return mixed
     */
    public function post()
    {
        $body = $this->request->get_body();
        $params = json_decode($body, true);
        if (empty($params)) {
            $params = [[]];
        }

        $first_query = SafeAccess::fromArray($params, 0, 'array', false);
        if (!is_array($first_query)) {
            return [];
        }

        $items = [];
        $internal_cache_opt = Options::get('wp_cache_lists');
        if (!empty($internal_cache_opt)) {
            $cache_key = $this->getCacheKey($params);
            $cache = $this->_getCacheRepo();
            $_raw_items = json_decode($cache->get($cache_key), true);
            if (!empty($_raw_items)) {
                $items = $_raw_items;
                CacheUtils::setCacheStatusHeader(true);
            } else {
                CacheUtils::setCacheStatusHeader(false);
            }
        } else {
            CacheUtils::setCacheStatusHeader(false);
        }

        if (empty($items)) {
            $this->loadModules();
            $items = $this->_getLists($params);
            if (!empty($internal_cache_opt)) {
                $cache = $this->_getCacheRepo();
                $cache_key = $this->getCacheKey($params);
                $cache->set($cache_key, wp_json_encode($items));
            }
        }

        return $items;
    }

    /**
     * Get lists
     *
     * @param array $params listing params
     *
     * @return array
     */
    private function _getLists(array $params): array
    {
        $ordering_plugin = IntegrationServiceFactory::getService('taxonomy_order');
        $ordering_plugin::disable();

        ob_start();
        $filter = FilterFacade::create();
        $items = $filter->filter($params);
        ob_clean();
        ob_get_clean();
        @ob_end_clean();
        return $items;
    }

    /**
     * Get cach repository
     *
     * @return ICacheRepo
     */
    private function _getCacheRepo(): ICacheRepo
    {
        if (!static::$cache) {
            $engine = CacheFactory::make(Internal::class, [$this->cache_ttl]);
            static::$cache = new CacheRepo($engine);
        }
        return static::$cache;
    }

    /**
     * Get key for storage
     *
     * @param array $params listing params
     *
     * @return string
     */
    protected function getCacheKey(array $params): string
    {
        // first we need a full url to parse
        $request_uri = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '/');
        $url = home_url($request_uri);
        $parsed_url = parse_url($url);
        if (empty($parsed_url)) {
            return Permalink::getCurrentLocationUrl();
        }

        $scheme = SafeAccess::fromArray($parsed_url, 'scheme', 'string', 'https');
        $host = SafeAccess::fromArray($parsed_url, 'host', 'string', 'localhost');
        $path = SafeAccess::fromArray($parsed_url, 'path', 'string', '/');
        $raw_query = SafeAccess::fromArray($parsed_url, 'query', 'string', '');

        $q_params = [];
        foreach (explode('&', $raw_query) as $pair) {
            if (empty($pair)) {
                continue ;
            }

            list($key, $val) = explode('=', $pair);
            $q_params[$key] = empty($val)? '' : urldecode($val);
        }
        $q_params['data'] = md5(wp_json_encode($params));

        $query = http_build_query($q_params);

        $cache_key = sprintf("%s://%s%s?%s", $scheme, $host, $path, $query);
        return $cache_key;
    }

    /**
     * Load addons
     *
     * @return void
     */
    protected function loadModules()
    {
        $mngr = new AddonManager();
        $mngr->loadAddons();
    }
}
