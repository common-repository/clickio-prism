<?php
/**
 * Widget utils
 */

namespace Clickio\Utils;

use Clickio\Db\ModelFactory;
use Clickio\Db\Models\PageWidgets;
use Clickio\Options;

/**
 * Widget utils
 *
 * @package Utils
 */
class Widgets
{
    /**
     * Sidebars filter
     *
     * @var array
     */
    protected static $not_sidebar = [
        "wp_inactive_widgets",
        "array_version"
    ];

    /**
     * Meta key in posts
     *
     * @var string
     */
    const POST_WIDGETS_META_KEY = '_clickio_active_widgets';

    /**
     * Founded widgets
     *
     * @var array
     */
    protected static $page_widgets = [];

    /**
     * List of active widgets
     *
     * @return array
     */
    public static function getActiveWidgets(): array
    {
        $widgets = [];
        $widgets_ids = static::getActiveWidgetsIds();

        foreach ($widgets_ids as $id) {
            $widget = static::getWidget($id);

            if (empty($widget)) {
                continue ;
            }
            $widgets[$id] = $widget;
        }
        return $widgets;
    }

    /**
     * ID's list of active widgets
     *
     * @return array
     */
    public static function getActiveWidgetsIds(): array
    {
        $widgets_ids = [];
        $sidebars = get_option('sidebars_widgets');

        foreach ($sidebars as $sidebar_name => $sidebar) {
            if (in_array($sidebar_name, static::$not_sidebar)) {
                continue ;
            }

            foreach ($sidebar as $wgt_id) {
                $widgets_ids[] = $wgt_id;
            }
        }

        return $widgets_ids;
    }

    /**
     * Get sidebar widgets by name
     *
     * @param string $sidebar_name sidebar name
     *
     * @return array
     */
    public static function getSidebarWidgets(string $sidebar_name = ''): array
    {
        $sidebar_struct = [];
        $sidebars = get_option('sidebars_widgets');
        foreach ($sidebars as $name => $sidebar) {
            if (in_array($name, static::$not_sidebar)) {
                continue ;
            }
            if ($name == $sidebar_name) {
                $sidebar_struct = $sidebar;
                break;
            }
        }
        return $sidebar_struct;
    }

    /**
     * Split widget id on base_id and sequence
     *
     * @param string $id widget id e.g. custom_html-2
     *
     * @return array
     */
    public static function parseWidgetId(string $id): array
    {
        $_expl = explode('-', $id);
        $number = 0;
        $key = '';
        if (count($_expl) > 2) {
            $number = array_pop($_expl);
            $key = implode("-", $_expl);
        } else {
            $key = array_shift($_expl);
            $number = array_shift($_expl);
        }
        return [$key, $number];
    }

    /**
     * Get widget data like title, params etc.
     *
     * @param string $id widget id e.g. custom_html-2
     *
     * @return array
     */
    public static function getWidgetConfig(string $id): array
    {
        list($key, $number) = static::parseWidgetId($id);
        $widgets_conf = get_option('widget_'.$key);
        $conf = [];
        if (array_key_exists($number, $widgets_conf)) {
            $conf = $widgets_conf[$number];
        }

        if (empty($conf)) {
            $conf = [];
        }
        return $conf;
    }

    /**
     * Get widget object by id
     *
     * @param string $id widget id e.g. custom_html-2
     *
     * @return array
     */
    public static function getWidget(string $id): array
    {
        global $wp_widget_factory;
        list($key, $number) = static::parseWidgetId($id);
        $widget = [];
        $obj = null;
        foreach ($wp_widget_factory->widgets as $widget_obj) {
            if ($widget_obj->id_base == $key) {
                $obj = $widget_obj;
                break ;
            }
        }

        if (empty($obj)) {
            return $widget;
        }

        $widgets_conf = static::getWidgetConfig($id);

        $title = $obj->name;
        if (array_key_exists('title', $widgets_conf) && !empty($widgets_conf['title'])) {
            $title = $widgets_conf['title'];
        }

        $widget['title'] = $title;
        $widget['obj'] = $obj;
        $widget['params'] = $widgets_conf;
        return $widget;
    }

    /**
     * Lookup page widgets
     *
     * @param array $instance widget instance
     *
     * @return array
     */
    public static function lookupPageWidgets($instance)
    {
        wp_reset_postdata();
        wp_reset_query();
        if (!has_action('wp_print_footer_scripts', [static::class, 'updatePageWidgets'])) {
            add_action('wp_print_footer_scripts', [static::class, 'updatePageWidgets']);
        }
        if (is_array($instance) && array_key_exists('id', $instance)) {
            static::$page_widgets[] = $instance['id'];
        }
        return $instance;
    }

    /**
     * Save widgets for current page type
     *
     * @return void
     */
    public static function updatePageWidgets()
    {
        wp_reset_postdata();
        wp_reset_query();
        $widgets = array_unique(static::$page_widgets);
        if (LocationType::isPost()) {
            $post_id = get_the_ID();
            update_post_meta($post_id, static::POST_WIDGETS_META_KEY, $widgets);
        } else {
            $term_id = '';
            $post_type = '';
            $location_type = LocationType::getType();
            $obj = get_queried_object();
            if (LocationType::isTaxonomy()) {
                $term_id = $obj->term_id;
            }

            if (LocationType::isArchive()) {
                if ($obj instanceof \WP_Post_Type) {
                    $post_type = $obj->name;
                }
            }
            $model = ModelFactory::create(PageWidgets::class);
            $model->replace(implode(",", $widgets), $term_id, $post_type, $location_type);
        }
    }

    /**
     * Get widgets active on this page
     *
     * @return array
     */
    public static function getPageWidgets(): array
    {
        if (Options::get('log_widgets') != 1) {
            return [];
        }

        wp_reset_postdata();
        wp_reset_query();
        $widgets = [];
        if (LocationType::isPost()) {
            $id = get_the_ID();
            $widgets = get_post_meta($id, static::POST_WIDGETS_META_KEY);
            if (!empty($widgets)) {
                $widgets = array_shift($widgets);
            }
        } else {
            $term_id = 0;
            $post_type = '';
            $location_type = LocationType::getType();
            $obj = get_queried_object();
            if (LocationType::isTaxonomy()) {
                $term_id = $obj->term_id;
            }

            if (LocationType::isArchive()) {
                if ($obj instanceof \WP_Post_Type) {
                    $post_type = $obj->name;
                }
            }

            $model = ModelFactory::create(PageWidgets::class);
            $params = [
                "term_id" => $term_id,
                "post_type" => $post_type,
                "location_type" => $location_type
            ];
            $row = $model->selectRow($params);
            if (!empty($row) && array_key_exists('widget_list', $row)) {
                $widgets = explode(",", $row['widget_list']);
            }
        }

        if (empty($widgets)) {
            $widgets = [];
        }

        return array_values($widgets);
    }
}
