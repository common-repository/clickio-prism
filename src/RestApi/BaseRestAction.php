<?php
/**
 * Abstract rest api action
 */

namespace Clickio\RestApi;

use Clickio\Authorization\TokenAuthorizationManager;
use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Clickio\Utils\SafeAccess;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Base class
 *
 * @package RestApi
 */
abstract class BaseRestAction
{
    use LoggerAccess;

    /**
     * Http request
     *
     * @var WP_REST_Request
     */
    protected $request = null;

    /**
     * The hasPermissions callback is called twice, this property
     * prevents duplicate logs.
     *
     * @var int
     */
    public static $log_write = 0;

    /**
     * Constructor
     *
     * @param WP_REST_Request $request WP http request
     */
    public function __construct(WP_REST_Request $request)
    {
        $this->request = $request;
    }

    /**
     * Extract payload for protected endpoints
     *
     * @return array
     */
    protected function getPayload(): array
    {
        $body = $this->request->get_body();
        $decoded = json_decode($body, true);
        $payload = SafeAccess::fromArray($decoded, "payload", "string", "{}");
        return json_decode($payload, true);
    }

    /**
     * Dispatcher
     * Call rest action method by http request method
     *
     * @param WP_REST_Request $request http request
     *
     * @return mixed
     */
    public static function dispatch(WP_REST_Request $request)
    {
        $w3total = IntegrationServiceFactory::getService('w3total');
        $w3total::disable();

        $obj = new static($request);
        $method = strtolower($request->get_method());
        if (method_exists($obj, $method)) {
            return call_user_func([$obj, $method]);
        }
        return new WP_REST_Response(null, 501);
    }

    /**
     * Validate message to protected namespace
     *
     * @param WP_REST_Request $request http request
     *
     * @return bool
     */
    public static function hasPermissions(WP_REST_Request $request): bool
    {
        if ($request->get_method() == 'GET') {
            $params = $request->get_params();
            $token = SafeAccess::fromArray($params, "token", "string", "");
            unset($params['token']);
            $payload = empty($params)? (object)[] : $params;
            $payload = json_encode($payload);
        } else {
            $body = $request->get_body();
            $params = json_decode($body, true);
            $payload = SafeAccess::fromArray($params, "payload", "string", "{}");
            $token = SafeAccess::fromArray($params, "token", "string", "");
        }
        $auth_serv = TokenAuthorizationManager::create();

        $has_access = $auth_serv->validate($payload, $token);

        $is_debug = Options::get("is_debug");
        if ($is_debug) {
            $has_access = true;
        }

        if (static::$log_write < 1) {
            if ($has_access) {
                static::logDebug("Successful authorization", $params);
            } else {
                static::logDebug("Authorization failed", $params);
            }
            static::$log_write += 1;
        }
        return $has_access;
    }
}
