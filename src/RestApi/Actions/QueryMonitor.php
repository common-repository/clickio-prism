<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;
use Clickio\Utils\Permalink;
use Clickio\Utils\QueryMonitor as UtilsQueryMonitor;
use Clickio\Utils\SafeAccess;
use WP_REST_Response;

/**
 * Query monitor data
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/protected/query_monitor
 *
 * @package RestApi\Actions
 */
class QueryMonitor extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $obj = UtilsQueryMonitor::getInstance();
        return $obj->getQueries();
    }

    /**
     * Handle http post method
     *
     * @return mixed
     */
    public function post()
    {
        $body = json_decode($this->request->get_body(), true);
        $page = SafeAccess::fromArray($body, 'page', 'string', '');
        if (empty($body) || empty($page)) {
            return new WP_REST_Response(["message" => "page is required"], 400);
        }

        $page = $this->addUrlParams($page);
        $resp = wp_remote_get($page, ['timeout' => 50]);

        $ret = [
            "page" => $page,
            "resp_code" => 0,
            "error" => "",
            "data" => []
        ];

        if (is_wp_error($resp)) {
            $ret['resp_code'] = $resp->get_error_code();
            $ret['error'] = $resp->get_error_message();
            return new WP_REST_Response($resp, 400);
        }

        $obj = UtilsQueryMonitor::getInstance();

        $ret['resp_code'] = $resp['response']['code'];
        $ret['data'] = $obj->getQueries();

        return new WP_REST_Response($ret, $ret['resp_code']);
    }

    protected function addUrlParams(string $page): string
    {
        $parsed = wp_parse_url($page);
        $path = SafeAccess::fromArray($parsed, 'path', 'string', '');
        $query = SafeAccess::fromArray($parsed, 'query', 'string', '');
        $url = home_url(sprintf('%s?%s', $path, $query));

        $anticache = Permalink::getAnticache();
        $args = [
            "anticache" => $anticache,
            "query_monitor" => 1,
            "clab" => 1
        ];
        return add_query_arg($args, $url);
    }
}
