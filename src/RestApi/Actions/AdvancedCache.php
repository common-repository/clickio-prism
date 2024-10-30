<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\ClickioPlugin;
use Clickio\Options;
use Clickio\Prism\Cache\CacheManager;
use Clickio\RestApi as rest;
use Clickio\Utils\SafeAccess;

/**
 * Advanced caching e.g. desktop, db, object
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/advanced-cache/?type=<<engine type>>
 *
 * @package RestApi\Actions
 */
class AdvancedCache extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http post method
     *
     * @return mixed
     */
    public function post()
    {
        $body = $this->parseBody();

        if (empty($body) || !array_key_exists('type', $body) || empty($body['type']) || !is_array($body['type'])) {
            return new \WP_REST_Response(null, 400);
        }

        $cache_opt = Options::get('cache');
        if (!$cache_opt) {
            return new \WP_REST_Response(['status' => false, "description" => "Cache disabled by options"], 400);
        }

        $type = $body['type'];
        $result = false;
        $adv_result = false;
        if (array_intersect($type, ['all', 'advanced'])) {
            $adv_result = CacheManager::setupAdvancedCache();
        }

        // for new conditions use
        // $result = $adv_result && $some_cache && $my_cache;
        $result = $adv_result;
        if ($result) {
            return new \WP_REST_Response(null, 202);
        }
        return new \WP_REST_Response(["status" => $result, "description" => "See the logs for more information"], 500);
    }

    /**
     * Handle http post method
     *
     * @return mixed
     */
    public function delete()
    {
        $body = $this->parseBody();

        if (empty($body) || !array_key_exists('type', $body) || empty($body['type'])) {
            return new \WP_REST_Response(null, 400);
        }

        $type = $body['type'];
        $result = false;
        $adv_result = false;
        if (array_intersect($type, ['all', 'advanced'])) {
            $adv_result = CacheManager::uninstallAdvancedCache();
        }

        $result = $adv_result;
        if ($result) {
            return new \WP_REST_Response(null, 202);
        }
        return new \WP_REST_Response(["status" => $result, "description" => "See the logs for more information"], 500);
    }

    /**
     * Parse http body
     *
     * @return mixed
     */
    protected function parseBody(): array
    {
        $body = [];
        $content_type_arr = $this->request->get_content_type();
        $content_type = SafeAccess::fromArray($content_type_arr, 'value', 'string', 'application/json');
        if ($content_type == 'multipart/form-data') {
            $body = $this->request->get_body_params();
        } else {
            $body = json_decode($this->request->get_body(), true);
        }

        if (empty($body)) {
            $body = [];
        }
        return $body;
    }
}
