<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\ClickioPlugin;
use Clickio\RestApi as rest;

/**
 * Confirm authority when requesting Clickio service
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/authenticate/?key=<< application key >>
 *
 * @package RestApi\Actions
 */
class Authenticate extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $key = $this->request->get_param('key');
        if (empty($key)) {
            return new \WP_REST_Response(null, 400);
        }

        $plugin = ClickioPlugin::getInstance();
        $auth = $plugin->authenticate(trim($key));

        if (!$auth) {
            return new \WP_REST_Response(null, 401);
        }

        return new \WP_REST_Response(null, 202);
    }
}