<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;
use Clickio\Utils\PolicyCheck as UtilsPolicyCheck;
use Clickio\Utils\SafeAccess;
use WP_HTTP_Response;

/**
 * Check policy errors
 *
 * Example:
 *      POST http://domain.name/wp-json/clickio/policy
 *      {
 *          "url": "https://example.com/example-post-name-123/",
 *      }
 *
 * @package RestApi\Actions
 */
class PolicyCheck extends rest\BaseRestAction implements rest\Interfaces\IRestApi
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
        $url = SafeAccess::fromArray($params, 'url', 'string', '');
        if (empty($params) || json_last_error() || empty($url)) {
            $err = [
                "message" => "Field 'url' is required"
            ];
            return new WP_HTTP_Response($err, 400);
        }

        $post_id = url_to_postid($url);
        if (empty($post_id)) {
            return new WP_HTTP_Response(["message" => "Post not found"], 400);
        }

        $status = UtilsPolicyCheck::getStatus($post_id);
        $status['valid_until_dt'] = date("Y-m-d H:i:s", $status['valid_until']);
        $status['is_policy'] = UtilsPolicyCheck::isPolicy($post_id);
        return $status;
    }
}
