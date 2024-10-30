<?php
/**
 * Attachment builder
 */

namespace Clickio\Listings\Builders;

use Clickio\Listings\Containers\FilterItemContainer;
use Clickio\Listings\Containers\FilterListingContainer;
use Clickio\Listings\Interfaces\IPostBuilder;
use Clickio\Utils\Container;

/**
 * Build listing as attachment
 *
 * @package Listings\Builders
 */
final class AttachmentBuilder extends AbstractBuilder implements IPostBuilder
{

    /**
     * Builder alias
     *
     * @var string
     */
    const ALIAS = 'attachment';

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

        $params->post_status = 'inherit';
        $params->per_page = 0;

        $listings = new FilterListingContainer();
        $query = new \WP_Query($params->toArray());
        $posts_array = $query->get_posts();

        $listings = new FilterListingContainer();
        $listings->id = $params->id;
        $listings->total_count = $query->found_posts;
        $listings->taxonomy_term = $params->taxonomy_term;
        $listings->taxonomy = $params->taxonomy;

        foreach ($posts_array as $idx => $post) {
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
            $item->link = get_permalink($post->ID);
            $item->title =  $post->post_title;
            $item->format = \get_post_format($post->ID);
            $images = $this->getFeatureImage($post->ID, $size, $desired, $min_width, $params->use_cropped);
            $item->WpFeaturedImage = $images;
            $item->content = $post->post_content;
            $item->excerpt = $post->post_excerpt;
            $listings->addItem($item);
        }

        return $listings;
    }
}