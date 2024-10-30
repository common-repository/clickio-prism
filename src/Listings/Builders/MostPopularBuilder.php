<?php
/**
 * Attachment builder
 */

namespace Clickio\Listings\Builders;

use Clickio\Integration\Services\WordPressPopularPosts;
use Clickio\Listings\Containers\FilterListingContainer;
use Clickio\Listings\Interfaces\IPostBuilder;
use Clickio\Utils\Container;

/**
 * Build listing by popularity
 *
 * @package Listings\Builders
 */
final class MostPopularBuilder extends PostBuilder implements IPostBuilder
{

    /**
     * Builder alias
     *
     * @var string
     */
    const ALIAS = 'mostpopular';

    /**
     * Builder main function
     *
     * @param Container $params query result
     *
     * @return FilterListingContainer
     */
    public function build(Container $params): FilterListingContainer
    {
        $posts = $this->getWppPostsIds($params);
        $params->post__in = $posts;
        $params->endpoint = '';

        return parent::build($params);
    }

    /**
     * Get posts from WordPress Popular Posts
     *
     * @param Container $params filter parameters
     *
     * @return array
     */
    protected function getWppPostsIds(Container $params): array
    {
        $wpp_params = [
            'limit' => $params->posts_per_page,
            'range' => 'last24hours',
            'freshness' => 1,
            'order_by' => 'views',
        ];
        $posts = WordPressPopularPosts::getPopularPosts($wpp_params);
        return $posts;
    }
}