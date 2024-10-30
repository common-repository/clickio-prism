<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;

/**
 * Get post media
 *
 * Sinopsys:
 *      GET http://domain.name/wp-json/clickio/media/(?P<id>\d+)
 *
 * @package RestApi\Actions
 */
class GetMedia extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $id = $this->request->get_param('id');
        $size = $this->request->get_param('size');

        $response = ['Alt' => get_post_meta($id, '_wp_attachment_image_alt', true)];
        foreach (['thumbnail', 'medium', 'large', 'full'] as $v) {
            $res = wp_get_attachment_image_src($id, $v);
            $response[$v] = ['Src' => $res[0], 'Width' => $res[1], 'Height' => $res[2]];
        }
        return $response;
    }
}
