<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\RestApi as rest;
use Clickio\Utils\UserUtils;

/**
 * Some user actions
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/captcha/
 *
 * @package RestApi\Actions
 */
class User extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{

    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $resp = [
            "authenticated" => false,
            "uid" => 0,
            "email" => "",
            "name" => "",
            "voted_id" => []
        ];

        $user = UserUtils::getCurrentUser();
        if (empty($user)) {
            return $resp;
        }

        $resp['authenticated'] = true;
        $resp['uid'] = UserUtils::encryptUserId($user->ID);
        $resp['email'] = $user->user_email;
        $resp['name'] = $user->display_name;

        $post_id = $this->request->get_param('post_id');
        if (!empty($post_id)) {
            $discuz = IntegrationServiceFactory::getService('wpdiscuz');
            $resp['voted_id'] = $discuz::getPostUserVotes($post_id, $user->ID);
        }

        return $resp;
    }
}