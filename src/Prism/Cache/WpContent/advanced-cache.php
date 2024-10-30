<?php

/**
 * Clickio Prism advanced cache module
 */
//clickio_protect_replace_dropin();
if (defined('CLICKIO_ADV_CACHE')) {
    return ;
}

define('CLICKIO_ADV_CACHE', '1.2.1');

if (!defined('ABSPATH')) {
    die();
}

/**
 * Abort Clickio Prism Cache loading if WordPress is upgrading
 */
if (defined('WP_INSTALLING') && WP_INSTALLING) {
    return;
}

function clickio_fallback()
{
    $fallback = WP_CONTENT_DIR . '/fallback-advanced-cache.php';
    if (@file_exists($fallback) && @is_readable($fallback)) {
        include_once $fallback;
    }
}
$no_cache_urls = [
    "wp-json",
    "wp-admin",
    "wp-login",
    "login",
    "register",
    "forgot-password",
    "\.js[\?]{0,1}",
    "\.cljs[\?]{0,1}",
    "\.cljson[\?]{0,1}",
    "\.css[\?]{0,1}",
    "\.xml[\?]{0,1}",
    "\.map[\?]{0,1}",
    "\.ico[\?]{0,1}",
    ".*cl_widget.*",
    "clab=[10]",
    "clickio_ignore_me",
    "admin-ajax",
    "wp-.*",
    "embed",
    "[\?\&]cl_.*"
];

$no_cache_url = false;
foreach ($no_cache_urls as $url) {
    $no_cache_url = preg_match('/(?:'.$url.')/i', $_SERVER['REQUEST_URI']);
    if ($no_cache_url) {
        break ;
    }
}

$no_cache_params = [
    "get_id",
    "lx_nocache",
    "lx_force_nocache",
    "lx_debug",
    "cl_debug",
];

$no_cache_param = false;
foreach ($no_cache_params as $param) {
    $no_cache_param = array_key_exists($param, $_REQUEST);
    if ($no_cache_param) {
        break;
    }
}

$no_cache = $no_cache_url || $no_cache_param;
if ($no_cache) {
    if($no_cache_url){
        $fallback = WP_CONTENT_DIR . '/fallback-advanced-cache.php';
        if (@file_exists($fallback) && @is_readable($fallback)) {
            include_once $fallback;
        }
//        clickio_fallback();
    } else {
        define('WP_ROCKET_ADVANCED_CACHE', true);
    }
    return ;
}

if (!defined('CLICKIO_PLUGIN_DIR')) {
    $plugin_dir = (defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins') . '/clickio-prism';
    define('CLICKIO_PLUGIN_DIR', $plugin_dir);
}

// var_dump(CLICKIO_PLUGIN_DIR, is_dir(CLICKIO_PLUGIN_DIR));die();
if (!@is_dir(CLICKIO_PLUGIN_DIR) || !@file_exists(CLICKIO_PLUGIN_DIR . '/clickioprism.php')) {
    $fallback = WP_CONTENT_DIR . '/fallback-advanced-cache.php';
    if (@file_exists($fallback) && @is_readable($fallback)) {
        include_once $fallback;
    }
//    clickio_fallback();
    return ;
}

global $clickio_options;
$clickio_options = [];

function clickio_protect_replace_dropin()
{
    global $wp_cache_phase1_loaded;
    $wp_cache_phase1_loaded = true;
}

function clickio_option_load()
{
    global $clickio_options;
    $location = WP_CONTENT_DIR . "/uploads/clickio/.config.php";

    if (!@is_file($location) || !@is_readable($location)) {
        return false;
    }
    $cont = file_get_contents($location);
    $raw_cfg = substr($cont, 15);
    $clickio_options = json_decode($raw_cfg, true);
    if (json_last_error() == JSON_ERROR_NONE) {
        return true;
    }
    return false;
}

function clickio_option_get(string $opt, $default = null)
{
    global $clickio_options;
    if (empty($clickio_options)) {
        if (!clickio_option_load()) {
            return $default;
        }
    }

    if (array_key_exists($opt, $clickio_options)) {
        return $clickio_options[$opt];
    }
    return $default;
}

function clickio_getPath($key): string
{
    $blog_id = 1;
    $hash = md5($key);
    $path = sprintf('%s/%s/%s/%s.php', $blog_id, substr($hash, -1, 1), substr($hash, -3, 2), $hash);
    return $path;
}

function clickio_readCacheFile(string $key): string
{
    $path = WP_CONTENT_DIR.'/cache/clickio' . DIRECTORY_SEPARATOR . clickio_getPath($key);
    if (!@is_readable($path)) {
        return '';
    }

    $fp = @fopen($path, 'rb');
    if (!$fp) {
        return '';
    }

    @flock($fp, LOCK_SH);

    $expires_at = @fread($fp, 4);
    list(, $created_at) = @unpack('L', @fread($fp, 4));
    $data = '';

    if ($expires_at !== false) {
        list(, $expires_at) = @unpack('L', $expires_at);

        if (time() > $expires_at) {
            clickio_purge($key);
        } else {
            clickio_setCacheHeaders($created_at, $expires_at);
            while (!@feof($fp)) {
                $data .= @fread($fp, 4096);
            }
            $data = substr($data, 14);
        }
    }

    @flock($fp, LOCK_UN);
    @fclose($fp);
    return $data;
}

function clickio_setCacheHeaders($created, $expires)
{
    if (!is_numeric($expires) || empty($expires) || $expires <= 0) {
        $expires = time() + 60;
    }

    $max_age = $expires - time();
    if (empty($max_age) || $max_age <= 0) {
        $max_age = 60;
    }

    if (!is_numeric($created) || empty($created) || $created <= 0) {
        $created = $expires - 60;
    }

    @header("Cache-Control: max-age=$max_age");
    @header('Last-Modified: '.date('D, j M Y H:i:s \G\M\T', $created));
}

function clickio_get(string $key)
{
    $data = clickio_readCacheFile($key);
    if (!empty($data)) {
        $data_unserialized = @unserialize($data);
    } else {
        $data_unserialized = $data;
    }

    if (empty($data_unserialized)) {
        $data_unserialized = '';
    }
    return $data_unserialized;
}

function clickio_purge(string $key): bool
{
    $path = WP_CONTENT_DIR.'/cache/clickio' . DIRECTORY_SEPARATOR . clickio_getPath($key);

    if (!file_exists($path)) {
        return true;
    }

    return @unlink($path);
}

function clickio_isMobile()
{
    $ua_devices = [
        'Android',
        'iPhone',
        'iPad',
        'iPod'
    ];
    $ua = array_key_exists('HTTP_USER_AGENT', $_SERVER)? $_SERVER['HTTP_USER_AGENT'] : 'no user agent';
    foreach ($ua_devices as $device) {
        if (stripos($ua, $device)) {
            return true;
        }
    }

    return false;
}

function clickio_getCacheKey(string $url): string
{
    $isMobile = clickio_isMobile();
    $isPrism = false;

    $cl_debug = array_key_exists('cl_debug', $_REQUEST)? $_REQUEST['cl_debug'] : '';

    if ($isMobile || !empty($cl_debug)) {
        $isPrism = true;
    }
    $key = sprintf("%s:%d:%d", $url, $isPrism, $isMobile);
    return $key;
}

function clickio_adv_cache_get(string $url): string
{
    $lx_nocache = array_key_exists('lx_nocache', $_GET);
    if (!empty($lx_nocache)) {
        return '';
    }

    $key = clickio_getCacheKey($url);
    return clickio_get($key);
}

function clickio_userAuthenticated()
{
    $black_list = [
        'wordpress_sec_',
        'wordpress_logged_in_',
        'wordpress_rec_'
    ];

    foreach ($black_list as $cookie) {
        foreach (array_keys($_COOKIE) as $name) {
            if (preg_match("/(?:$cookie)/", $name)) {
                return true;
            }
        }
    }
    return false;
}

function clickio_readCacheMeta($key)
{
    $path = WP_CONTENT_DIR.'/cache/clickio/meta' . DIRECTORY_SEPARATOR . clickio_getPath($key);
    if (!@is_readable($path)) {
        return '';
    }

    $fp = @fopen($path, 'rb');
    if (!$fp) {
        return '';
    }

    @flock($fp, LOCK_SH);

    $data = '';

    while (!@feof($fp)) {
        $data .= @fread($fp, 4096);
    }
    $data = substr($data, 14);

    @flock($fp, LOCK_UN);
    @fclose($fp);
    return $data;
}

function clickio_getCacheMeta($url)
{
    $key = clickio_getCacheKey($url);
    $data = clickio_readCacheMeta($key);
    if (!empty($data)) {
        $data_unserialized = @unserialize($data);
    }

    if (empty($data_unserialized) || !is_array($data_unserialized)) {
        $data_unserialized = [];
    }
    return $data_unserialized;
}

function clickio_shutdown()
{
    global $is_prism;
    if ($is_prism) {
        if (function_exists('wp_ob_end_flush_all')) {
            wp_ob_end_flush_all();
        }
        if (class_exists('\Clickio\Prism\Cache\CacheManager')) {
            Clickio\Prism\Cache\CacheManager::saveBuffer();
        }
        exit ;
    }
}

register_shutdown_function('clickio_shutdown');

$clickio_opt_loaded = clickio_option_load();
$clickio_cache = clickio_option_get('cache', 0);
$clickio_custom_login_opt = clickio_option_get('login_url', '');
$clickio_custom_login = preg_match('/(?:'.preg_quote($clickio_custom_login_opt, '/').')/i', $_SERVER['REQUEST_URI']);
$clickio_auth = clickio_userAuthenticated();
$clickio_is_get = array_key_exists('REQUEST_METHOD', $_SERVER)? strtolower($_SERVER['REQUEST_METHOD']) == 'get': false;
$clickio_is_mobile_opt = clickio_option_get('mobile', 0);
$isMobile = clickio_isMobile();
$status = clickio_option_get('status', 'disabled');
$_integration = clickio_option_get("integration_scheme", 'dns');
$integration = $status == 'active' && $_integration == 'cms';

$allow_bot = true;
$ua = array_key_exists('HTTP_USER_AGENT', $_SERVER)? $_SERVER['HTTP_USER_AGENT'] : 'no user agent';
if (stripos($ua, 'googlebot') && $isMobile) {
    $indexing = clickio_option_get('allow_indexing');
    if (empty($indexing)) {
        $allow_bot = false;
    }
}

$_clab_cookie = isset($_COOKIE['_clab'])? $_COOKIE['_clab'] : null;
if (!$clickio_opt_loaded
    || empty($clickio_cache)
    || $clickio_auth
    || !$clickio_is_get
    || empty($clickio_is_mobile_opt)
    || !$isMobile
    || !$integration
    || $_clab_cookie == 1
    || !$allow_bot
) {
//clickio_fallback();
    $fallback = WP_CONTENT_DIR . '/fallback-advanced-cache.php';
    if (@file_exists($fallback) && @is_readable($fallback)) {
        include_once $fallback;
    }
return ;
}

$meta = clickio_getCacheMeta($_SERVER['REQUEST_URI']);
$tmp_disabled = false;
$cache = '';
if (!array_key_exists('last-modified', $meta)) {
    $cache = clickio_adv_cache_get($_SERVER['REQUEST_URI']);
}

if (empty($cache) || $clickio_custom_login || $_clab_cookie == null) {
    define('WP_ROCKET_ADVANCED_CACHE', true);
    header("x-clickio-cache-status: MISS");
    define( 'DONOTCACHEPAGE', 1 );
/*
    $fallback = WP_CONTENT_DIR . '/fallback-advanced-cache.php';
    if (@file_exists($fallback) && @is_readable($fallback)) {
        include_once $fallback;
    }
*/
    return;
} else {
    global $is_prism;
    $is_prism = true;
    header("x-clickio-cache-status: HIT");
    echo $cache;
    exit;
}
// WP SUPER CACHE 1.2 this string disable replace clickio dropin