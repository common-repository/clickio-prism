<?php

/**
 * Determine current page type
 */

namespace Clickio\Utils;

/**
 * Determine current location type
 *
 * @package Utils
 */
class LocationType
{

    /**
     * Is home page
     *
     * @return bool
     */
    public static function isHome(): bool
    {
        return is_home() || is_front_page();
    }

    /**
     * Is post page
     *
     * @return bool
     */
    public static function isPost(): bool
    {
        if (is_archive() || is_search()) {
            return false;
        }

        $all_types = get_post_types(array('_builtin' => false));

        // prevent warnings
        if (empty($all_types)) {
            $all_types = [];
        }

        $custom_types = array_keys($all_types);
        $current_type = get_post_type();
        if ($current_type == 'attachment') {
            return false;
        }

        if (empty($current_type)) {
            $current_type = 'post';
        }

        return is_single() || in_array($current_type, $custom_types);
    }

    /**
     * Is Category page
     *
     * @return bool
     */
    public static function isCategory(): bool
    {
        return is_category();
    }

    /**
     * Is author page
     *
     * @return bool
     */
    public static function isAuthor(): bool
    {
        return is_author();
    }

    /**
     * Is tag page
     *
     * @return bool
     */
    public static function isTag(): bool
    {
        return is_tag();
    }

    /**
     * Is static page
     *
     * @return bool
     */
    public static function isPage(): bool
    {
        if (is_front_page()) {
            return false;
        }
        return is_page();
    }

    /**
     * Is "archive" page
     * more info https://developer.wordpress.org/reference/functions/is_archive/
     *
     * @return bool
     */
    public static function isArchive(): bool
    {
        return is_archive();
    }

    /**
     * Is date page
     *
     * @return bool
     */
    public static function isDate(): bool
    {
        return is_date();
    }

    /**
     * Is search page
     *
     * @return bool
     */
    public static function isSearch(): bool
    {
        return is_search();
    }

    /**
     * Is "taxononmy" page
     * This combine is_tax() || is_category() || is_tag() methods
     *
     * @return bool
     */
    public static function isTaxonomy(): bool
    {
        return is_tax() || is_category() || is_tag();
    }

    /**
     * Is 404 error page
     *
     * @return bool
     */
    public static function is404(): bool
    {
        return is_404();
    }

    /**
     * Is page of the deleted post
     *
     * @return bool
     */
    public static function isTrashed(): bool
    {
        if (!static::is404()) {
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'];
        $id = url_to_postid($uri);
        $post = null;
        if (!empty($id)) {
            $post = get_post($id);
        } else {
            global $wp, $wpdb;

            $name = SafeAccess::fromArray($wp->query_vars, 'name', 'string', '');

            if (!empty($name)) {
                $q = "SELECT ID FROM $wpdb->posts WHERE post_name like '$name%'";
                $post_row = $wpdb->get_row($q);
                if (!empty($post_row) && property_exists($post_row, 'ID')) {
                    $post = get_post($post_row->ID);
                }
            }
        }

        if (!empty($post) && $post->post_status == 'trash') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is get_id page
     *
     * @return bool
     */
    public static function isGetId(): bool
    {
        $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        return preg_match("/[\?\&]get_id/", $url);
    }

    /**
     * Get current page type
     *
     * @return string
     */
    public static function getType(): string
    {
        $types = [
            "home" => static::isHome(),
            "post" => static::isPost(),
            "tag" => static::isTag(),
            "author" => static::isAuthor(),
            "taxonomy" => static::isTaxonomy(),
            "archive" => static::isArchive(),
            "page" => static::isPage(),
            "search" => static::isSearch(),
            "error" => static::is404()
        ];

        $type = 'undefined';
        foreach ($types as $type_name => $type_value) {
            if ($type_value) {
                $type = $type_name;
                break;
            }
        }
        return $type;
    }

    /**
     * Get template name for current page
     *
     * @return string
     */
    public static function getCurrentTemplate(): string
    {
        $tag_templates = array(
            'is_embed' => 'get_embed_template',
            'is_404' => 'get_404_template',
            'is_search' => 'get_search_template',
            'is_front_page' => 'get_front_page_template',
            'is_home' => 'get_home_template',
            'is_privacy_policy' => 'get_privacy_policy_template',
            'is_post_type_archive' => 'get_post_type_archive_template',
            'is_tax' => 'get_taxonomy_template',
            'is_attachment' => 'get_attachment_template',
            'is_single' => 'get_single_template',
            'is_page' => 'get_page_template',
            'is_singular' => 'get_singular_template',
            'is_category' => 'get_category_template',
            'is_tag' => 'get_tag_template',
            'is_author' => 'get_author_template',
            'is_date' => 'get_date_template',
            'is_archive' => 'get_archive_template',
        );
        $tpl = false;

        foreach ($tag_templates as $tag => $template_getter) {
            if (call_user_func($tag)) {
                $tpl = call_user_func($template_getter);
            }

            if ($tpl) {
                break;
            }
        }

        if (!$tpl) {
            $tpl = get_index_template();
        }
        $realpath = realpath($tpl);
        $themepath = get_template_directory();
        $path = str_replace($themepath.'/', '', $realpath);
        return $path;
    }
}
