<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;

/**
 * Get page by id
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/page/(?P<id>\d+)/
 *
 * @package RestApi\Actions
 */
class GetPage extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $id = $this->request->get_param('id');
        $description = get_post_meta($id, '_yoast_wpseo_metadesc', true);
        if ($description == '') {
            $description = get_bloginfo('description');
        }

        $keywords = get_post_meta($id, "keywords", true);
        if ($keywords  == '') {
            $keywords = strtolower(get_bloginfo('name'));
        }

        return [
            'description' => $description,
            'keywords' =>  $keywords,
            'site_description' => get_bloginfo('description'),
            'site_name' =>  get_bloginfo('name')
        ];
    }
}