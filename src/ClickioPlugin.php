<?php

/**
 * Plugin
 */

namespace Clickio;

use Clickio as org;
use Clickio\Addons\AddonManager;
use Clickio\Authorization\TokenAuthorizationManager;
use Clickio\Cron as cron;
use Clickio\CacheControl as cache;
use Clickio\Db\DatabaseManager;
use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Logger\Interfaces\ILogger;
use Clickio\Logger\Logger;
use Clickio\Prism\Cache\CacheManager;
use Clickio\Prism\Cache\CacheRepo;
use Clickio\Prism\PrismFeed;
use Clickio\Prism\Structures\DataStruct;
use Clickio\Prism\Utils\Proxy;
use Clickio\Prism\Utils\StatWarmup;
use Clickio\RestApi\RestApiFactory;
use Clickio\Tasks\TaskManager;
use Clickio\Utils\AcmeChallenge;
use Clickio\Utils\CacheUtils;
use Clickio\Utils\FileSystem;
use Clickio\Utils\LocationType;
use Clickio\Utils\Permalink;
use Clickio\Utils\QueryMonitor;
use Clickio\Utils\SafeAccess;
use Clickio\Utils\Shortcodes;
use Clickio\Utils\WebSub;
use Clickio\Utils\Widgets;

/**
 * Plugin entry point
 *
 * @package Clickio
 */
class ClickioPlugin
{
    /**
     * Logger instance
     *
     * @var ILogger
     */
    protected $log = null;

    /**
     * Cache manager instance
     *
     * @var cache\Interfaces\ICacheManager
     */
    protected $cache = null;

    /**
     * Cron manager
     *
     * @var cron\Interfaces\ICronManager
     */
    protected $cron = null;

    /**
     * Cookie name
     *
     * @var string
     */
    protected $preview_key = "cl_debug";

    /**
     * Singletone instance
     *
     * @var ClickioPlugin
     */
    private static $_inst = null;

    /**
     * Post meta key.
     * amphtml error
     *
     * @var string
     */
    const CLICKHOUSE_ERROR_KEY = "clickio_amphtml_error";

    /**
     * Constructor
     *
     * @param ILogger $log Logger instance
     */
    public function __construct(ILogger $log)
    {
        $this->log = $log;
        add_action('activated_plugin', [$this, 'afterActivation']);
        add_action('upgrader_process_complete', [$this, 'afterUpgrade'], 10, 2);
        if (defined('CLICKIO_PLUGIN_NAME')) {
            \register_activation_hook(CLICKIO_PLUGIN_NAME, [$this, 'onActivate']);
            \register_deactivation_hook(CLICKIO_PLUGIN_NAME, [$this, 'onDeactivate']);
        }
    }

    /**
     * Activation hook
     * Fires when plugin activated
     *
     * @return void
     */
    public function onActivate()
    {

        org\Options::loadOptions();
        org\Options::loadRemoteOptions();

        $auth = TokenAuthorizationManager::create();
        $app_key = $auth->generateAplicationKey();
        org\Options::setApplicationKey($app_key);
        org\Options::save();

        DatabaseManager::updateIfRequired();

        // rebuild rewrite routes
        Proxy::init();
        AcmeChallenge::setupRewrite();
        flush_rewrite_rules();
    }

    /**
     * Hook handler.
     * Fire after plugin updated
     *
     * @param mixed $cls upgrader instance
     * @param array $options upgrade options
     *
     * @return void
     */
    public function afterUpgrade($cls, $options)
    {
        $this->log->debug("Upgrade process complete", $options);
        if ($options['action'] === 'update' && $options['type'] === 'plugin' && isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {

                if ($plugin == CLICKIO_PLUGIN_NAME) {
                    $this->log->info("Plugin updated");
                    $this->onActivate();
                    break;
                }
            }
        }
    }

    /**
     * Plugin activation hook
     * Fire after plugin been activated
     *
     * @param string $plugin plugin name
     *
     * @return void
     */
    public function afterActivation($plugin)
    {
        if ($plugin != CLICKIO_PLUGIN_NAME) {
            return ;
        }

        $auth = TokenAuthorizationManager::create();
        $app_key = $auth->generateAplicationKey();
        org\Options::setApplicationKey($app_key);
    }

    /**
     * Deactivation hook
     * Fires when plugin deactivated
     *
     * @return void
     */
    public function onDeactivate()
    {
        $symlink = sprintf("%s/accelerator.php", WPMU_PLUGIN_DIR);
        if (is_link($symlink)) {
            @unlink($symlink);
        }

        CacheManager::uninstallAdvancedCache();
    }

    /**
     * Start listning events
     *
     * @return void
     */
    public function setupEventListners()
    {
        add_action('init', [$this, 'wpInit'], 25);
        add_action('admin_init', [$this, 'initAdmin']);
        add_action('admin_menu', [$this, 'initAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'staticFiles']);
        add_action('wp_enqueue_scripts', [$this, 'staticFiles']);
        add_action('admin_enqueue_scripts', [$this, 'adminStaticFiles']);
        add_action("shutdown", [CacheManager::class, 'saveBuffer']);
        add_action('wp', [$this, 'onWpReady']);
        add_action('wp_footer', [$this, 'footerContent']);
        add_action('wp_loaded', [$this, 'onWpLoaded'], 1);

        // websyb start
        add_action('transition_post_status', [$this, 'sendPushMessage'], 10, 3);
        add_action('rss2_head', [WebSub::class, 'addRssHeadLinks']);
        add_action('atom_head', [WebSub::class, 'addAtomHeadLinks']);
        add_action('template_redirect', [WebSub::class, 'addHeadersLinks']);
        // websub end

        add_action('_clickio_getid_before_content', [$this, 'beforeGetidContent']);
        add_action('_clickio_getid_after_content', [$this, 'afterGetidContent']);

        $rest = RestApiFactory::createManager('default');
        add_action('rest_api_init', [$rest, "registerRestRoutes"]);
        add_filter("plugin_action_links_".CLICKIO_PLUGIN_NAME, [$this, 'addPluginLinks']);

        if (!LocationType::isGetId() && Options::get('log_widgets') == 1) {
            add_filter("dynamic_sidebar", [Widgets::class, "lookupPageWidgets"]);
        }

        add_filter("get_pagenum_link", [$this, "omitLinksJunk"]);
        add_filter("clean_url", [$this, "replaceGetIdOnClean"]);

        $ampforwp = IntegrationServiceFactory::getService("ampforwp");
        $ampforwp::removeGetIdFromLinks();

        $qadsens = IntegrationServiceFactory::getService("qadsens");
        $qadsens::registerSaveOptionsHandler();

        $discuz = IntegrationServiceFactory::getService('wpdiscuz');
        $discuz::setupCacheCleaners();

        $websub = IntegrationServiceFactory::getService("websub");
        $websub::setupFilters();

        $amp = IntegrationServiceFactory::getService("amp");
        $amp::addListners();

        StatWarmup::setupEventListners();
        QueryMonitor::setupListners();
    }

    /**
     * Fires when global WP object is ready
     *
     * @return void
     */
    public function onWpReady()
    {
        $addons_manager = new AddonManager();
        $addons_manager->loadAddons();

        TaskManager::maybeRunTask();
    }

    /**
     * Hook 'init'
     *
     * @return void
     */
    public function wpInit()
    {
        Proxy::init();
        AcmeChallenge::setupRewrite();
        // $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        // $status = CacheUtils::getCacheStatus();
        // if ($status) {
        //     $repo = CacheRepo::getInstance();
        //     $cache = $repo->get($url);
        //     if (!empty($cache)) {
        //         $w3total = IntegrationServiceFactory::getService('w3total');
        //         $w3total::disable();

        //         CacheUtils::setCacheStatusHeader(true);
        //         $this->log->debug("Page from cache", ["url" => $url]);
        //         echo $cache;
        //         exit(0);
        //     }
        // }

        // $integration = Options::get('integration_scheme');

        // if ($integration == 'cms') {
        //     $debug = CacheUtils::getCacheStatusArray();
        //     $this->log->debug("Cache permissions", ["url" => $url, "permissions" => $debug]);
        // }
    }

    /**
     * Fires on wp_loaded
     *
     * @return void
     */
    public function onWpLoaded()
    {

    }

    /**
     * Add custom buttons to plugins page
     *
     * @param array $links links to add to plugin row
     *
     * @return array
     */
    public function addPluginLinks(array $links)
    {
        $url = esc_url(
            add_query_arg(
                'page',
                org\Options::OPT_KEY,
                get_admin_url() . 'admin.php'
            )
        );
        $link = sprintf('<a href="%s">%s</a>', $url, "Settings");
        $links['settings'] = $link;
        return $links;
    }

    /**
     * Init plugin
     *
     * @return void
     */
    // public function initPlugin()
    // {
    //     global $wp_post_types;
    //     $customtypes = org\Options::get('customtypes', []);
    //     $post_type_names = explode(',', $customtypes);
    //     foreach ($post_type_names as $post_type_name) {
    //         if (isset($wp_post_types[$post_type_name])) {
    //             $wp_post_types[$post_type_name]->show_in_rest = true;
    //             // Optionally customize the rest_base or controller class
    //             $wp_post_types[$post_type_name]->rest_base = $post_type_name;
    //             $wp_post_types[$post_type_name]->rest_controller_class = 'WP_REST_Posts_Controller';
    //         }
    //     }
    // }

    /**
     * Fires as an admin screen or script is being initialized.
     * Deactivate self and then print admin notice
     *
     * @return void
     */
    public function initAdmin()
    {
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(CLICKIO_PLUGIN_NAME);
            add_action('admin_notices', [$this, 'requireWpVersionNotice']);
            return;
        }

        register_setting(
            org\Options::OPT_KEY,
            org\Options::OPT_KEY,
            [
                "sanitize_callback" => [org\Options::class, 'validateOptions']
            ]
        );

        $cache_opt = Options::get('cache');
        if (!empty($cache_opt) && !CacheUtils::hasFreeSpace()) {
            $txt = "<b>Clickio Prism</b>: Caching is disabled. Reason: Not enough disk space.";
            Notification::warning($txt);
        }

        $cache_opt = Options::get('cache');
        $new_ver = CacheManager::ADV_CACHE_VER;
        $old_ver = defined('CLICKIO_ADV_CACHE')? CLICKIO_ADV_CACHE : $new_ver;

        if ($cache_opt && (!CacheManager::isAdvCacheInstaled() || version_compare($new_ver, $old_ver) == 1)) {
            CacheManager::setupAdvancedCache();
        } else if (empty($cache_opt) && CacheManager::isAdvCacheInstaled()) {
            CacheManager::uninstallAdvancedCache();
        }
    }

    /**
     * Fires before the administration menu loads in the admin.
     *
     * @return void
     */
    public function initAdminMenu()
    {
        add_options_page(
            'Clickio',
            'Clickio',
            'manage_options',
            org\Options::OPT_KEY,
            [$this, 'displaySettingsPage']
        );
    }

    /**
     * Admin notice
     * Prints incompatible WP version
     *
     * @return void
     */
    public function requireWpVersionNotice()
    {
        echo "<div id='message' class='notice notice-error is-dismissible'>
            <p>".
                sprintf(
                    'Clickio Plugin requires WordPress 5.0 or greater. Please <a href="%1$s">upgrade WordPress</a>.',
                    esc_url(admin_url('update-core.php'))
                ).
            "</p>
        </div>";
    }

    /**
     * Singletone
     *
     * @param ?\ILogger $log Logger instance
     * @param ?\ICacheManager $cache_manager Cache manager instance
     *
     * @return self
     */
    public static function getInstance($log = null, $cache_manager = null)
    {
        if (empty(static::$_inst)) {

            if ($log) {
                $logger = $log;
            } else {
                $log_name = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
                $logger = Logger::getLogger($log_name);
            }

            if ($cache_manager) {
                $cache = $cache_manager;
            } else {
                $cache = new cache\CacheManager($logger);
            }

            static::$_inst = new static($logger);
            static::$_inst->setCacheControll($cache);
        }

        return static::$_inst;
    }

    /**
     * Add custom static to all pages
     *
     * @return array
     */
    public function staticFiles()
    {
        if (is_user_logged_in() && is_admin_bar_showing()) {
            $url_css = plugins_url("style.css", CLICKIO_PLUGIN_DIR.'/src/static/style.css'); //dirty hack
            wp_register_style('clickio_cdn', $url_css, false, filemtime(CLICKIO_PLUGIN_DIR.'/src/static/style.css'));
            wp_enqueue_style('clickio_cdn');
        }
    }

    /**
     * Add custom statics to admin pages
     *
     * @param string $prefix admin page identifier
     *
     * @return void
     */
    public function adminStaticFiles($prefix)
    {
        // load options js only on own settings page

        if ($prefix == 'settings_page_clickio_opt') {
            $url_js = plugins_url("options.js", CLICKIO_PLUGIN_DIR.'/src/static/options.js'); //dirty hack

            wp_register_script('clickio_cdn', $url_js, false, CLICKIO_PRISM_VERSION, true);
            wp_enqueue_script('clickio_cdn');
        }
    }

    /**
     * Toggle preview mode
     *
     * @return int
     */
    public function getPreviewMode(): int
    {
        if (!empty($_COOKIE[$this->preview_key])) {
            return (int) $_COOKIE[$this->preview_key];
        } else if (!empty($_REQUEST[$this->preview_key])) {
            return (int) $_REQUEST[$this->preview_key];
        } else {
            return 0;
        }
    }

    /**
     * Getter.
     * Get logger
     *
     * @return ILogger
     */
    public function getLogger(): ILogger
    {
        return $this->log;
    }

    /**
     * Getter.
     * Get cache manager
     *
     * @return array
     */
    public function getCache(): cache\Interfaces\ICacheManager
    {
        return $this->cache;
    }

    /**
     * Setter.
     * Set cache manager
     *
     * @param cache\Interfaces\ICacheManager $cache some cache manager
     *
     * @return void
     */
    public function setCacheControll(cache\Interfaces\ICacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Display settings page
     *
     * @return void
     */
    public function displaySettingsPage()
    {
        include CLICKIO_PLUGIN_DIR."/clickio_settings.php";
    }

    /**
     * Authentication
     *
     * @param string $key appkey
     *
     * @return bool
     */
    public function authenticate(string $key): bool
    {
        $stored_key = Options::getApplicationKey();
        return $stored_key == $key;
    }

    /**
     * Get url to wp rest api
     *
     * @return string
     */
    public static function getRestUrl(): string
    {
        $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        return sprintf('https://%s/wp-json/wp/v2', $domain);
    }

    /**
     * Disable pagination
     *
     * @param mixed $pages list of pages
     *
     * @return array
     */
    public function disablePagination($pages)
    {
        $disable = Options::get('disable_pages', 0);
        if (!empty($disable)) {
            $full_page = implode('', $pages);
            $pages = [$full_page];
        }
        return $pages;
    }

    /**
     * Remove unnecessary query parameter from links
     *
     * @param string $link target link
     *
     * @return string
     */
    public function omitLinksJunk($link)
    {
        $url = parse_url($link);
        $query = SafeAccess::fromArray($url, 'query', 'string', '');
        if (array_key_exists('query', $url)) {
            $query = explode('&', $url['query']);
            $query = array_filter(
                $query,
                function ($val) {
                    $get_id = strpos($val, 'get_id') === false;
                    $anticache = strpos($val, 'anticache') === false;
                    $extra = strpos($val, 'get_extra_content') === false;
                    if ($get_id && $anticache && $extra) {
                        return $val;
                    }
                }
            );
            $query = implode('&', $query);
        }
        if (!empty($query)) {
            $query = "?".$query;
        }
        $path = SafeAccess::fromArray($url, 'path', 'string', '/');
        return sprintf("%s://%s%s%s", $url['scheme'], $url['host'], $path, $query);
    }

    // TODO: refactoring required
    public function replaceGetIdOnClean($link)
    {
        if (!preg_match("/[\?\&]get_id/", $link)) {
            return $link;
        }

        $link_parts = explode('?', $link);
        if (count($link_parts) != 2) {
            return $link;
        }

        $pairs = [];
        foreach (explode('&', $link_parts[1]) as $pair) {
            list($name, $val) = explode('=', $pair);
            $name = trim($name);
            $val = empty($val)? '' : trim($val);

            if (in_array($name, ['get_id', 'anticache', 'get_extra_content'])) {
                continue ;
            }
            $pairs[$name] = $val;
        }

        return sprintf("%s?%s", $link_parts[0], http_build_query($pairs));
    }

    /**
     * Redirect to canonical url to avoid blank pages in Prism
     *
     * @param bool $do_redirect when true it will break plugin execution
     *                          and returns 301 response code
     *
     * @return void|string
     */
    public function canonicalRedirect(bool $do_redirect = true)
    {
        if (LocationType::is404()) {
            return ;
        }

        $requested_url = Permalink::getFullCurrentUrl();
        $alt_url = Permalink::getTralingSlashUrl($requested_url);
        $traling_slash_opt = Options::get('uri_strip_last_slash');


        if (function_exists('redirect_canonical')) {
            $redirect_to = redirect_canonical($requested_url, false);
            if (empty($traling_slash_opt) && !empty($redirect_to) && $redirect_to == $alt_url) {

                $w3total = IntegrationServiceFactory::getService('w3total');
                $w3total::disable();

                $struct = new DataStruct();
                $struct->wp_type = 'alien';
                print wp_json_encode($struct->toArray(), JSON_PRETTY_PRINT);
                exit;
            }

            if (!empty($redirect_to) && !in_array($redirect_to, [$requested_url, $alt_url])) {
                $this->log->debug("Canonical redirect", ["request_url" => $requested_url, "redirect_url" => $redirect_to]);
                return redirect_canonical($requested_url, $do_redirect);
            }
        }
    }

    /**
     * Plugin User-Agent
     *
     * @return string
     */
    public static function getPluginUA(): string
    {
        global $wp_version;
        $wp = $wp_version;
        $host = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $plugin = CLICKIO_PRISM_VERSION;

        $ua_pattern = "Clickio-Prism/%s (WordPress/%s; %s)";
        return sprintf($ua_pattern, $plugin, $wp, $host);
    }

    /**
     * Action before content
     *
     * @return void
     */
    public function beforeGetidContent()
    {
        Shortcodes::setRemoverStatus(true);
    }

    /**
     * Action after content
     *
     * @return void
     */
    public function afterGetidContent()
    {
        Shortcodes::setRemoverStatus(false);
    }

    /**
     * Execute on post status chage
     *
     * @param string $new_status next status
     * @param string $old_status prev status
     * @param WP_Post $post_obj changed post
     *
     * @return void
     */
    public function sendPushMessage($new_status, $old_status, $post_obj)
    {
        if ($new_status === 'publish' && $old_status !== $new_status) {
            WebSub::publish();
        }
    }

    /**
     * Handler for wp_footer action
     *
     * @return mixed
     */
    public function footerContent()
    {
        $feed_debug_param = SafeAccess::fromArray($_REQUEST, 'cl_prism_feed', 'mixed', 0);
        if (!empty($feed_debug_param)) {
            echo PrismFeed::getInitScript();
        }

        $compatibility_test = SafeAccess::fromArray($_REQUEST, 'cl_cmpt_test', 'mixed', 0);
        if (!empty($compatibility_test)) {
            echo "<!-- cl-test-googleapis: https://fonts.googleapis.com/css2?family=Roboto:wght@100&display=swap -->";
            echo "<!-- cl-test-gstatic: https://fonts.gstatic.com/s/roboto/v29/KFOkCnqEu92Fr1MmgVxMIzIFKw.woff2 -->";
        }
    }

    /**
     * Adding permissive attributes
     *
     * @param string $content post content
     *
     * @return string
     */
    public function filterContent($content = "")
    {
        $styles = Options::get("allow_styles");
        if (!empty($styles)) {
            $replace = "<style data-allow-style";
            $content = str_replace("<style", $replace, $content);
        }
        return $content;
    }
}
