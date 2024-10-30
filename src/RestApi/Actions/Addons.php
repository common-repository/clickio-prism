<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Addons\AddonManager;
use Clickio\RestApi\BaseRestAction;
use Clickio\RestApi\Interfaces\IRestApi;
use WP_REST_Response;

/**
 * Confirm authority when requesting Clickio service
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/addon/
 *
 * @package RestApi\Actions
 */
class Addons extends BaseRestAction implements IRestApi
{
    /**
     * Handle http GET method
     * Get list of available addons
     *
     * @return mixed
     */
    public function get()
    {
        $manager = new AddonManager();
        return $manager->listAddons();
    }

    /**
     * Handle http POST method
     * Install new addon
     *
     * @return mixed
     */
    public function post()
    {
        $body = json_decode($this->request->get_body(), true);
        if (empty($body) || !array_key_exists("name", $body)) {
            return new WP_REST_Response(["error" => "Empty request"], 400);
        }

        $manager = new AddonManager();
        try {
            $manager->install($body['name']);
        } catch (\Exception $err) {
            return new WP_REST_Response(["error" => $err->getMessage()], 409);
        }
        return new WP_REST_Response(null, 202);
    }

    /**
     * Handle http DELETE method
     * Uninstall addon
     *
     * @return mixed
     */
    public function delete()
    {
        $body = json_decode($this->request->get_body(), true);
        if (empty($body) || !array_key_exists("name", $body)) {
            return new WP_REST_Response("Empty request", 400);
        }

        $manager = new AddonManager();
        $manager->uninstall($body['name']);
        return new WP_REST_Response(null, 202);
    }
}
