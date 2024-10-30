<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Listings\Containers\FilterParamsContainer;
use Clickio\RestApi as rest;

/**
 * Debug listings
 *
 * Example:
 *      POST http://domain.name/wp-json/clickio/lists/debug/
 *      [{
 *          id: 1,
 *          order: DESC,
 *          orderby: some_field
 *      }]
 *
 * @package RestApi\Actions
 */
class DebugLists extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
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
            $params = [];
        }

        $debug = [];
        foreach ($params as $param) {
            $item = FilterParamsContainer::create($param);
            $debug[$item->id] = $item->toArray();
        }
        return $debug;
    }
}