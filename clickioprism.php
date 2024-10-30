<?php
/**
 * Clickio Prism
 *
 * @link              https://clickio.com
 * @package           Clickio Prism
 *
 * @wordpress-plugin
 * Plugin Name:       Clickio Prism Plugin
 * Description:       Transform your website with Clickio Prism
 * Version:           2.29.16
 * Author:            Clickio
 * Author URI:        https://clickio.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clickioprism
 * Domain Path:       /languages
 */

if (defined('CLICKIO_PRISM_VERSION')) {
    return ;
}

if (!defined('CLICKIO_PLUGIN_DIR')) {
    define('CLICKIO_PLUGIN_DIR', dirname(__FILE__));
}

define('CLICKIO_PLUGIN_NAME', plugin_basename(__FILE__));
require_once CLICKIO_PLUGIN_DIR."/vendor/autoload.php";

use Clickio as org;
use Clickio\ClickioPlugin;
use Clickio\Utils as util;
use Clickio\ExtraContent as ec;
use Clickio\ExtraContent\Services\FieldsContent;
use Clickio\ExtraContent\Services\HooksContent;
use Clickio\ExtraContent\Services\ShortCodesContent;
use Clickio\ExtraContent\Services\WidgetsContent;
use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Integration\Services\GenesisFramework;
use Clickio\Integration\Services\WPEL;
use Clickio\Integration\Services\WPSEO;
use Clickio\Integration\Services\WPSubtitle;
use Clickio\Logger\Logger;
use Clickio\Meta\PostMeta;
use Clickio\Meta\TermMeta;
use Clickio\Options;
use Clickio\Prism\Cache\CacheManager;
use Clickio\Prism\PrismManager;
use Clickio\Utils\CacheUtils;
use Clickio\Utils\DeviceType;
use Clickio\Utils\ImageInfo;
use Clickio\Utils\Locale;
use Clickio\Utils\SafeAccess;
use Clickio\Utils\LocationType;
use Clickio\Utils\Permalink;
use Clickio\Utils\PolicyCheck;
use Clickio\Utils\Seo;
use Clickio\Utils\Widgets;
use Clickio\Utils\WpConfig;

const clickio_urlPrefix = 'amp';

$get_id_flag = false;

if (! defined('WPINC')) {
    die;
}

// @codingStandardsIgnoreLine
if (defined('WP_CLI') && \WP_CLI) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

if (function_exists('php_sapi_name')) {
    $sapi = php_sapi_name();
    if ('cli' == strtolower($sapi)) {
        $_SERVER['HTTP_HOST'] = 'localhost';
    }
}

define('CLICKIO_PRISM_VERSION', '2.29.16');

$clickio_plugin = org\ClickioPlugin::getInstance();
$clickio_plugin->setupEventListners();

add_action('init', 'clickio_my_init', 25);
add_action('wp_head', 'clickio_add_amp_link', \PHP_INT_MAX);

add_action('wp', 'amp_clickio_content', 999999);

add_action('do_parse_request', 'clickio_prizm');

add_action('shutdown', 'getIdErrorHandler', 0, 0);

// TODO: refactoring required, convert to Prism service
function clickio_prizm()
{
    $cl_debug = org\ClickioPlugin::getInstance()->getPreviewMode();
    if (substr($_SERVER['REQUEST_URI'], -5) == '.iswp' ) {
        $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, -5);
    }
    $uriParts = explode('/', $_SERVER['REQUEST_URI']);


    if ($uriParts[1] == 'cl-static'
        || $uriParts[1] == 'related'
        || $uriParts[1] == 'manifest'
        || $uriParts[1] == 'sw.js'
        || $uriParts[1] == 'pwa'
    ) {
        $w3total = IntegrationServiceFactory::getService('w3total');
        $w3total::disable();

        $getUrl = 'https://'.org\Options::getPwaHost().$_SERVER['REQUEST_URI'];
        if (!empty($cl_debug) && $cl_debug > 1) {
            print $getUrl;
            exit(1);
        }
        $data = wp_remote_get(
            $getUrl, [
            'timeout' => 5,
            'followlocation' => true,
            'sslverify' => false,
            'headers' => array("Referer" => SafeAccess::fromArray($_SERVER, 'HTTP_REFERER', 'string', '')),
            ]
        );

        if (!is_wp_error($data) && !empty($data) && $data !== false && $data['response']['code'] == 200) {

            $amp_src = SafeAccess::fromArray($_GET, '__amp_source_origin', 'string', '');
            header('Content-Type: '. $data['headers']['content-type']);
            header('Access-Control-Allow-Origin: '.SafeAccess::fromArray($_SERVER, 'HTTP_ORIGIN', 'string', '*'));
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin');
            header('AMP-Access-Control-Allow-Source-Origin: '.urldecode($amp_src));

            echo $data['body'];
            exit;
        } else {
            print_r($data);
            exit;
        }
        exit;
    }
    return true;
}

// TODO: refactoring required, move to ClickioPlugin class
function clickio_my_init()
{
    // redirect if bot came with cl_.*
    $uri = Permalink::getFullCurrentUrl();
    $plugin_only = Options::get('integration_scheme') == 'cms';
    if ($plugin_only && DeviceType::isBot() && preg_match("/[\?\&]cl_.*/", $uri)) {
        $path = explode("?", $uri);
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $path[0]);
        exit ;
    }

    require_once CLICKIO_PLUGIN_DIR."/src/compatibility.php";
    global $wp_post_types;
    $customtypes = org\Options::get('customtypes', "");
    $post_type_names = explode(',', $customtypes);
    foreach ($post_type_names as $post_type_name) {
        if (isset($wp_post_types[$post_type_name])) {
            $wp_post_types[$post_type_name]->show_in_rest = true;
            // Optionally customize the rest_base or controller class
            $wp_post_types[$post_type_name]->rest_base = $post_type_name;
            $wp_post_types[$post_type_name]->rest_controller_class = 'WP_REST_Posts_Controller';
        }
    }
}

// TODO: refactoring required, must be part of Prizm service
function clickio_get_id($options, $display = true)
{
    do_action('_clickio_before_get_id');
    // if the page is redirected in the original version
    // then it should be redirected when get_id is requested.
    //
    // This will stop the plugin execution and return a 301 http response
    (ClickioPlugin::getInstance())->canonicalRedirect();

    $w3total = IntegrationServiceFactory::getService('w3total');
    $w3total::disable();
    if (is_embed()) {
        $struct = ["wp_type" => "alien"];
        echo wp_json_encode($struct, JSON_PRETTY_PRINT);
        exit ;
    }


    ob_start();
    global $clickio_plugin;
    add_filter("content_pagination", [$clickio_plugin, 'disablePagination']);
    add_filter("the_content", [$clickio_plugin, 'filterContent']);

    $wpfront = IntegrationServiceFactory::getService('wpfront');
    $wpfront::disable();

    remove_filter('the_title', 'wptexturize');

    $rest_url = ClickioPlugin::getRestUrl();
    $rest_url .= "/";

    $id = get_the_ID();
    $meta = get_post_meta($id);

    $wpb = IntegrationServiceFactory::getService('wpb');
    $wpb::registerShortcodes();

    $bjll = IntegrationServiceFactory::getService('bjll');
    $bjll::disable();

    $redis_oc = IntegrationServiceFactory::getService('redis_oc');
    $redis_oc::disableDebugOut();

    $memberpress = IntegrationServiceFactory::getService('memberpress');
    $memberpress::disablePostRules();

    $lazyload_videos = IntegrationServiceFactory::getService('lazyload_videos');
    $lazyload_videos::disable();

    $addthis = IntegrationServiceFactory::getService('addthis');
    $addthis::disable();

    $addfunc = IntegrationServiceFactory::getService('addfunc_header_footer');
    $addfunc::disable();

    $wpforms = IntegrationServiceFactory::getService('wpforms');
    $wpforms::adaptFormForPrism();

    $docket_cache = IntegrationServiceFactory::getService('docket_cache');
    $docket_cache::disable();

    $onesignal = IntegrationServiceFactory::getService("onesignal");
    $onesignal::setupListners();

    $quiz_maker = IntegrationServiceFactory::getService("quiz_maker");
    $quiz_maker::listenShortcodes();

    $polylang = IntegrationServiceFactory::getService("polylang");
    $polylang::disableCanonicalRedirect();

    $wpdiscuz = IntegrationServiceFactory::getService("wpdiscuz");
    $wpdiscuz::addCommentsTemplateToExtra();

    $data = [
        'config' => WpConfig::getFullConfig(),
        'PostInfo' => [
            'description' => Seo::getMetaDescription(),
            'site_name' =>  get_bloginfo('name'),
            'site_title' => Seo::getTitle(),
            'site_description' => get_bloginfo('description'),
            'language' => str_replace("_", "-", Locale::getCurrentLocale())
        ],
        "src_type" => LocationType::getType(),
        "title" => [
            'rendered' => get_the_title(),
        ],
        "canonical_url" => Permalink::getCurrentLocationUrl(),
        "template" => LocationType::getCurrentTemplate(),
        "widgets" => Widgets::getPageWidgets(),
    ];

    if (is_home() || is_front_page()) {
            $data['wp_type'] = 'home';
            $data['id'] = 1;
    } else if (is_author()) {
        $data['type'] = 'author';
        $data['wp_type'] = 'author';

        $author = get_queried_object();
        $author_id = $author->ID;
        $data['id'] = $author_id;
        $data['author'] = [[
            'href'       => $rest_url. 'users/' . $author->post_author,
            'embeddable' => true,
        ]];

        $author_avatar_urls=[];
        $uphoto = IntegrationServiceFactory::getService('uphoto');
        foreach ( [ 24, 48, 96 ] as $size ) {
            if ($uphoto::integration()) {
                $author_avatar_urls[ $size ] = $uphoto::getAvatar($author->post_author);
            } else {
                $author_avatar_urls[ $size ] = get_avatar_url($author->user_email, array( 'size' => $size));
            }
        }

        if (function_exists('get_field')) {
            if ($authorAvatar = get_field('user_photo', 'user_'.$author_id, false)) {
                if ($image = wp_get_attachment_image_src($authorAvatar, [96,96])) {
                    foreach ( [ 24, 48, 96 ] as $size ) {
                        $author_avatar_urls[ $size ] = $image[0];
                    }
                }
            }
        }

        $author_desc = get_the_author_meta('description', $author->post_author);
        $data['WpAuthor']=[
            'id' => $author_id,
            'name' => $author->display_name,
            'link' => get_author_posts_url($author->ID, $author->user_nicename),
            'description' => $author_desc,
            'feed' => get_author_feed_link($user->ID, 'rss2'),
            'slug' => $user->user_nicename,
            'avatar_urls' => $author_avatar_urls,
        ];
    } else if (is_tag()) {
        $data['type'] = 'tag';
        $data['wp_type'] = 'tag';
        $tag = get_queried_object();
        $data['id'] = $tag->term_id;
        $data['title']['rendered'] = $tag->name;
        if ($tag->description) {
            $data['content']['rendered'] = apply_filters('the_content', $tag->description);
        }
    } else if (is_archive()) {
        $data['type'] = 'category';
        $category = get_queried_object();

        if (!empty($category) && $category instanceof \WP_Term) {
            $cat_desc = '';
            if ($category->term_id) {
                $cat_desc = GenesisFramework::getTermMeta('description', $category->term_id);
            }

            if (empty($cat_desc) && property_exists($category, 'term_id') && !empty($category->term_id)) {
                $_vanilla_desc = apply_filters('category_description', $category->description, 0);
                $_vanilla_desc = trim(str_replace('&nbsp;', ' ', $_vanilla_desc));
                if (!empty($_vanilla_desc)) {
                    $cat_desc = $_vanilla_desc;
                } else {
                    $_yoast_desc = WPSEO::getCategoryMeta('description', $category->term_id);
                    if (!empty($_yoast_desc)) {
                        $cat_desc = $_yoast_desc;
                    }
                }
            }
            $data['PostInfo']['link'] = get_term_link($category);
            $data['PostInfo']['description'] = $cat_desc;
        } else if ($category instanceof \WP_Post_Type) {
            $data['PostInfo']['description'] = apply_filters('category_description', $category->description, 0);
            $data['PostInfo']['link'] = get_post_type_archive_link(get_post_type());
        }

        if ($category instanceof \WP_Term || $category instanceof \WP_Post_Type) {
            $data['PostInfo']['name'] = $category->name;
        }

        if (LocationType::isTaxonomy()) {
            if (empty($category->taxonomy) || $category->taxonomy == 'post_tag') {
                $data['WpCategoriesInfo'] = [];
            } else {
                $data['id'] = $category->term_id;
                $data['taxonomy'] = $category->taxonomy;
                $opts = [
                    "parent" => $category->parent,
                    "orderby" => "slug",
                    'hide_empty' => false,
                ];
                $data['WpCategoriesInfo'] = TermMeta::getTerms($category->taxonomy, $opts);
            }
        } else {
            $data['id'] = get_the_ID();
        }

        if (get_post_type() && get_post_type() != 'post') {
            $data['wp_type'] = get_post_type();
        } else {
            $data['wp_type'] = 'category';
        }

    } else if (is_singular('post') || get_post_type()) {

        $post = get_post();
        $post_meta = new PostMeta($post);
        setup_postdata($post);
        if (!empty($post->post_parent)) {
            $data['parent'] = $post->post_parent;
        }

        $data['protected'] = $memberpress::isPostProtected($post);
        $data['id'] = get_the_ID();

        $create_dt = get_post_datetime($data['id'], 'date');
        if (empty($create_dt)) {
            $create_dt = new DateTimeImmutable($post->post_date);
        }
        $data['date'] = $create_dt->format("Y-m-d H:i:s P");
        $data['guid']['rendered'] =  $post->guid;

        $modif_dt = get_post_datetime($data['id'], 'modified');
        if (empty($modif_dt)) {
            $modif_dt = new DateTimeImmutable($post->post_modified);
        }
        $data['modified'] = $modif_dt->format("Y-m-d H:i:s P");
        $data['type'] =  $post->post_type;
        $rating = $post_meta->getRating();
        if (!empty($rating)) {
            $data['PostInfo']['rating'] = $rating;
        }

        $time = $post_meta->getReadingTime();
        if ($time >= 0) {
            $data['read_time'] = $time;
        }

        $views = $post_meta->getViewsCounter();
        if ($views >= 0) {
            $data['views'] = $views;
        }

        $td_composer = IntegrationServiceFactory::getService('td_composer');
        if (WPSubtitle::integration()) {
            $data['subtitle']['rendered'] = WPSubtitle::getTheSubtitle($post->ID);
        } else if ($td_composer::integration()) {
            $data['subtitle']['rendered'] = $td_composer::getSubtitle($post->ID);
        } else {
            $single = true;
            $subtitle = get_post_meta($post->ID, 'subtitle', $single);
            if (!empty($subtitle)) {
                $data['subtitle']['rendered'] = $subtitle;
            }
        }
        global $numpages, $page;
        $data['content']['current_page'] = $page;
        $data['content']['total_pages'] = $numpages;

        do_action('_clickio_getid_before_content');
        $content = apply_filters('the_content', get_the_content());
        $wpel = IntegrationServiceFactory::getService("wpel");
        $data['content']['rendered'] = $wpel::apply($content);
        $data['excerpt']['rendered'] =  get_the_excerpt();
        do_action('_clickio_getid_after_content');

        $video_url = get_post_meta($post->ID, 'video_url', true);
        if ($video_url) {
            $video_html = wp_oembed_get($video_url);
            $data['PostInfo']['video_url'] = empty($video_html)? '' : $video_html;
        }
        $data['WpCategoriesInfo'] =  $post_meta->getCategories();
        if (LocationType::isPost()) {
            $topcat = Seo::getTopLevelCategory();
            if (!empty($topcat)) {
                $topcat_desc = apply_filters('category_description', $topcat->description, 0);
                if (empty($topcat_desc)) {
                    $topcat_desc = GenesisFramework::getTermMeta('description', $topcat->term_id);
                }

                $data['top_level_category'] = [
                    "term_id" => $topcat->term_id,
                    "name" => $topcat->name,
                    "slug" => $topcat->slug,
                    "term_group" => $topcat->term_group,
                    "term_taxonomy_id" => $topcat->term_taxonomy_id,
                    "taxonomy" => $topcat->taxonomy,
                    "description" => $topcat_desc,
                    "parent" => $topcat->parent,
                    "count" => $topcat->count,
                    "filter" => $topcat->filter,
                    "link" => get_category_link($topcat->term_id)
                ];
            }
        }

        $polylang = IntegrationServiceFactory::getService("polylang");
        $data['translations'] = $polylang::getPostTranslations($post->ID);

        $data['_links'] =  [
            'self'       => [ ['href' => $rest_url.$post->post_type .'/'. $post->ID]  ] ,
            'collection' => [ ['href' => $rest_url. $post->post_type .'/'] ] ,
            'about'      => [ ['href' => $rest_url.'types/' . $post->post_type]  ],
            ];

        $data['author'] = [[
            'href'       => $rest_url. 'users/' . $post->post_author,
            'embeddable' => true,
        ]];
        $user = get_userdata($post->post_author);
        $author_avatar_urls=[];
        $uphoto = IntegrationServiceFactory::getService('uphoto');
        foreach ( [ 24, 48, 96 ] as $size ) {
            if ($uphoto::integration()) {
                $author_avatar_urls[ $size ] = $uphoto::getAvatar($post->post_author);
            } else {
                $author_avatar_urls[ $size ] = get_avatar_url($user->user_email, array( 'size' => $size));
            }
        }
        
        if (function_exists('get_field')) {
            if ($authorAvatar = get_field('user_photo', 'user_'.$user->ID, false)) {
                if ($image = wp_get_attachment_image_src($authorAvatar, [96,96])) {
                    foreach ( [ 24, 48, 96 ] as $size ) {
                        $author_avatar_urls[ $size ] = $image[0];
                    }
                }
            }
        }

        $author_desc = get_the_author_meta('description', $post->post_author);
        if (empty($author_desc)) {
            $author_desc = GenesisFramework::getUserMeta('description', $post->post_author);
        }
        $data['WpAuthor']=[
            'id' => $user->ID,
            'name' => $user->display_name,
            'link' => get_author_posts_url($user->ID, $user->user_nicename),
            'description' => $author_desc,
            'feed' => get_author_feed_link($user->ID, 'rss2'),
            'slug' => $user->user_nicename,
            'avatar_urls' => $author_avatar_urls,
        ];

        if ($co_author = org\Options::get('co_author', '')) {

            if ($t = get_post_custom_values($co_author, $post->ID)) {

                if (isset($t[0]) && $t[0]) {

                    if ($author = get_userdata($t[0])) {

                        $author_avatar_urls=[];
                        $uphoto = IntegrationServiceFactory::getService('uphoto');
                        foreach ( [ 24, 48, 96 ] as $size ) {
                            if ($uphoto::integration()) {
                                $author_avatar_urls[ $size ] = $uphoto::getAvatar($author->ID);
                            } else {
                                $author_avatar_urls[ $size ] = get_avatar_url($author->user_email, array( 'size' => $size));
                            }
                        }

                        $author_desc = get_the_author_meta('description', $author->ID);
                        if (empty($author_desc)) {
                            $author_desc = GenesisFramework::getUserMeta('description', $author->ID);
                        }

                        $data['CoAuthor']=[[
                            'id' => $author->ID,
                            'name' => $author->display_name,
                            'link' => get_author_posts_url($author->ID, $author->user_nicename),
                            'description' => $author_desc,
                            'feed' => get_author_feed_link($author->ID, 'rss2'),
                            'slug' => $author->user_nicename,
                            'avatar_urls' => $author_avatar_urls,
                        ]];
                    }
                }
            }
        }

        $tags = get_terms('post_tag', ['object_ids' => $post->ID ]);
        $data['WpTags']['Tags']=[];

        foreach ($tags as $tag) {
            $data['WpTags']['Tags'][] = [
                'id' => $tag->term_id,
                'count'  => (int) $tag->count,
                'description' => $tag->description,
                'link' => get_term_link($tag),
                'name' => $tag->name,
                'slug' => $tag->slug,
                'taxonomy' => $tag->taxonomy,
                'parent' => (int) $tag->parent,
            ];
        }

        $data['replies'] = [[
            'href'       => $rest_url. 'comments?post=' . $post->ID ,
            'embeddable' => true,
        ]];

        $comments = $post_meta->getComments();
        if (!empty($comments)) {
            $data['Comments'] = $comments;
        }

        $image_id = get_post_thumbnail_id($post->ID);
        if (!empty($image_id)) {
            $data['wp:featuredmedia'] = [
                'href'       => ImageInfo::getMediaRestUrl($image_id),
                'embeddable' => true,
            ];

            $data['ImageInfo'] = ImageInfo::getImageInfo($image_id);
        }

        if (!in_array($post->post_type, array('attachment', 'nav_menu_item', 'revision'), true)) {
            $attachments_url = $rest_url. 'media' ;
            $attachments_url = add_query_arg('parent', $post->ID, $attachments_url);
            $data['wp:attachment'] = [ 'href' => $attachments_url ];
        }

        $taxonomies = get_object_taxonomies($post->post_type);

        if (!empty($taxonomies)) {
            $data['wp:term'] = [];

            foreach ($taxonomies as $tax) {
                $taxonomy_obj = get_taxonomy($tax);

                // Skip taxonomies that are not public.
                if (empty($taxonomy_obj->show_in_rest) || empty($taxonomy_obj->public)) {
                    continue;
                }

                $tax_base = !empty($taxonomy_obj->rest_base) ? $taxonomy_obj->rest_base : $tax;

                $terms_url = add_query_arg(
                    'post',
                    $post->ID,
                    $rest_url. $tax_base
                );

                $data['wp:term'][] = [
                    'href'       => $terms_url,
                    'taxonomy'   => $tax,
                    'embeddable' => true,
                ];
            }
        }

    } elseif (is_page()) {
        $data['type'] = 'pages';
        $data['id'] = get_the_ID();
        $data['content' ] = get_the_content(null, false, get_post());
    } elseif (get_post_type() == 'recensioni') {
        $data['type'] = 'recensioni';
        $data['id'] = get_the_ID();
        $data['content' ] = get_the_content(null, false, get_post());
    } elseif (get_post_type() == 'video') {
        $data['type'] = 'video';
        $data['id'] = get_the_ID();
        $data['video'] = wp_oembed_get(get_post_meta(get_the_ID(), 'video_url', true));
        $data['content' ] = get_the_content(null, false, get_post());
    } elseif (get_post_type() == 'offerta') {
        $data['type'] = 'offerta';
        $data['id'] = get_the_ID();
        $data['content' ] = get_the_content(null, false, get_post());
    } elseif (LocationType::isTrashed()) {
        status_header(410, 'Gone');
    }

    if (is_archive()) {
        $data['wp_is_category'] = 1;
    }

    if (is_date()) {
        $data['type'] = 'ignore';
    }


    $data['breadcrumbs'] = util\Breadcrumbs::getBreadcrumbs();
    $data['breadcrumbs_list'] = util\Breadcrumbs::getAllBreadcrumbs();

    // We need to get raw_head before extra content
    $raw_head = "";
    if (org\Options::get('enable_seo')) {
        $raw_head = Seo::getHead();
    }

    $force_extra_content = SafeAccess::arrayKeyExists('get_extra_content', $_REQUEST);
    $force_extra_opt = Options::get('force_extra');
    if (org\Options::get('extra_content') || ($force_extra_content || $force_extra_opt)) {
        $services = [];
        if (empty($force_extra_content) && empty($force_extra_opt)) {
            $fields = org\Options::get('extra_fields');
            $widgets = org\Options::get('extra_widgets');
            $actions = org\Options::get('extra_actions');
            $custom_actions = org\Options::get('extra_custom_actions');
            $shortcodes = org\Options::get('extra_shortcodes');

            if (!empty($fields)) {
                $services[] = FieldsContent::getName();
            }

            if (!empty($widgets)) {
                $services[] = WidgetsContent::getName();
            }

            if (!empty($actions) || !empty($custom_actions)) {
                $services[] = HooksContent::getName();
            }

            if (!empty($shortcodes)) {
                $services[] = ShortCodesContent::getName();
            }

            $sources = org\Options::get('extra_sources');
            foreach ($sources as $data_source) {
                $parts = explode('.', $data_source);
                $source_name = array_shift($parts);
                if (!in_array($source_name, $services)) {
                    $services[] = $source_name;
                }
            }
            $services = array_filter(array_unique($services));
        }

        $extra_obj = ec\ExtraContent::create($services);

        if ($force_extra_content || $force_extra_opt) {
            $force = true;
        } else {
            $force = false;
        }
        $extra = $extra_obj->getExtraContent($force);
        if (!empty($extra)) {
            $data['extra'] = $extra;
        }
    }

    $data['raw_head'] = $raw_head;

    WPEL::disable(); // disable WP External Links plugin
    ob_get_clean();
    while (ob_get_length()) {
        ob_clean();
    }
    while (ob_get_level()) {
        @ob_end_clean();
    }

    $data = apply_filters("_clickio_after_getid", $data);
    global $get_id_flag;
    $get_id_flag = true;

    if ($display) {
        print wp_json_encode($data, JSON_PRETTY_PRINT);
    }
    define( 'DONOTCACHEPAGE', 1 );
    return wp_json_encode($data, JSON_PRETTY_PRINT);
}

// TODO: refactoring required, convert to AMP service
function amp_clickio_content()
{
    $headers = [];
    foreach (getallheaders() as $name => $val) {
        $headers[strtolower($name)] = $val;
    }

    // gtranslate
    $cl_debug = org\ClickioPlugin::getInstance()->getPreviewMode();
    if (empty($cl_debug) && array_key_exists('x-gt-lang', $headers) && !array_key_exists('clab', $_REQUEST)) {
        return ;
    }

    if (is_admin()) {
        return ;
    }


    if (!empty(get_query_var('feed')) || !empty(get_query_var('amp'))) {
        return ;
    }

    $w3total = IntegrationServiceFactory::getService('w3total');

    // $query_value = substr($_SERVER['REQUEST_URI'], 1, strlen(clickio_urlPrefix)) == clickio_urlPrefix;

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_array = explode("/", $uri);
    $path_array = array_values(array_filter($path_array));

    $amp_part = SafeAccess::fromArray($path_array, 0, 'string', '');
    $type = Options::get("type");
    if ($type == 'ampfolder_postfix') {
        $last_idx = (count($path_array) -1);
        $amp_part = SafeAccess::fromArray($path_array, $last_idx, 'string', '');
    }

    $amp_url = Options::get('amp_url');
    if (substr($amp_url, 0, 1) == '/') {
        $amp_url = substr($amp_url, 1);
    }
    if (substr($amp_url, -1, 1) == '/') {
        $amp_url = substr($amp_url, 0, strlen($amp_url) - 1);
    }

    $query_value = $amp_part == $amp_url;

    if (isset($_REQUEST['lx_ignore_wp'])) {
        $w3total::disable();
        if (SafeAccess::fromArray($_SERVER, 'HTTP_X_UA_DEVICE', 'string', '') =='desktop') {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . get_permalink());
            exit(0);
        }
        return;
    } elseif (isset($_REQUEST['lx_sh']) && !isset($_SERVER['HTTP_SWIPE'])) {
        if (get_the_ID() == 0) {
            return;
        }

        $w3total::disable();

        $path = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $path);
        exit(0);
    }

    $options = org\Options::getOptions();

    $get_id = SafeAccess::fromArray($_REQUEST, 'get_id', 'string', '');
    $_get_id_header = SafeAccess::fromArray($_SERVER, 'HTTP_X_CLICKIO_PRISM', 'string', '');
    if (!empty($get_id) || $_get_id_header == 'get_id') {
        header('Content-Type: application/json');
        try {
            clickio_get_id($options);
        } catch (\Exception $e) {
            $data = [
                "wp_type" => "alien",
                "trace" => var_export($e, true),
            ];
            echo wp_json_encode($data, JSON_PRETTY_PRINT);
        }
        exit(0);
    }

    if (isset($_REQUEST['lx_ignore_wp'])) {
        return;
    }

    $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
    $ABPWA = 0;
    if (isset($_SERVER['HTTP_X_AB'])) {
        $ABPWA = $_SERVER['HTTP_X_AB'];
    }
    $ABPWA+=1;

    $clab_val = SafeAccess::fromArray($_REQUEST, 'clab', 'mixed', null);
    if (empty($cl_debug) && $clab_val !== null && $clab_val >= 1) {
        return ;
    }

    $ua = SafeAccess::fromArray($_SERVER, 'HTTP_USER_AGENT', 'string', 'no user agent');
    $is_desctop = stripos($ua, 'Android') === false && stripos($ua, 'iPhone') === false && stripos($ua, 'iPad') === false && stripos($ua, 'iPod') === false;

    $allow_bot = true;
    if (stripos($ua, 'googlebot') && !$is_desctop) {
        $indexing = Options::get('allow_indexing');
        if (empty($indexing)) {
            $allow_bot = false;
        }
    }

    $not_prism = array_key_exists('HTTP_X_NOT_PRISM', $_SERVER);

    $_request_uri = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
    $_mobile_opt = org\Options::get("mobile", false);
    $_amp_opt = org\Options::get("useamp", false);
    $_integration = org\Options::get("integration_scheme", 'dns');
    $embed = preg_match("/\/embed/", $_request_uri);
    $static = preg_match("/\.(js|css|map|xml|ico|cljs|cljson|json)/", $_request_uri);
    $clab = preg_match("/clab=0/", $_request_uri);

    $_sh = SafeAccess::fromArray($_REQUEST, 'lx_sh', 'string', '');
    $_swipe = SafeAccess::fromArray($_SERVER, 'HTTP_SWIPE', 'string', '');
    $is_shadow = !empty($_sh) && !empty($_swipe);

    $status = org\Options::get('status');
    $_clab_cookie = SafeAccess::fromArray($_COOKIE, '_clab', 'mixed', null);

    $dns_active = $_integration == 'dns' && $status == 'active';
    if ($_integration == 'cms' && !empty($_mobile_opt) && $status != 'disabled' && $_clab_cookie === null) {
        $percent = org\Options::get('prism_version_rotation_percent');
        $rand = random_int(0, 100);
        $expires = time() + 1800;
        $path = "/";
        $secure = true;
        $httponly = true;
        if ($rand <= $percent) {
            $_clab_val = 0;
        } else {
            $_clab_val = 1;
        }
        setcookie("_clab", $_clab_val, $expires, $path, null, $secure, $httponly);
        $_clab_cookie = $_clab_val;
    }

    $is_authenticated = get_current_user_id();
    $disable_auth_redirect = Options::get("disable_auth_redirect");

    if (empty($cl_debug) && !empty($is_authenticated) && empty($disable_auth_redirect)) {
        return ;
    }

    $_infinity = SafeAccess::fromArray($_REQUEST, 'infinite_scroll', 'string', '');
    $force_amp = $query_value && $options['useamp'] == '0' && ($cl_debug > 0 || !empty($_infinity));

    $log_prism_debug = SafeAccess::fromArray($_REQUEST, 'log_prism_debug', 'string', '');
    if ($log_prism_debug) {
        $debug = [
            'REQUEST_URI: '.$_SERVER['REQUEST_URI'],
            'USER_AGENT: '.SafeAccess::fromArray($_SERVER, 'HTTP_USER_AGENT', 'string', 'undefined'),
            '---amp---',
            '$query_value: '.var_export($query_value, true),
            '$options["useamp"] == "1": '.var_export($options['useamp'] == '1', true),
            '$force_amp: '.var_export($force_amp, true),
            '---end_amp---',
            '$cl_debug: '.var_export($cl_debug, true),
            '!empty($clab): '.var_export(!empty($clab), true),
            '$clab: '.var_export($clab, true),
            '---mobile---',
            'isset($options["mobile"]): '.var_export(isset($options['mobile']), true),
            '$options["mobile"] == 1: '.var_export(($options['mobile'] == 1), true),
            '!$not_prism: '.var_export(!$not_prism, true),
            '$is_desctop != 1: '.var_export(($is_desctop != 1), true),
            '!$dns_active: '.var_export(!$dns_active, true),
            '$ABPWA == "1": '.var_export(($ABPWA == '1'), true),
            'empty($embed): '.var_export(empty($embed), true),
            'empty($static): '.var_export(empty($static), true),
            '$_clab_cookie == 0: '.var_export(($_clab_cookie == 0), true),
            '$allow_bot: '.var_export($allow_bot, true),
        ];
        global $clickio_plugin;
        $logger = $clickio_plugin->getLogger();
        $logger->debug('Show prism page', $debug);
    }

    if ($query_value && ($options['useamp'] == '1' || $force_amp)) {

        $w3total::disable();
        $mngr = CacheManager::make();
        $mngr->startCache();

        $parsed = wp_parse_url($_SERVER['REQUEST_URI']);
        $path = SafeAccess::fromArray($parsed, 'path', 'string', '');
        $query = SafeAccess::fromArray($parsed, 'query', 'string', '');
        $type = Options::get("type");

        if ($type == 'ampfolder_postfix') {
            $_request_path = preg_replace("/\/".$amp_url."[\/]*$/", '', $path);
        } else {
            $_request_path = preg_replace("/^[\/]*$amp_url/", '', $path);
        }
        $_request_uri = $_request_path;
        if (!empty($query)) {
            $_request_uri .= "?$query";
        }
        $ampHost = org\Options::getAmpHost();

        if (isset($_REQUEST['cl_beta'])) {
            $ampHost = 'amptest.clickiocdn.com';
        }

        $getUrl = 'http://'.$ampHost.'/a/'.$domain.$_request_uri;

        if (isset($_SERVER['HTTPS'])) {
            if (strpos($getUrl, '?') === false ) {
                $getUrl .= '?original_scheme=https&lx_wp=1';
            } else {
                $getUrl .= '&original_scheme=https&lx_wp=1';
            }
        }

        if ($force_amp) {
            $glue = '&';
            if (strpos($getUrl, '?') === false ) {
                $glue .= '?';
            }
            $getUrl .= $glue.'no_index=1';
        }

        if (!empty($cl_debug) && $cl_debug > 1) {
            print $getUrl;
            exit(1);
        }

        $data = wp_remote_get($getUrl, array(
            'timeout' => 5,
            'followlocation' => true,
            'sslverify' => false
        ));

        if (!is_wp_error($data) && !empty($data) && $data !== false && $data['response']['code'] == 200) {
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', 1);
            }
            status_header(200);
            echo $data['body'];
            exit;
        } else {
            if (!empty($cl_debug)) {
                print_r([$getUrl, $data]);
                exit(1);
            }
            $msg = "undefined error";
            if (is_wp_error($data)) {
                $msg = $data->get_error_message();
            } else if (empty($data)) {
                $msg = 'Daemon responds with empty body';
            } else if ($data['response']['code'] != 200) {
                $msg = $data['headers']['x-error'];
            }
            header('x-clickio-prism-error: '.$msg);
            header('Location: '.$_request_uri);
            exit;
        }
    //@codingStandardsIgnoreStart
    } else if ((!empty($cl_debug) && $cl_debug > 0 )
        || (!empty($clab))
        || (isset($options['mobile'])
            && $options['mobile'] == 1
            && !$not_prism
            && $is_desctop != 1
            && !$dns_active
            && $ABPWA == '1'
            && empty($embed)
            && empty($static)
            && $_clab_cookie == 0
            && $allow_bot)
        || $is_shadow
    ) {
    //@codingStandardsIgnoreEnd
        $w3total::disable();
        $mngr = CacheManager::make();
        $mngr->startCache();

        $prism = PrismManager::getPrismPage();

        $cache_status = CacheUtils::getCacheStatus();
        $buffer_status = CacheManager::getBufferingStatus();
        if ($cache_status) {
            if ($buffer_status) {
                $default_ttl = Options::get('cache_lifetime');
                $custom_ttl = CacheManager::getCustomLifetime();
                $ttl = empty($custom_ttl)? intval($default_ttl, 10) : $custom_ttl;
            } else {
                $ttl = 60;
            }
            CacheUtils::setCacheTtlHeaders(time(), time() + $ttl);
        }

        if (!empty($prism)) {
            global $is_prism;
            $is_prism = true;
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', 1);
            }
            echo $prism;
            exit(0);
        }

    } else if ($options['useamp'] == '1') {
        if (isset($options['redir']) && $options['redir'] == 1) {
            $ignore = 0;
            if (stripos($ua, 'Android') === false && stripos($ua, 'iPhone') === false && stripos($ua, 'iPad') === false && stripos($ua, 'iPod') === false ) {
                $ignore = 1;
            } else {
                if ($options['ignore'] != '') {
                    $masks = explode("\n", str_replace("\r", "", $options['ignore']));
                    foreach ($masks as $mask) {
                        if (preg_match('/'. str_replace('\*', '.*', preg_quote($mask, '/')).'$/', $domain.$_SERVER['REQUEST_URI'])) {
                            $ignore = 1;
                            break;
                        }
                    }
                }
            }
            if (!$ignore && (
                   ((isset($options['posts']) && $options['posts'] == 1)  && is_singular('post'))  ||
                   ((isset($options['pages']) && $options['pages'] == 1) && is_page())
              )
           ) {
                if (isset($options['domain']) && isset($options['type']) && $options['domain'] != '' && $options['type'] == 'domain') {
                    wp_redirect('http://'.$options['domain'].$_SERVER['REQUEST_URI']);
                    exit;
                } elseif (isset($options['type']) && $options['type'] != 'domain') {
                    wp_redirect('http://'.$domain.'/amp'.$_SERVER['REQUEST_URI'].'">');
                    exit;
                }
            }
        }
    }
}

// TODO: refactoring required, move to AMP service
function clickio_add_amp_link()
{
    $amp_opt = Options::get("useamp");
    if (empty($amp_opt)) {
        return ;
    }

    $url = PrismManager::getAmpLink();
    if (!empty($url)) {
        printf('<link rel="amphtml" href="%s">', $url);
    }
}

/**
 *  Show error if get_id is requested
 *  and clickio_get_id was not executed
 *
 * @return void
 */
function getIdErrorHandler()
{
    $is_debug = Options::get('is_debug');
    if ($is_debug) {
        return ;
    }

    global $get_id_flag;
    $get_id_query = SafeAccess::fromArray($_REQUEST, 'get_id', 'string', '');
    $err = error_get_last();
    if (empty($err)) {
        return ;
    }
    $is_error = in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR]);

    if (!empty($get_id_query) && !$get_id_flag && !$is_error) {
        $w3total = IntegrationServiceFactory::getService('w3total');
        $w3total::disable();
        ob_clean();
        $struct = ["wp_type" => "alien"];
        echo wp_json_encode($struct, JSON_PRETTY_PRINT);
    }
}
