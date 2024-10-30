<?php
/**
 * Wordpress config
 */

namespace Clickio\Utils;

use Clickio\Integration\IntegrationServiceFactory;

/**
 * Wordpress config class
 *
 * @package Utils
 */
final class WpConfig
{

    /**
     * Get complete wordpress config
     *
     * @return array
     */
    public static function getFullConfig(): array
    {
        global $wp_rewrite;
        $permalink_struct = '';
        if (!empty($wp_rewrite) && property_exists($wp_rewrite, 'permalink_structure')) {
            $permalink_struct = $wp_rewrite->permalink_structure;
        }

        $polylang = IntegrationServiceFactory::getService("polylang");
        $conf = [
            "comments" => [
                "moderation" => !empty(get_option('comment_moderation')),
                "status" => static::commentsAreOpen(),
                "require_name_email" => !empty(get_option('require_name_email')),
                "show_avatars" => !empty(get_option("show_avatars")),
                "nested" => !empty(get_option('thread_comments')),
                "nested_level" => static::getNestedCommentsLevel(),
                "guest" => static::getGuestCommentStatus()
            ],
            "router" => [
                "permalink_struct" => $permalink_struct
            ],
            "multilang" => [
                "status" => $polylang::integration(),
                "languages" => $polylang::getLanguageList()
            ]
        ];
        return $conf;
    }

    /**
     * Get comments status
     *
     * @param int $post_id post id
     *
     * @return bool
     */
    public static function commentsAreOpen(int $post_id = 0): bool
    {
        $status = get_option('default_comment_status') == 'open';
        if (LocationType::isPost() || LocationType::isPage()) {
            $status = comments_open($post_id);
        }
        return $status;
    }

    /**
     * Get nested comments level
     *
     * @return int
     */
    public static function getNestedCommentsLevel(): int
    {
        $nested = get_option('thread_comments');
        $level = get_option('thread_comments_depth');
        if (!empty($nested) && !empty($level)) {
            return intval($level, 10);
        }
        return 0;
    }

    /**
     * Is guests comments allowed
     *
     * @return bool
     */
    public static function getGuestCommentStatus(): bool
    {
        $wp_status = get_option("comment_registration")? false : true;

        $discuz = IntegrationServiceFactory::getService("wpdiscuz");
        $discuz_status = $discuz::getFormCommentStatus();
        return $discuz_status === null? $wp_status : (bool)$discuz_status;
    }
}
