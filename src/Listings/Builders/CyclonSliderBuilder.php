<?php
/**
 * CycloneSlider builder
 */

namespace Clickio\Listings\Builders;

use Clickio\Listings\Containers\FilterItemContainer;
use Clickio\Listings\Containers\FilterListingContainer;
use Clickio\Listings\Interfaces\IPostBuilder;
use Clickio\Utils\Container;
use Clickio\Utils\SafeAccess;

/**
 * Build listing as cycloneslider
 *
 * @package Listings\Builders
 */
final class CyclonSliderBuilder extends AbstractBuilder implements IPostBuilder
{
    /**
     * Builder alias
     *
     * @var string
     */
    const ALIAS = 'cycloneslider';

    /**
     * Get builder alias
     *
     * @return string
     */
    public static function getAlias(): string
    {
        return static::ALIAS;
    }

    /**
     * Builder main function
     *
     * @param Container $params query result
     *
     * @return FilterListingContainer
     */
    public function build(Container $params): FilterListingContainer
    {
        if ($params->ignore_duplicates) {
            $params->post_exclude = [];
        }

        $query = new \WP_Query($params->toArray());
        $posts_array = $query->get_posts();

        $listings = new FilterListingContainer();
        $listings->id = $params->id;
        $listings->taxonomy_term = $params->taxonomy_term;
        $listings->taxonomy = $params->taxonomy;

        if (empty($posts_array)) {
            return $listings;
        }

        $slider = array_pop($posts_array);
        $meta = get_post_meta($slider->ID);
        $img_meta = unserialize($meta['_cycloneslider_metas'][0]);
        $listings->total_count = count($img_meta);

        foreach ($img_meta as$idx => $img_info) {
            $post = get_post($img_info['id'], \OBJECT);
            if (empty($post)) {
                continue ;
            }

            $size = $params->image_size;
            $desired = $params->image_size_width;
            $min_width = $params->image_size_min_width;
            if (!$idx) {
                // if first item
                $size = $params->first_image_size;
                $desired = $params->first_image_size_width;
                $min_width = $params->first_image_size_min_width;
            }
            $item = new FilterItemContainer();
            $item->id = $post->ID;
            $item->date =  mysql_to_rfc3339($post->post_date);
            $item->guid =  $post->guid;
            $item->modified =  mysql_to_rfc3339($post->post_modified);
            $item->type =  $post->post_type;
            $item->link = SafeAccess::fromArray($img_info, 'link', "string", "");
            $item->title = SafeAccess::fromArray($img_info, 'title', "string", "");
            $item->format = \get_post_format($post->ID);
            $featured_img = $this->getFeatureImage($post->ID, $size, $desired, $min_width, $params->use_cropped);
            $featured_img['Alt'] = SafeAccess::fromArray($img_info, 'img_alt', "string", "");
            $item->WpFeaturedImage = $featured_img;
            $item->content = SafeAccess::fromArray($img_info, 'description', "string", "");
            $item->excerpt =  SafeAccess::fromArray($img_info, 'img_title', "string", "");
            $listings->addItem($item);
        }
        return $listings;
    }
}