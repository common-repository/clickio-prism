<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Addons\AddonManager;
use Clickio\Db\ModelFactory;
use Clickio\Db\Models\Captcha;
use Clickio\RestApi\Actions\Containers\SubscribeContainer;
use Clickio\RestApi\BaseRestAction;
use Clickio\RestApi\Interfaces\IRestApi;
use WP_REST_Response;

/**
 * Subscribe to a newsletter
 *
 * Example:
 *      POST http://domain.name/wp-json/clickio/subscribe/
 *      {
 *          "email": john.doe@example.com,
 *          "terms_cond": true,
 *          "full_name": "John Doe"
 *      }
 *
 * @package RestApi\Actions
 */
class Subscribe extends BaseRestAction implements IRestApi
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
            return new WP_REST_Response(["code" => 400, "msg" => 'invalid_request'], 400);
        }

        if (array_key_exists("captcha", $params)) {
            $cont = SubscribeContainer::create($params);
            if (!$cont->isValid()) {
                $resp['code'] = 400;
                $resp['result'] = false;
                $resp['msg'] = $cont->getErrors();
                return new WP_REST_Response($resp, 400);
            }
            unset($params['captcha']);
            unset($params['sign']);
            $model = ModelFactory::create(Captcha::class);
            $model->deleteRow('captcha_hash', $cont->sign);
        }

        // addons are not loaded automatically in the rest api
        $addons_manager = new AddonManager();
        $addons_manager->loadAddons();

        $ret = [
            "success" => false,
            "msg" => ""
        ];
        $subscribe_resp = apply_filters('_clickio_subscribe', $ret, $params);
        $resp = [
            'code' => 200,
            'result' => $subscribe_resp['success'],
            'status' => $subscribe_resp['msg'],
        ];
        return $resp;
    }
}
