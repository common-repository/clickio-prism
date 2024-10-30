<?php
/**
 * Rest api manager
 */

namespace Clickio\RestApi;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Options;

/**
 * Rest api manager
 *
 * @package RestApi
 */
class RestApiManager implements Interfaces\IRestApiManager
{

    /**
     * Register rest api routes
     *
     * @return void
     */
    public function registerRestRoutes()
    {
        require_once CLICKIO_PLUGIN_DIR."/src/compatibility.php";

        $wpquiz = IntegrationServiceFactory::getService("wpquiz");
        $wpquiz::fixPlayDataIssue();

        $path = implode(DIRECTORY_SEPARATOR, [CLICKIO_PLUGIN_DIR, 'src', 'RestApi', 'routes.json']);
        $routes_json = file_get_contents($path);
        $routes_list = json_decode($routes_json);
        $is_debug = Options::get('is_debug');
        foreach ($routes_list as $cfg) {
            if (empty($is_debug) && $cfg->debug_only) {
                continue;
            }
            $callback = sprintf('%s\Actions\%s', __NAMESPACE__, $cfg->callback);
            $perm_callback = $this->_getPermissionCallback($cfg);
            $opt = [
                "methods" => $cfg->methods,
                "callback" => [$callback, 'dispatch'],
                "permission_callback" => $perm_callback,
            ];
            register_rest_route($cfg->namespace, $cfg->route, $opt);
        }
    }

    /**
     * Get permisson callback
     *
     * @param stdClass $route route config
     *
     * @return array|string
     */
    private function _getPermissionCallback(\stdClass $route)
    {
        if ($route->namespace == 'clickio/protected') {
            $callback = sprintf('%s\Actions\%s', __NAMESPACE__, $route->callback);
            return [$callback, "hasPermissions"];
        }
        return '__return_true';
    }
}
