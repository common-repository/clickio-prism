<?php
/**
 * Yoast seo integration
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\LocationType;
use Clickio\Utils\Plugins;
use Clickio\Utils\SafeAccess;

/**
 * Yoast Seo Integrtion
 *
 * @package Integration\Services
 */
class WPSEO extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wordpress-seo/wp-seo.php';

    /**
     * Premium version
     *
     * @var string
     */
    const PREMIUM_PLUGIN_ID = 'wordpress-seo-premium/wp-seo-premium.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wpseo';

    /**
     * Tets integration is available
     *
     * @return bool
     */
    public static function integration(): bool
    {
        $wpseo = Plugins::pluginIsActive(static::PLUGIN_ID);
        $wpseo_premium = Plugins::pluginIsActive(static::PREMIUM_PLUGIN_ID);
        if ($wpseo) {
            $plugin_id = static::PLUGIN_ID;
        } else if ($wpseo_premium) {
            $plugin_id = static::PREMIUM_PLUGIN_ID;
        } else {
            return false;
        }

        $dir = ABSPATH."/wp-content/plugins/";
        $plugin_data = get_plugin_data($dir.$plugin_id);
        $version = SafeAccess::fromArray($plugin_data, 'Version', 'string', '1.0');
        return version_compare($version, '13.0') >= 0;
    }

    /**
     * Get list of plugins with which the service can integrate
     *
     * @return array
     */
    protected static function getIntegrationList(): array
    {
        return [static::PLUGIN_ID, static::PREMIUM_PLUGIN_ID];
    }

    /**
     * Get category info
     *
     * @param string $field_name field name
     * @param string $term_id category id
     *
     * @return string
     */
    public static function getCategoryMeta(string $field_name, int $term_id): string
    {
        if (!static::integration()) {
            return '';
        }

        switch($field_name){
            case 'description':
                return static::getCategoryDescription($term_id);
        }
        return '';
    }

    /**
     * Get category description
     *
     * @param int $term_id category id
     *
     * @return string
     */
    public static function getCategoryDescription(int $term_id): string
    {
        if (!static::integration()) {
            return '';
        }

        if (class_exists("\WPSEO_Taxonomy_Meta")) {
            $meta_obj = \WPSEO_Taxonomy_Meta::get_term_meta($term_id, 'category');
            $desc = SafeAccess::fromArray($meta_obj, 'wpseo_desc', 'string', '');
            $desc = trim(str_replace('&nbsp;', ' ', $desc));
            if (!empty($desc)) {
                return $desc;
            }
        }
        return '';
    }

    /**
     * Meta tag description
     *
     * @return string
     */
    public static function getMetaDescription(): string
    {
        if (!static::integration()) {
            return '';
        }

        $metadesc = '';
        try {
            ob_start();
            if (LocationType::isHome()) {
                if (class_exists('\WPSEO_Options') && method_exists(\WPSEO_Options::class, 'get')) {
                    $metadesc = \WPSEO_Options::get('metadesc-home-wpseo');
                }
            } else if (LocationType::isPost()) {
                $post = get_post();
                $metadesc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
            }
            ob_end_clean();
        } catch (\Exception $err) {
            // do nothing
        }
        return $metadesc;
    }

    /**
     * Link tag rel=robots
     *
     * @return string
     */
    // public static function getMetaRobots(): string
    // {
    //     if (!static::integration()) {
    //         return '';
    //     }

    //     if (class_exists('\WPSEO_Frontend')) {
    //         $cls = \WPSEO_Frontend::get_instance();
    //         $robots = '';
    //         if (method_exists($cls, 'get_robots')) {
    //             $robots = $cls->get_robots();
    //         }
    //         return $robots;
    //     }
    //     return '';
    // }

    /**
     * Link tag rel=canonical
     *
     * @return string
     */
    // public static function getMetaCanonical(): string
    // {
    //     if (!static::integration()) {
    //         return '';
    //     }

    //     if (class_exists('\WPSEO_Frontend')) {
    //         $cls = \WPSEO_Frontend::get_instance();
    //         $canonical = '';
    //         if (method_exists($cls, 'canonical')) {
    //             $canonical = $cls->canonical(false);
    //         }
    //         return $canonical;
    //     }
    //     return '';
    // }

    /**
     * Get JSON Linked Data
     *
     * @return string
     */
    /*public static function getJsonLd(): array
    {
        if (!static::integration()) {
            return [];
        }

        if (!class_exists('\WPSEO_Schema')) {
            if (defined('WPSEO_FILE')) {
                $cls_file = sprintf("%s/frontend/schema/class-schema.php", dirname(WPSEO_FILE));
                if (is_readable($cls_file)) {
                    include_once $cls_file;
                }
            }
        }

        if (!class_exists('\WPSEO_Schema')) {
            return [];
        }

        $schema_obj = new \WPSEO_Schema();
        ob_start();
        if (method_exists($schema_obj, 'generate')) {
            $schema_obj->generate();
        }
        $schema = ob_get_clean();
        ob_end_clean();

        $re = '/<script.*>(.*)<\/script>/m';
        preg_match_all($re, $schema, $matches, PREG_SET_ORDER, 0);

        $json_schema = array_pop($matches);
        if (empty($json_schema) || count($json_schema) < 2) {
            return [];
        }

        return json_decode($json_schema[1], true);
    }*/

    /**
     * Breadcrumbs
     *
     * @return array
     */
    public static function getBreadcrumbs(): array
    {

        if (!static::integration()
            || !class_exists('\WPSEO_Breadcrumbs')
            || !method_exists('\WPSEO_Breadcrumbs', 'breadcrumb')
        ) {
            return [];
        }

        ob_start();
        try {
            $breadcrumbs = \WPSEO_Breadcrumbs::breadcrumb('', '', false);
        } catch (\Exception $err) {
            // do nothing
        }
        ob_end_clean();

        if (empty($breadcrumbs)) {
            $breadcrumbs = '';
        }

        $re = '/<a.*href=["\']?(?P<link>[^"\'>]+)["\'].*>(?P<name>.*)<\/a>/m';
        preg_match_all($re, $breadcrumbs, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return [];
        }

        $breadcrumbs = [];
        foreach ($matches as $match) {
            $struct = [];

            $struct['link'] = $match['link'];
            $struct['name'] = $match['name'];
            $breadcrumbs[] = $struct;
        }
        return $breadcrumbs;
    }

    /**
     * Get primary category id
     *
     * @param int $post_id post id
     *
     * @return int
     */
    public static function getPrimaryTerm(int $post_id): int
    {
        if (!static::integration()
            || !class_exists("\WPSEO_Primary_Term")
        ) {
            return 0;
        }


        $primary_term = new \WPSEO_Primary_Term('category', $post_id);

        if (!method_exists($primary_term, "get_primary_term")) {
            return 0;
        }
        $primary_term_id = $primary_term->get_primary_term();
        if (empty($primary_term_id)) {
            $primary_term_id = 0;
        }
        return $primary_term_id;
    }
}
