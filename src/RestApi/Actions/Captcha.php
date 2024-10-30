<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Db\ModelFactory;
use Clickio\Db\Models\Captcha as ModelCaptcha;
use Clickio\Options;
use Clickio\RestApi as rest;
use Clickio\Utils\Captcha as UtilsCaptcha;
use WP_REST_Response;

/**
 * Start captcha chellenge
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/captcha/
 *
 * @package RestApi\Actions
 */
class Captcha extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        if (!function_exists('imagettftext')) {
            $data = ["error" => "Function 'imagettftext' not found. Try to compile GD librarry with TrueType2 support."];
            return new WP_REST_Response(wp_json_encode($data), 405);
        }

        $captcha = UtilsCaptcha::generateBase64();
        $appkey = Options::getApplicationKey();
        $sign = md5($captcha['letters'].$appkey);
        $resp = [
            "mime" => 'image/png',
            "sign" => $sign,
            "img" => $captcha['img']
        ];

        $model = ModelFactory::create(ModelCaptcha::class);
        $model->insert($captcha['letters'], $sign);

        $chance = random_int(0, 100);
        if ($chance <= 20) {
            $model->cleanUpUnsolved();
        }
        return $resp;
    }


}
