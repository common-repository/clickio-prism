<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Options;
use Clickio\Prism\Cache\CacheFactory;
use Clickio\Prism\Cache\CacheRepo;
use Clickio\Prism\Cache\Engine\Internal;
use Clickio\Prism\Cache\Interfaces\ICacheRepo;
use Clickio\RestApi as rest;
use Clickio\Utils\CacheUtils;
use Clickio\Utils\SafeAccess;

/**
 * Get items by id
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/menus/(?P<id>\d+)/
 *
 * @package RestApi\Actions
 */
class GetMenu extends rest\BaseRestAction implements rest\Interfaces\IRestApi
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
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        $cache = $this->_getCacheRepo();
        $menu = [];
        if (!empty($url)) {
            $cached = json_decode($cache->get($url), true);
            if (!empty($cached)) {
                CacheUtils::setCacheStatusHeader(true);
                $menu = $cached;
            } else {
                CacheUtils::setCacheStatusHeader(false);
            }
        }

        if (empty($menu)) {
            $id = $this->request->get_param('id');
            $menu = $this->getMenus($id);
            $cache->set($url, wp_json_encode($menu));
        }

        return ["MenuItems"=> $menu];
    }

    /**
     * Get menu by id
     *
     * @param int $id menu id
     *
     * @return array
     */
    protected function getMenus(int $id): array
    {
        $args = array(
            'order'                  => 'ASC',
            'orderby'                => 'menu_order',
            'output'                 => "ARRAY_A",
            'output_key'             => 'menu_order',
            'update_post_term_cache' => false,
        );
        $items = [];
        $res=[];
        foreach (wp_get_nav_menu_items($id, $args) as $item) {
            if ($item->menu_item_parent == 0) {
                $res[$item->ID]=[
                    'WpId' => $item->ID,
                    'DisableSwipe' => 0,
                    'Name'  => $item->title,
                    'Order' => $item->menu_order,
                    'Url'   => $item->url,
                ];
                $items[$item->ID] = &$res[$item->ID];
            } else {
                $items[$item->menu_item_parent]['Children'][] = [
                    'WpId' => $item->ID,
                    'DisableSwipe' => 0,
                    'Name'  => $item->title,
                    'Order' => $item->menu_order,
                    'Url'   => $item->url,
                    'parent' => $item->menu_item_parent,
                ];
                $items[$item->ID] = &$items[$item->menu_item_parent]
                                        ['Children']
                                        [count($items[$item->menu_item_parent]['Children'])-1];
            }
        }
        return array_values($res);
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
}
