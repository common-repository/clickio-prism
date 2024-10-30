<?php
/**
 * Breadcrumbs
 */

namespace Clickio\Utils;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Logger as log;
use Clickio\Logger\Interfaces\ILogger;
use Clickio\Meta\PostMeta;

/**
 * Breadcrumbs utils
 *
 * @package Utils
 */
class Breadcrumbs
{

    /**
     * Yoast SEO breadcrumbs
     *
     * @var string
     */
    const YOAST = 'wpseo';

    /**
     * NavXT breadcrumbs
     *
     * @var string
     */
    const NAVXT = 'navxt';

    /**
     * TagDiv Composer breadcrumbs
     *
     * @var string
     */
    const TAGDIV = 'tagdiv';

    /**
     * Custom breadcrumbs
     *
     * @var string
     */
    const NATIVE = 'native';

    /**
     * Default structure
     *
     * @var array
     */
    protected $breadcrumbs = [
        "type" => "native",
        "items" => []
    ];

    /**
     * Post
     *
     * @var \WP_Post
     */
    private $_post = 0;

    /**
     * Constructor
     *
     * @param ILogger $logger logger
     * @param \WP_Post $post post
     */
    public function __construct(ILogger $logger, $post)
    {
        $this->log = $logger;
        $this->_post = $post;
    }

    /**
     * Factory method
     * Get breadcrumbs
     *
     * @param int $postid post id
     * @param string $service where to get breadcrumbs
     *
     * @return array
     */
    public static function getBreadcrumbs(int $postid = 0, string $service = ''): array
    {
        $host = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $logger = log\Logger::getLogger($host);

        wp_reset_postdata();
        $post = get_post($postid);
        $inst = new static($logger, $post);

        $breadcrumbs = [];
        switch ($service) {
            case static::YOAST:
                $breadcrumbs = $inst->yoastBreadcrumbs();
                break;
            case static::NAVXT:
                $breadcrumbs = $inst->navxtBreadcrumbs();
                break;
            case static::NATIVE:
                $breadcrumbs = $inst->nativeBreadcrumbs();
                break;
            case static::TAGDIV:
                $breadcrumbs = $inst->tagDivBreadcrumbs();
                break;
            default:
                $breadcrumbs = $inst->nativeBreadcrumbs();
                break;
        }
        return $breadcrumbs;
    }

    /**
     * Get breadcrumbs for all types
     *
     * @param int $postid post id
     *
     * @return array
     */
    public static function getAllBreadcrumbs(int $postid = 0): array
    {
        $types = [
            static::NATIVE,
            static::YOAST,
            static::NAVXT,
            static::TAGDIV
        ];
        $breadcrumbs = [];
        foreach ($types as $type) {
            $breadcrumbs[] = static::getBreadcrumbs($postid, $type);
        }
        return $breadcrumbs;
    }

    /**
     * Get custom breadcrumbs
     *
     * @return array
     */
    public function nativeBreadcrumbs(): array
    {

        $items = [];
        if (LocationType::isPost()) {
            $items = $this->getPostBreadcrumbs();
        } else if (LocationType::isTaxonomy()) {
            $items = $this->getTaxonomyBreadcrumbs();
        } else if (LocationType::isArchive() && !LocationType::isHome()) {
            $items = $this->getArchiveBreadcrumbs();
        }
        $this->breadcrumbs['items'] = array_reverse($items);
        $this->breadcrumbs['type'] = static::NATIVE;
        return $this->breadcrumbs;
    }

    /**
     * Get breadcrumbs from Yoast Seo
     *
     * @return array
     */
    public function yoastBreadcrumbs(): array
    {
        $this->breadcrumbs['type'] = static::YOAST;

        $service = IntegrationServiceFactory::getService(static::YOAST);
        $yoast_breadcrumbs = $service::getBreadcrumbs();
        if (empty($yoast_breadcrumbs)) {
            return $this->breadcrumbs;
        }

        $first = $yoast_breadcrumbs[0];
        $url = $first['link'];
        if (substr($url, -1)=== '/') {
            $url = substr($url, 0, (strlen($url) - 1));
        }
        $home = home_url();
        if ($home == $url) {
            $yoast_breadcrumbs = array_slice($yoast_breadcrumbs, 1, null, true);
        }

        // repack
        $breadcrumbs = [];
        foreach ($yoast_breadcrumbs as $single) {
            $breadcrumbs[] = [
                "Name" => $single['name'],
                "Url" => $single['link']
            ];
        }

        $this->breadcrumbs['items'] = $breadcrumbs;
        return $this->breadcrumbs;
    }

    /**
     * Get bradcrumbs from Breadcrumbs NavXT
     *
     * @return array
     */
    public function navxtBreadcrumbs(): array
    {
        $this->breadcrumbs['type'] = static::NAVXT;
        $service = IntegrationServiceFactory::getService(static::NAVXT);
        $navxt_breadcrumbs = $service::getBreadcrumbs();
        array_shift($navxt_breadcrumbs);
        foreach ($navxt_breadcrumbs as $item) {
            $this->breadcrumbs['items'][] = [
                "Name" => $item['name'],
                "Url" => $item['link'],
            ];
        }
        return $this->breadcrumbs;
    }

    /**
     * Generate post breadcrumbs
     *
     * @return array
     */
    protected function getPostBreadcrumbs(): array
    {
        if (empty($this->_post)) {
            return [];
        }

        $category = (new PostMeta($this->_post))->getCategories();
        if (empty($category)) {
            return [];
        }

        $term = [];
        foreach ($category as $_cat) {
            if (property_exists($_cat, 'primary') && $_cat->primary) {
                $term = $_cat;
                break;
            }
        }

        if (empty($term)) {
            $term = array_shift($category);
        }

        if (empty($term) || is_wp_error($term)) {
            return [];
        }

        return $this->_getCascadeTaxonomy($term->term_id);
    }

    /**
     * Generate taxonomy breadcrumbs
     *
     * @return array
     */
    protected function getTaxonomyBreadcrumbs(): array
    {
        $term_id = get_queried_object_id();

        if (empty($term_id)) {
            return [];
        }
        return $this->_getCascadeTaxonomy($term_id);
    }

    /**
     * Generate cascade breadcrumbs
     *
     * @param int $term_id taxonomy id
     *
     * @return array
     */
    private function _getCascadeTaxonomy(int $term_id): array
    {

        $breadcrumbs = [];
        do {
            $term = get_term($term_id);
            $link = get_term_link($term->term_id);
            $link = str_replace('/./', '/', $link);

            $breadcrumbs[] = [
                "Name" => $term->name,
                "Url" => $link
            ];
            $term_id = $term->parent;
        } while (!empty($term->parent));

        return $breadcrumbs;
    }

    /**
     * Breadcrumbs for archive pages
     *
     * @return array
     */
    protected function getArchiveBreadcrumbs(): array
    {
        if (empty($this->_post)) {
            return [];
        }
        $items[] = [
            "Name" => post_type_archive_title('', false),
            "Url" => get_post_type_archive_link($this->_post->post_type)
        ];
        return $items;
    }

    /**
     * Get breadcrumbs from tagDiv Composer
     *
     * @return array
     */
    public function tagDivBreadcrumbs(): array
    {
        $this->breadcrumbs['type'] = self::TAGDIV;
        $td_composer = IntegrationServiceFactory::getService('td_composer');
        $breadcrumbs = $td_composer::getBreadcrumbs($this->_post);

        foreach ($breadcrumbs as $item) {
            $this->breadcrumbs['items'][] = [
                'Name' => $item['display_name'],
                'Url' => $item['url']
            ];
        }
        return $this->breadcrumbs;
    }
}