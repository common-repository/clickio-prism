<?php
/**
 * WordPress Popular Posts
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Actions with WordPress Popular Posts plugin
 *
 * @package Integration\Services
 */
final class WordPressPopularPosts extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wordpress-popular-posts/wordpress-popular-posts.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wpp';

    /**
     * Get popular posts ids list
     *
     * @param array $params popular posts params
     *
     * @return array
     */
    public static function getPopularPosts(array $params): array
    {
        if (!static::integration() || !class_exists('\WordPressPopularPosts\Query')) {
            return [];
        }

        $query = new \WordPressPopularPosts\Query($params);
        $posts = $query->get_posts();

        if (empty($posts)) {
            $posts = [];
        }

        $ids_list = array_map(
            function ($el) {
                return $el->id;
            },
            $posts
        );
        if (empty($ids_list)) {
            $ids_list = [];
        }

        return $ids_list;
    }
}