<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;
use Clickio\Utils\SafeAccess;

/**
 * Get single post by id
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/post/(?P<id>\d+)/
 *
 * @package RestApi\Actions
 */
class GetPost extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $id = $this->request->get_param('id');
        $meta = get_post_meta($id);
        $description = array_key_exists('_yoast_wpseo_metadesc', $meta)? $meta['_yoast_wpseo_metadesc'][0]: '';
        if ($description == '') {
            $description = get_bloginfo('description');
        }
        $keywords = get_post_meta($id, "keywords", true);

        if ($keywords  == '') {
            $keywords = strtolower(get_bloginfo('name'));
        }

        $video_url_list = SafeAccess::fromArray($meta, 'video_url', 'string', '');
        $video_url = SafeAccess::fromArray($video_url_list, 0, 'string', '');
        $embed = wp_oembed_get($video_url, true);
        if ($embed == false ) {
            $embed ='';
        }
        if ($embed == '{{unknown}}') {
            $embed='';
        }
        $review_rating = SafeAccess::fromArray($meta, '_rev_review_rating', 'array', []);
        return [
            'description' => $description,
            'keywords' =>  $keywords,
            'site_description' => get_bloginfo('description'),
            'site_name' =>  get_bloginfo('name'),
            'video_url' => $embed,
            'review_rating' => $review_rating,
        ];
    }
}
