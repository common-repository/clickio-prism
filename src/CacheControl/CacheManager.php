<?php
/**
 * Cache manager
 */

namespace Clickio\CacheControl;

use Clickio as org;
use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Logger as log;
use Clickio\Meta\PostMeta;
use Clickio\Meta\TermMeta;
use Clickio\Prism\Cache\CacheFactory;
use Clickio\Prism\Cache\CacheRepo;
use Clickio\Prism\Cache\Engine\Internal;
use Clickio\Utils\SafeAccess;
use Exception;

/**
 * Cache manager
 *
 * @package CacheControl
 */
class CacheManager implements Interfaces\ICacheManager
{

    /**
     * List of URLs to be purged
     *
     * (default value: array())
     *
     * @var array
     * @access protected
     */
    protected $purge_urls = [];

    /**
     * Services pool
     *
     * @var array
     */
    protected $services = [];

    /**
     * Logger instance
     *
     * @var ILogger
     */
    protected $log = null;

    /**
     * Constructor
     *
     * @param log\Interfaces\ILogger $logger Logger instance
     */
    public function __construct(log\Interfaces\ILogger $logger)
    {
        $this->log = $logger;

        // only cleaners list, can be available always
        add_action('admin_init', [ $this, 'initAdmin']);

        add_action('init', [ $this, 'init']);
        add_action('admin_bar_menu', [$this, 'initAdminBar'], 100);
        add_action('import_start', [$this, 'import_start']);
        add_action('import_end', [$this, 'import_end']);

        $lx_force_nocache = SafeAccess::fromArray($_REQUEST, 'lx_force_nocache', 'string', '');
        $cache_opt = org\Options::get('cache');
        if ($cache_opt && !empty($lx_force_nocache)) {
            add_action('plugins_loaded', [$this, 'forcePurgeCache']);
        }

        // Check if there's an upgrade
        add_action('upgrader_process_complete', [$this, 'checkUpgrades'], 10, 2);

        if (array_key_exists('w3tc_flush_minify', $_GET)) {
            $this->execute_purge_no_id();
        }
    }

    /**
     * Getter.
     * Returns all cache services
     *
     * @return array
     */
    public function getAllCacheServices(): array
    {
        return CacheServiceFactory::createAll();
    }

    /**
     * Fires in admin page
     *
     * @return void
     */
    public function initAdmin()
    {
        $this->services = $this->getAllCacheServices();
    }

    /**
     * Init cache manager
     *
     * @return void
     */
    public function init()
    {
        global $wp_db_version;

        if (file_exists(WP_CONTENT_DIR . '/object-cache.php') && (int)get_option('db_version') !== $wp_db_version) {
            wp_cache_flush();
        }

        $wp_rocket = IntegrationServiceFactory::getService('wp_rocket');
        $wp_rocket::setUpCacheCleaners();

        $sp_cache = IntegrationServiceFactory::getService('super_cache');
        $sp_cache::setUpCacheCleaners();

        $wpdiscuz = IntegrationServiceFactory::getService('wpdiscuz');
        $wpdiscuz::setupCacheCleaners();

        $events = $this->getRegisterEvents();
        $no_id_events = $this->getNoIdEvents();
        $tax_events = $this->getTaxonomyEvents();

        if (!empty($events) && !empty($no_id_events)) {

            $events = (array) $events;
            $no_id_events = (array) $no_id_events;

            foreach ($events as $event) {
                if (in_array($event, $no_id_events, true)) {
                    add_action($event, [$this, 'execute_purge_no_id'], 1);
                } elseif (in_array($event, $tax_events, true)) {
                    add_action($event, [$this, 'purgeTaxonomy'], 10, 2);
                } else {
                    add_action($event, [$this, 'purge_post'], 10, 2);
                }
            }
            add_action('post_updated', [$this, 'purgeOldPostSlug'], 10, 3);
        }
        add_action('shutdown', [$this, 'execute_purge']);
        if ((isset( $_GET['vhp_flush_all']) && check_admin_referer('vhp-flush-all'))
            || (isset( $_GET['vhp_flush_do'] ) && check_admin_referer('vhp-flush-do'))
        ) {
            add_action('admin_notices', [$this, 'adminMessagePurge']);
        }
    }

    /**
     * Clear object cache when plugin updated
     *
     * @param WP_Upgrader $object WP_Upgrader instance
     * @param array $options array of bulk item update data
     *
     * @return void
     */
    public function checkUpgrades($object, $options)
    {
        if (file_exists(WP_CONTENT_DIR.'/object-cache.php')) {
            wp_cache_flush();
        }
    }

    /**
     * Show admin message when purge all
     *
     * @return void
     */
    public function adminMessagePurge()
    {
        echo '<div id="message" class="notice notice-success fade is-dismissible">
                <p>
                    <strong>' . esc_html__('Cache emptied!', 'clickioamp') . '</strong>
                </p>
            </div>';
    }

    /**
     * Execute filter on home_url
     *
     * @return string
     */
    public static function theHomeUrl()
    {
        $home_url = apply_filters('vhp_home_url', home_url());
        return $home_url;
    }

    /**
     * Add custom btn to admin bar
     *
     * @param WP_Admin_Bar $admin_bar admin bar instance
     *
     * @return void
     */
    public function initAdminBar($admin_bar)
    {
        global $wp;
        $can_purge    = false;
        $cache_titled = 'Clickio CDN Cache';

        if ((!is_admin()
            && get_post() !== false
            && current_user_can('edit_published_posts'))
            || current_user_can('activate_plugins')
        ) {
            // Main Array.
            $title='<span class="ab-icon" style="background-image: url('.self::getIconSvg().') !important;">
                    </span>
                    <span class="ab-label">' . $cache_titled . '</span>';
            $args      = array(
                array(
                    'id'    => 'purge-cache',
                    'title' => $title,
                    'meta'  => array(
                        'class' => 'clickioamp',
                    ),
                ),
            );
            $can_purge = true;
        }

        // Checking user permissions for who can and cannot use the all flush.
        if ((!is_multisite() && current_user_can('activate_plugins'))
            || current_user_can('manage_network')
            || (is_multisite()
            && current_user_can('activate_plugins')
            && (SUBDOMAIN_INSTALL || (!SUBDOMAIN_INSTALL && (\BLOG_ID_CURRENT_SITE !== $blog_id))))
            ) {

            $args[] = array(
                'parent' => 'purge-cache',
                'id'     => 'purge-cache-all',
                'title'  => __('Purge cache (all pages)', 'clickioamp'),
                'href'   => wp_nonce_url(add_query_arg('vhp_flush_do', 'all'), 'vhp-flush-do'),
                'meta'   => array(
                    'title' => __('Purge cache (all pages)', 'clickioamp'),
                ),
            );

            // If a memcached file is found, we can do this too.
            // if (file_exists(\WP_CONTENT_DIR . '/object-cache.php')) {
            //     $args[] = array(
            //         'parent' => 'purge-cache',
            //         'id'     => 'purge-cache-db',
            //         'title'  => __('Purge database cache', 'clickioamp'),
            //         'href'   => wp_nonce_url(add_query_arg('vhp_flush_do', 'object'), 'vhp-flush-do'),
            //         'meta'   => array(
            //             'title' => __('Purge database cache', 'clickioamp'),
            //         ),
            //     );
            // }

            // If we're on a front end page and the current user can edit published posts, then they can do this.
            if (!is_admin() && get_post() !== false && current_user_can('edit_published_posts')) {
                $page_url = esc_url(home_url($wp->request));
                $args[]   = array(
                    'parent' => 'purge-cache',
                    'id'     => 'purge-cache-this',
                    'title'  => __('Purge сache (this page)', 'clickioamp'),
                    'href'   => wp_nonce_url(add_query_arg('vhp_flush_do', $page_url . '/'), 'vhp-flush-do'),
                    'meta'   => array(
                        'title' => __('Purge сache (this page)', 'clickioamp'),
                    ),
                );
            }
        }

        if ($can_purge) {
            foreach ($args as $arg) {
                $admin_bar->add_node($arg);
            }
        }
    }

    /**
     * Get html icon purge all cache button
     *
     * @param bool $base64 encode icon into basse64
     * @param bool $icon_color admin colors for icon
     *
     * @return string
     */
    public static function getIconSvg($base64 = true, $icon_color = false)
    {
        global $_wp_admin_css_colors;

        $fill = (false !== $icon_color) ? sanitize_hex_color($icon_color) : '#82878c';

        if (is_admin() && false === $icon_color) {
            $admin_colors  = json_decode(wp_json_encode($_wp_admin_css_colors), true);
            $current_color = get_user_option('admin_color');
            $default = ["icon_colors" => ["base" => "#a7aaad"]];
            $_cur_color_scheme = SafeAccess::fromArray($admin_colors, $current_color, 'array', $default);
            $fill = $_cur_color_scheme['icon_colors']['base'];
        }

        // Flat
        //@codingStandardsIgnoreLine
        $svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="100%" height="100%" style="fill:' . $fill . '" viewBox="0 0 36.2 34.39" role="img" aria-hidden="true" focusable="false"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path fill="' . $fill . '" d="M24.41,0H4L0,18.39H12.16v2a2,2,0,0,0,4.08,0v-2H24.1a8.8,8.8,0,0,1,4.09-1Z"/><path fill="' . $fill . '" d="M21.5,20.4H18.24a4,4,0,0,1-8.08,0v0H.2v8.68H19.61a9.15,9.15,0,0,1-.41-2.68A9,9,0,0,1,21.5,20.4Z"/><path fill="' . $fill . '" d="M28.7,33.85a7,7,0,1,1,7-7A7,7,0,0,1,28.7,33.85Zm-1.61-5.36h5V25.28H30.31v-3H27.09Z"/><path fill="' . $fill . '" d="M28.7,20.46a6.43,6.43,0,1,1-6.43,6.43,6.43,6.43,0,0,1,6.43-6.43M26.56,29h6.09V24.74H30.84V21.8H26.56V29m2.14-9.64a7.5,7.5,0,1,0,7.5,7.5,7.51,7.51,0,0,0-7.5-7.5ZM27.63,28V22.87h2.14v2.95h1.81V28Z"/></g></g></svg>';

        if ($base64) {
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        return $svg;
    }

    /**
     * Events when purging is required
     *
     * @return array
     */
    protected function getRegisterEvents(): array
    {

        // Define registered purge events.
        $theme = get_option('stylesheet');
        $actions = [
            'wp_ajax_edit-theme-plugin-file',
            'autoptimize_action_cachepurged', // Compat with https://wordpress.org/plugins/autoptimize/ plugin.
            'delete_attachment',              // Delete an attachment - includes re-uploading.
            'before_delete_post',             // Delete a post.
            'wp_trash_post',
            'edit_post',                      // Edit a post - includes leaving comments.
            'import_start',                   // When importer starts
            'import_end',                     // When importer ends
            'save_post',                      // Save a post.
            'update_option_theme_mods_'.$theme,
            'switch_theme',                   // After a theme is changed.
            'trashed_post',                   // Empty Trashed post.
            'edited_terms',
            "w3tc_cdn_purge_all",
            "w3tc_flush_post",
            "w3tc_flush_all",
            "w3tc_flush_url",
            "wp_update_nav_menu",              // After a menus changed
            "customize_save_after"
        ];

        return $actions;
    }

    /**
     * List of events where no post id
     *
     * @return array
     */
    protected function getNoIdEvents(): array
    {
        $theme = get_option('stylesheet');
        $actions = [
            'wp_ajax_edit-theme-plugin-file', // When theme/plugin editor save
            'autoptimize_action_cachepurged', // Compat with https://wordpress.org/plugins/autoptimize/ plugin.
            'import_start',                   // When importer starts
            'import_end',                     // When importer ends
            'switch_theme',                   // After a theme is changed.
            'update_option_theme_mods_'.$theme, // After theme option changes
            'w3tc_cdn_purge_all',
            'w3tc_flush_all',
            'wp_update_nav_menu',              // After a menus changed
            'customize_save_after'
        ];

        return $actions;
    }

    /**
     * Events requiring clearing canonical caches
     *
     * @return array
     */
    protected function getPurgeCanonicalEvents(): array
    {
        $actions = [
            'wp_update_nav_menu',
            'wp_ajax_edit-theme-plugin-file',
            'customize_save_after'
        ];
        return $actions;
    }

    /**
     * List of taxonomy events
     *
     * @return array
     */
    protected function getTaxonomyEvents(): array
    {
        $events = [
            "edited_terms"
        ];
        return $events;
    }

    /**
     * Start
     */
    public function execute_purge()
    {
        $purge_urls = array_unique($this->purge_urls);
        if (empty($purge_urls) && isset($_GET)) {
            if (isset($_GET['vhp_flush_all']) && check_admin_referer('vhp-flush-all')) {
                // Flush Cache recursize.
                $this->clearCache([$this->theHomeUrl() . '/?purge_all']);
            } elseif (isset( $_GET['vhp_flush_do']) && check_admin_referer('vhp-flush-do')) {
                $this->debug("Cache clearing requested manually");
                if ('object' === $_GET['vhp_flush_do']) {
                    // Flush Object Cache (with a double check).
                    if (file_exists(WP_CONTENT_DIR . '/object-cache.php')) {
                        wp_cache_flush();
                    }
                } elseif ('all' === $_GET['vhp_flush_do']) {
                    // Flush Cache recursize.
                    $this->clearCache([$this->theHomeUrl() . '/?purge_all']);
                } else {
                    // Flush the URL we're on.
                    $parsed_url = wp_parse_url(esc_url_raw(wp_unslash($_GET['vhp_flush_do'])));
                    if (!isset($parsed_url['host'])) {
                        return;
                    }
                    $this->clearCache([esc_url_raw(wp_unslash($_GET['vhp_flush_do']))]);
                }
            }
        } else {
            $this->clearCache($purge_urls);
        }
    }

    /**
     * Start cache cleaners
     *
     * @param array $urllist list of urls to be purged
     *
     * @return array
     */
    public function clearCache($urllist)
    {
        $cleaners = org\Options::get('cleaners', ['ClickIoCDN']);
        if (!in_array('ClickIoCDN', $cleaners)) {
            $cleaners[] = 'ClickIoCDN';
        }

        $integration = org\Options::get("integration_scheme");
        if (!in_array("Plugin", $cleaners) && $integration == 'cms') {
            $cleaners[] = 'Plugin';
        }

        $this->startCleaners($cleaners, $urllist);
    }

    /**
     * Start cache cleaners
     *
     * @param array $cleaners list of names
     * @param array $urls list of urls to be purged
     *
     * @return void
     */
    public function startCleaners(array $cleaners, array $urls)
    {
        // ignore all urls when purge_all
        foreach ($urls as $url) {
            $parsed_url = wp_parse_url($url);
            $query = SafeAccess::fromArray($parsed_url, 'query', 'string', '');
            $parsed_query = [];
            parse_str($query, $parsed_query);
            if (in_array('purge_all', array_keys($parsed_query))) {
                $urls = [$url];
                break;
            }
        }

        $this->debug(
            "Starting cleaners",
            [
                "cleaners" => $cleaners,
                "urls" => $urls
            ]
        );

        foreach ($cleaners as $serv_name) {
            try{
                $serv = CacheServiceFactory::create($serv_name);
            } catch(\Exception $err) {
                $this->debug($err->getMessage(), $err->getTrace());
                continue ;
            }
            $serv->clear($urls);
        }
    }

    public function execute_purge_no_id()
    {
        $hook = current_filter();
        $this->debug("Purge cache triggered.", ["event" => $hook, "target" => "purge_all"]);
        $listofurls = [];

        $purge_url = $this->theHomeUrl() . '/?purge_all';
        $canonical_events = $this->getPurgeCanonicalEvents();

        if (in_array($hook, $canonical_events)) {
            $purge_url .= "&purge_canonical";
        }

        array_push($listofurls, $purge_url);
        foreach ($listofurls as $url) {
            array_push($this->purge_urls, $url);
        }
    }

    public function purge_post($post_id)
    {
        $hook = current_filter();
        $this->debug("Purge cache triggered.", ["event" => $hook, "target" => "purge_single"]);

        $valid_post_status = array( 'publish', 'private', 'trash' );
        $this_post_status  = get_post_status($post_id);

        // Not all post types are created equal.
        $invalid_post_type   = array( 'nav_menu_item', 'revision' );
        $noarchive_post_type = array( 'post', 'page' );
        $this_post_type      = get_post_type($post_id);

        if (version_compare(get_bloginfo('version'), '4.7', '>=')) {
            $rest_api_route = 'wp/v2';
        }

        // array to collect all our URLs.
        $listofurls = array();
        // Verify we have a permalink and that we're a valid post status and a not an invalid post type.
        if (false !== get_permalink($post_id)
            && in_array($this_post_status, $valid_post_status, true)
            && ! in_array($this_post_type, $invalid_post_type, true)
        ) {

            // Post URL.
            $permalink = get_permalink($post_id);
            if (in_array($hook, ["before_delete_post", "wp_trash_post"])) {
                $permalink .= "?deleted";
            }

            array_push($listofurls, $permalink);
            // if (isset($rest_api_route)) {
            //     $post_type_object = get_post_type_object($post_id);
            //     $rest_permalink   = false;
            //     if (isset( $post_type_object->rest_base)) {
            //         $rest_permalink = get_rest_url() . $rest_api_route . '/' . $post_type_object->rest_base . '/' . $post_id . '/';
            //     } elseif ('post' === $this_post_type) {
            //         $rest_permalink = get_rest_url() . $rest_api_route . '/posts/' . $post_id . '/';
            //     } elseif ('page' === $this_post_type) {
            //         $rest_permalink = get_rest_url() . $rest_api_route . '/pages/' . $post_id . '/';
            //     }
            // }

            // if ( $rest_permalink ) {
            //     array_push($listofurls, $rest_permalink);
            // }

            // Add in AMP permalink for offical WP AMP plugin:
            // https://wordpress.org/plugins/amp/
            if (function_exists('amp_get_permalink')) {
                array_push($listofurls, amp_get_permalink($post_id));
            }

            // Regular AMP url for posts if ant of the following are active:
            // https://wordpress.org/plugins/accelerated-mobile-pages/
            if (defined('AMPFORWP_AMP_QUERY_VAR')) {
                array_push($listofurls, get_permalink($post_id) . 'amp/');
            }

            // Also clean URL for trashed post.
            if ('trash' === $this_post_status) {
                $trashpost = get_permalink($post_id);
                $trashpost = str_replace('__trashed', '', $trashpost);
                array_push($listofurls, $trashpost, $trashpost . 'feed/');
            }

            $categories = get_the_category($post_id);
            if ($categories) {
                foreach ($categories as $cat) {
                    $category = get_category_link($cat->term_id);
                    $cat_link = str_replace("/./", "/", $category);
                    array_push(
                        $listofurls,
                        $cat_link
                        // get_rest_url() . $rest_api_route . '/categories/' . $cat->term_id . '/'
                    );
                }
            }

            // Tag purge based on Donnacha's work in WP Super Cache.
            $tags = get_the_tags($post_id);
            if ($tags) {
                $tag_base = get_site_option('tag_base');
                if ('' === $tag_base) {
                    $tag_base = '/tag/';
                }
                foreach ($tags as $tag) {
                    array_push(
                        $listofurls,
                        get_tag_link($tag->term_id)
                        // get_rest_url() . $rest_api_route . $tag_base . $tag->term_id . '/'
                    );
                }
            }
            // Custom Taxonomies: Only show if the taxonomy is public.
            $taxonomies = get_post_taxonomies($post_id);
            if ($taxonomies) {
                foreach ($taxonomies as $taxonomy) {
                    $features = (array) get_taxonomy($taxonomy);
                    if ($features['public']) {
                        $terms = wp_get_post_terms($post_id, $taxonomy);
                        foreach ($terms as $term) {
                            array_push(
                                $listofurls,
                                get_term_link($term)
                                // get_rest_url() . $rest_api_route . '/' . $term->taxonomy . '/' . $term->slug . '/'
                            );
                        }
                    }
                }
            }

            // If the post is a post, we have more things to flush
            // Pages and Woo Things don't need all this.
            if ($this_post_type && 'post' === $this_post_type) {
                // Author URLs:
                $author_id = get_post_field('post_author', $post_id);
                array_push(
                    $listofurls,
                    get_author_posts_url($author_id),
                    get_author_feed_link($author_id)
                    // get_rest_url() . $rest_api_route . '/users/' . $author_id . '/'
                );

                // Feeds:
                array_push(
                    $listofurls,
                    get_bloginfo_rss('rdf_url'),
                    get_bloginfo_rss('rss_url'),
                    get_bloginfo_rss('rss2_url'),
                    get_bloginfo_rss('atom_url'),
                    get_bloginfo_rss('comments_rss2_url'),
                    get_post_comments_feed_link($post_id)
                );
            }

            // Archives and their feeds.
            if ($this_post_type && !in_array($this_post_type, $noarchive_post_type, true)) {
                array_push(
                    $listofurls,
                    get_post_type_archive_link(get_post_type($post_id)),
                    get_post_type_archive_feed_link(get_post_type($post_id))
                    // Need to add in JSON?
                );
            }

            // Home Pages and (if used) posts page.
            array_push(
                $listofurls,
                // get_rest_url(),
                $this->theHomeUrl() . '/'
            );
            if ('page' === get_site_option('show_on_front')) {
                // Ensure we have a page_for_posts setting to avoid empty URL.
                if (get_site_option('page_for_posts')) {
                    array_push($listofurls, get_permalink(get_site_option('page_for_posts')));
                }
            }
        } else {
            // We're not sure how we got here, but bail instead of processing anything else.
            return;
        }

        // If the array isn't empty, proceed.
        if (!empty($listofurls)) {
            // Strip off query variables
            foreach ($listofurls as $url) {
                $url = strtok($url, '?');
            }

            // Make sure each URL only gets purged once, eh?
            $purgeurls = array_unique($listofurls, SORT_REGULAR);

            // Flush all the URLs
            foreach ( $purgeurls as $url ) {
                array_push($this->purge_urls, $url);
            }
        }

        /*
         * Filter to add or remove urls to the array of purged urls
         * @param array $purge_urls the urls (paths) to be purged
         * @param int $post_id the id of the new/edited post
         */
        $this->purge_urls = apply_filters('vhp_purge_urls', $this->purge_urls, $post_id);
    }

    /**
     * Generate urls for posts in taxonomy
     *
     * @param int $term_id taxonomy term id
     * @param string $taxonomy taxonomy
     *
     * @return void
     */
    public function purgeTaxonomy($term_id, $taxonomy)
    {
        $hook = current_filter();
        $this->debug("Purge cache triggered.", ["event" => $hook, "target" => "purge_taxonomy"]);
        $posts = get_posts(
            [
                "tax_query" => [
                    [
                        "taxonomy" => $taxonomy,
                        "terms" => [$term_id]
                    ]
                ]
            ]
        );

        if (empty($posts)) {
            $posts = [];
        }

        try {
            $term = TermMeta::createFromId($term_id);
        } catch (Exception $err) {
            $msg_pattern = "Hook: %s; Term id: %s; Taxonomy: %s; Error: %s";
            $msg = sprintf($msg_pattern, $hook, $term_id, $taxonomy, $err->getMessage());
            $this->error($msg);
            return ;
        }
        $slug = $term->getTermSlug();
        foreach ($posts as $post) {
            $meta = new PostMeta($post);
            $url = $meta->getPermalink();
            if (strpos($url, $slug) !== false) {
                $this->purge_urls[] = $url;
            }
        }
        $this->purge_urls[] = $term->getPermalink();
    }

    /**
     * Debug log message
     * Wrapper around ILogger::debug
     *
     * @param string $msg log message
     * @param array $debugInfo additional debug info
     *
     * @return void
     */
    protected function debug($msg, $debugInfo = [])
    {
        $msg = sprintf("Cache Manager: %s\n", $msg);
        $this->log->debug($msg, $debugInfo);
    }

    /**
     * Info log message
     *
     * @param string $msg message
     *
     * @return void
     */
    protected function info(string $msg)
    {
        $msg = sprintf("Cache Manager: %s\n", $msg);
        $this->log->info($msg);
    }

    /**
     * Warning log message
     *
     * @param string $msg message
     *
     * @return void
     */
    protected function warning(string $msg)
    {
        $msg = sprintf("Cache Manager: %s\n", $msg);
        $this->log->warning($msg);
    }

    /**
     * Log error message
     *
     * @param string $msg message
     *
     * @return void
     */
    protected function error(string $msg)
    {
        $msg = sprintf("Cache Manager: %s\n", $msg);
        $this->log->error($msg);
    }

    /**
     * Getter
     * Full list of available services
     *
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Purge cache for old post slugwhen changed
     *
     * @param int $id post id
     * @param mixed $after new slug
     * @param mixed $before old slug
     *
     * @return void
     */
    public function purgeOldPostSlug($id, $after, $before)
    {
        if ($before->post_name != $after->post_name) {
            $old_link_tpl = get_permalink($id, true);
            $old_link = str_replace("%postname%", $before->post_name, $old_link_tpl);
            array_push($this->purge_urls, $old_link);
        }
    }

    /**
     * Manualy purge cache
     *
     * @param int $all purge all pages
     * @param array $pages list of urls to be purged
     * @param int $canonical purge canonical caches
     * @param int $internal purge only internal cache
     * @param int $cleaners start only selected cleaners
     *
     * @return void
     */
    public static function purge(int $all, array $pages, int $canonical = 0, int $internal = 0, array $cleaners = [])
    {
        if (!empty($internal)) {
            $engine = CacheFactory::make(Internal::class, []);
            $repo = new CacheRepo($engine);
            $repo->purgeAll();
        }

        if (!empty($all)) {
            $purge_canonical = "";
            if (!empty($canonical)) {
                $purge_canonical = "&purge_canonical";
            }
            $pages = [
                sprintf('%s/?purge_all%s', home_url(), $purge_canonical)
            ];
        }

        if (empty($pages)) {
            return ;
        }

        $target_cleaners = [];
        if (!empty($cleaners)) {
            foreach ($cleaners as $cleaner) {
                $target_cleaners[] = CacheServiceFactory::create($cleaner);
            }
        } else {
            $target_cleaners = CacheServiceFactory::createAll();
        }

        foreach ($target_cleaners as $_cleaner) {
            $_cleaner->clear($pages);
        }
    }

    /**
     * Add url purging
     *
     * @param string $url url to be purget
     *
     * @return void
     */
    public function addPurgeUrl(string $url)
    {
        $this->purge_urls[] = $url;
    }

    /**
     * Purge cache for current page if lx_nocache in url params
     *
     * @return void
     */
    public function forcePurgeCache()
    {
        $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        $parsed = wp_parse_url($url);
        $query = SafeAccess::fromArray($parsed, 'query', 'string', '');
        $path = SafeAccess::fromArray($parsed, 'path', 'string', '');
        $query_list = explode("&", $query);
        $filtered_list = array_filter(
            $query_list,
            function ($item) {
                if (!preg_match("/^lx_force_nocache/", $item)) {
                    return $item;
                }
            }
        );
        $filtered_query = implode("&", $filtered_list);
        if (empty($filtered_query)) {
            $url = $path;
        } else {
            $url = implode("?", [$path, $filtered_query]);
        }

        $this->addPurgeUrl($url);
    }
}
