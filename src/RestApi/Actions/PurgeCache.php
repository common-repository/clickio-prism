<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\CacheControl\CacheManager;
use Clickio\Options;
use Clickio\RestApi as rest;
use Clickio\Tasks\TaskManager;
use Clickio\Utils\SafeAccess;

/**
 * Confirm authority when requesting Clickio service
 *
 * Example:
 *      POST http://domain.name/wp-json/clickio/protected/purge_cache/
 *          {
 *              all_pages: 0
 *              pages: [...]
 *          }
 *
 * @package RestApi\Actions
 */
class PurgeCache extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http post method
     *
     * @return mixed
     */
    public function post()
    {

        $params = $this->getPayload();
        if (empty($params)) {
            return new \WP_REST_Response(null, 400);
        }
        $all = SafeAccess::fromArray($params, 'all', 'integer', 0);
        $pages = SafeAccess::fromArray($params, 'pages', 'array', []);
        $canonical = SafeAccess::fromArray($params, 'canonical', 'integer', 0);
        $internal = SafeAccess::fromArray($params, 'internal', 'integer', 0);
        CacheManager::purge($all, $pages, $canonical, $internal);

        $int_scheme = Options::get('integration_scheme');
        $cache_status = Options::get('cache');
        if ($int_scheme == 'cms' && $cache_status && !empty($all)) {
            $after = SafeAccess::fromArray($params, 'after', 'integer', 0);
            if (empty($after) || $after <= 0) {
                $after = Options::get('deffered_purge');
                if (empty($after)) {
                    $after = 0;
                }
            }
            $args = [['Plugin'], [], $all, 0, 0];
            $task_id = TaskManager::scheduleSync('purge_cache', $args, ($after + 15));
            return new \WP_REST_Response(["task_id" => $task_id], 202);
        }
        return new \WP_REST_Response((object)[], 202);
    }
}
