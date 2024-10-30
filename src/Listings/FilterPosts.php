<?php
/**
 * Filter by custom parametrs
 */

namespace Clickio\Listings;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Logger\Interfaces\ILogger;
use Clickio\Logger\Logger;
use Clickio\Utils\SafeAccess;

/**
 * Filter posts by custom params
 *
 * @package Utils
 */
class FilterPosts
{

    /**
     * Logger instance
     *
     * @var ILogger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param ILogger $logger logger instance
     */
    public function __construct(ILogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Filter posts
     *
     * @param array $params query params
     *
     * @return array
     */
    public function filter(array $params): array
    {
        $post_list = [];
        $used_posts = [];
        add_filter('excerpt_more', '__return_empty_string');

        $wpb = IntegrationServiceFactory::getService('wpb');
        $wpb::registerShortcodes();

        $this->logger->debug("Listings requested with params", $params);
        foreach ($params as $item) {
            $args = FilterParamsContainer::create($item);

            if (!$args->ignore_duplicates) {
                $args->post_exclude = $used_posts;
            }

            if ($args->smart_excerpt) {
                add_filter("get_the_excerpt", [$this, 'smartExcerpt'], 999, 2);
            }
            $builder_alias = $args->post_type;
            if (empty($builder_alias) || !BuilderFactory::builderExists($builder_alias)) {
                $builder_alias = 'post';
            }
            $builder = BuilderFactory::create($builder_alias, [$args]);
            $listing = $builder->build($args);

            if (!$args->ignore_duplicates) {
                foreach ($listing->itemsGenerator() as $item) {
                    $used_posts[] = $item->id;
                }
            }
            $post_list[] = $listing->toArray();
        }
        return $post_list;
    }

    /**
     * Factory method.
     * Create instance and then call filter
     *
     * @param array $params query params
     *
     * @return array
     */
    public static function getData(array $params): array
    {
        $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $logger = Logger::getLogger($domain);
        $inst = new static($logger);
        $new_params = $inst->_repackParams($params);
        return $inst->filter($new_params);
    }

    /**
     * Repack query params
     *
     * @param array $params params
     *
     * @return array
     */
    private static function _repackParams(array $params): array
    {
        $new_params = [];

        foreach ($params as $param) {
            $tax_q = SafeAccess::fromArray($param, 'tax_query', 'array', []);
            if (!empty($tax_q)) {
                foreach ($tax_q as $tax_query_params) {
                    if (is_array($tax_query_params)) {
                        $tax_query_params["include_children"] = array_key_exists("child_categories", $param);
                    }
                }
            }
            if (SafeAccess::arrayKeyExists('split', $tax_q) && $tax_q['split']) {
                unset($param['tax_query']);
                $taxonomy = $tax_q[0]['taxonomy'];
                $order = SafeAccess::fromArray($tax_q, 'order', 'string', 'alpha_desc');
                $params = [
                    "taxonomy" => $taxonomy
                ];
                $params = array_merge($params, static::_getSplitOrderingParams($order));
                $terms = get_terms($params);
                if (empty($terms) || is_wp_error($terms)) {
                    continue ;
                }
                foreach ($terms as $tax) {
                    $struct = $param;
                    $struct['taxonomy_term'] = $tax->name;
                    $struct['taxonomy'] = get_taxonomy($taxonomy)->label;
                    $struct['tax_query']["relation"] = $tax_q['relation'];
                    $struct['tax_query'][] = [
                        "taxonomy" => $taxonomy,
                        "terms" => $tax->term_id,
                        "include_children" => array_key_exists("child_categories", $param)
                    ];
                    $new_params[] = $struct;
                }
                continue ;
            }
            $new_params[] = $param;
        }
        return $new_params;
    }

    /**
     * Get ordering params for get_term
     *
     * @param string $ordering order param in tax_query
     *
     * @return array
     */
    private static function _getSplitOrderingParams(string $ordering): array
    {
        $order_by = '';
        $order = '';
        switch ($ordering) {
            case 'alpha_asc':
                $order_by = 'name';
                $order = 'ASC';
                break;
            case 'alpha_desc':
                $order_by = 'name';
                $order = 'DESC';
                break;
            case 'id_desc':
                $order_by = 'id';
                $order = 'DESC';
                break;
            default:
                $order_by = 'name';
                $order = 'DESC';
                break;
        }
        return ['orderby' => $order_by, 'order' => $order];
    }

    /**
     * Customized get_the_excerpt filter
     * Used when smart_excerpt option is true
     *
     * @param string $excerpt post excerpt
     * @param WP_Post $post_obj post object
     *
     * @return string
     */
    public static function smartExcerpt($excerpt, $post_obj)
    {
        $excerpt = trim($excerpt);
        if (!empty($excerpt)) {
            return $excerpt;
        }

        $content = apply_filters('the_content', get_the_content(null, false, $post_obj));
        $excerpt_length = intval(_x('55', 'excerpt_length'));
        $excerpt_length = (int) apply_filters('excerpt_length', $excerpt_length);
        $excerpt_more = apply_filters('excerpt_more', ' [&hellip;]');
        $excerpt = wp_trim_words($content, $excerpt_length, $excerpt_more);
        return $excerpt;
    }
}
