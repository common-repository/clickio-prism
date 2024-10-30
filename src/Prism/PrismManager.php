<?php

/**
 * Prism manager
 */

namespace Clickio\Prism;

use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Clickio\Prism\Cache\CacheManager;
use Clickio\Prism\Cache\CacheRepo;
use Clickio\Prism\Cache\Interfaces\ICacheRepo;
use Clickio\Request\Request;
use Clickio\Utils\LocationType;
use Clickio\Utils\PolicyCheck;
use Clickio\Utils\SafeAccess;
use DateTimeImmutable;
use Exception;

/**
 * Clickio prism manager
 *
 * @package Prism
 */
class PrismManager
{
    /**
     * Logging
     */
    use LoggerAccess;

    /**
     * Singletone container
     *
     * @var self
     */
    protected static $inst = null;

    /**
     * Cache repository instance
     *
     * @var ICacheRepo
     */
    protected $cache = null;

    /**
     * Disable prism page if this params in URL
     *
     * @var array
     */
    protected $param_blacklist = [
        "[\?&]s=.*", // default search
    ];

    /**
     * Constructor
     *
     * @param ICacheRepo $cache cache repo
     */
    public function __construct(ICacheRepo $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Factory method
     *
     * @param ?ICacheRepo $repo cache repository
     *
     * @return self
     */
    public static function getInstance($repo = null): self
    {
        if (empty(static::$inst)) {
            if (empty($repo)) {
                $repo = CacheRepo::getInstance();
            }
            static::$inst = new static($repo);
        }

        return static::$inst;
    }

    /**
     * Static alias for getPrism method
     *
     * @return string
     */
    public static function getPrismPage(): string
    {
        $obj = static::getInstance();
        return $obj->getPrism();
    }

    /**
     * Get prism daemon host
     *
     * @return string
     */
    private function _getDaemonHost(): string
    {
        $daemon_cookie = SafeAccess::fromArray($_COOKIE, 'cldaemon', 'string', '');
        $daemon_cookie = ucfirst($daemon_cookie);
        $domain = Options::getPwaHost($daemon_cookie);
        return sprintf("https://%s", $domain);
    }

    /**
     * Get prism daemon endpoint
     *
     * @return string
     */
    protected function getDaemonUrl(): string
    {
        $is_swipe = SafeAccess::fromArray($_SERVER, 'HTTP_SWIPE', 'string', 0);
        $host = $this->_getDaemonHost();
        if ($is_swipe) {
            $url = sprintf("%s/cl-shadow", $host);
        } else {
            $url = sprintf("%s/a", $host);
        }
        $path = $this->getRequestedUrl();
        return sprintf("%s%s", $url, $path);
    }

    /**
     * Get request parameters for the Prism daemon
     *
     * @return array
     */
    protected function getDaemonParams(): array
    {
        $params = [
            "lx_wp" => 1,
            "domain" => SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost'),
        ];

        if (LocationType::isPage()) {
            $params["lx_wp_type"] = 'pages';
            $params["lx_wp_id"] = get_the_ID();
        }

        if (LocationType::isArchive()) {
            $params["wp_is_category"] = 1;
        }

        $is_https = SafeAccess::fromArray($_SERVER, 'HTTPS', 'string', 'off');
        if ($is_https == 'on' ) {
            $params['original_scheme'] = 'https';
        }

        $raw_url = SafeAccess::fromArray($_SERVER, "REQUEST_URI", 'string', '');
        $parsed_url = wp_parse_url($raw_url);
        $raw_query = SafeAccess::fromArray($parsed_url, 'query', 'string', '');
        foreach (explode("&", $raw_query) as $query_items_list) {
            $exploded = explode("=", $query_items_list);
            $key = SafeAccess::fromArray($exploded, 0, 'string', '');
            $value = SafeAccess::fromArray($exploded, 1, 'string', '');
            if (!empty($key) && (!empty($value) || $value == 0)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }

    /**
     * Make a request to the daemon
     *
     * @return string
     */
    private function _getPrism(): string
    {
        $raw_url = SafeAccess::fromArray($_SERVER, "REQUEST_URI", 'string', '');
        $err = $this->applyPrismRules($raw_url);
        if (!empty($err)) {
            $dbg_data = [
                "url" => $raw_url,
                "err" => $err
            ];
            static::logDebug("Prism validation fail", $dbg_data);
            $cache = (CacheManager::make());
            $cache->stopCache();
            throw new Exception($err);
        }

        $pwaHost = $this->getDaemonUrl();
        $params = $this->getDaemonParams();
        $req = Request::create(["signed" => false]);
        try {
            $resp = $req->get($pwaHost, $params);

            $debug_data = [
                'url' => sprintf("%s?%s", $pwaHost, http_build_query($params)),
                'endpoint' => $pwaHost,
                'params' => $params
            ];
            static::logDebug("Trying to get prism page.", $debug_data);
        } catch (Exception $err) {
            $cache = (CacheManager::make());
            $cache->stopCache();
            throw new Exception($err->getMessage(), $err->getCode(), $err);
        }

        $cl_debug = SafeAccess::fromArray($_REQUEST, 'cl_debug', 'string', '0');
        if (!empty($cl_debug) && $cl_debug > 2) {
            $this->_outputDebug(
                [
                "url" => $pwaHost."?".http_build_query($params),
                "request" => $req,
                "response" => $resp
                ]
            );
        }

        $headers = $resp->headers->getAll();
        $is_error = $resp->response >= 400;
        $is_permanent_error = array_key_exists('x-permanent-error', $headers) && $headers['x-permanent-error'] == 1;
        if ($is_error && $is_permanent_error) {
            CacheManager::setCustomLifetime((MINUTE_IN_SECONDS * 15));
        } else if ($is_error && !$is_permanent_error) {
            CacheManager::setCustomLifetime((MINUTE_IN_SECONDS * 5));
        }

        if ($is_error) {
            $url = $this->getRequestedUrl();
            $cache = CacheRepo::getInstance();
            $cache->purgeCacheMeta($url);
            $err = isset($headers['x-error'])? $headers['x-error'] : 'Daemon reponds with status: '.$resp->response;
            throw new \Exception($err);
        }

        if (array_key_exists('last-modified', $headers)) {
            $cache = CacheRepo::getInstance();
            $url = $this->getRequestedUrl();
            $meta = $cache->getCacheMeta($url);

            $cache_meta = SafeAccess::fromArray($meta, 'last-modified', 'string', 'Mon, 1 Jan 1970 00:00:00 UTC');
            $prism_cache_dt = new DateTimeImmutable($headers['last-modified']);
            $plugin_cache_dt = new DateTimeImmutable($cache_meta);

            $prism_ts = (int)$prism_cache_dt->format('U');
            $plugin_ts = (int)$plugin_cache_dt->format('U');

            if ($prism_ts >= $plugin_ts) {
                $cache->purge($url, false, true);
            }
        }
        return $resp->body;
    }

    /**
     * Get prism page
     *
     * @return string
     */
    public function getPrism(): string
    {

        try {
            $prism = $this->_getPrism();
            return $prism;
        } catch (Exception $err) {
            static::logError($err->getMessage());
            header('x-clickio-prism-error: '.$err->getMessage());
            return '';
        }
    }

    /**
     * Format debug output
     *
     * @param array array $data debug info
     *
     * @return void
     */
    private function _outputDebug(array $data)
    {
        echo "<div><h3>Debug:</h3></div>";
        echo "<pre>";
        echo htmlentities(var_export($data, true));
        echo "</pre>";
        die();
    }

    /**
     * Test that page is blacklisted
     *
     * @return bool
     */
    public function isBlacklistedPage(): bool
    {
        $is_article = LocationType::isPost() || LocationType::isPage();
        $is_policy = false;
        if ($is_article) {
            $post_id = get_the_ID();
            $is_policy = PolicyCheck::isPolicy($post_id);
        }

        $blacklist = Options::get('ignore');
        $ignore = false;
        foreach (explode(PHP_EOL, $blacklist) as $pattern) {
            $pattern = preg_quote(trim($pattern), '/');
            if (empty($pattern)) {
                continue ;
            }

            $pattern = preg_replace("/(\\\\\.\\\\\*)|(\\\\\*)/m", '.*', $pattern);
            $host = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
            $uri = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', 'localhost');
            if (preg_match("/$pattern$/", $host.$uri)) {
                $ignore = true;
                break;
            }
        }
        return $is_policy || $ignore;
    }

    /**
     * Build amphtml link for current location
     *
     * @return string
     */
    public function buildAmpLink(): string
    {
        $url_path = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $_server_https = SafeAccess::fromArray('HTTPS', $_SERVER, 'mixed', 'on');
        $_server_port = SafeAccess::fromArray('SERVER_PORT', $_SERVER, 'mixed', 443);

        $amp_type = Options::get('type');
        $amp_domain = Options::get('domain');

        $amphtml_url = '';
        if ($amp_type == 'domain' && !empty($amp_domain)) {
            $is_https = (!empty($_server_https) && $_server_https !== 'off') || $_server_port == 443;
            $amphtml_url = sprintf('%s://%s%s', $is_https? 'https' : 'http', $amp_domain, $url_path);
        } else {
            $amp_url = Options::get('amp_url');
            if (substr($amp_url, 0, 1) == '/') {
                $amp_url = substr($amp_url, 1);
            }
            if (substr($amp_url, -1, 1) == '/') {
                $amp_url = substr($amp_url, 0, strlen($amp_url) - 1);
            }

            if (substr($url_path, 0, 1) == '/') {
                $url_path = substr($url_path, 1);
            }
            if (substr($url_path, -1, 1) == '/') {
                $url_path = substr($url_path, 0, strlen($url_path) - 1);
            }

            $url = get_option('home');
            if (is_ssl()) {
                $scheme = 'https';
            } else {
                $scheme = parse_url($url, PHP_URL_SCHEME);
            }
            $home = set_url_scheme($url, $scheme);

            $permalink = get_permalink();

            if ($amp_type == 'ampfolder') {
                if (substr($permalink, -1, 1) == '/') {
                    $url_path .= '/';
                }
                $amphtml_url = sprintf("%s/%s/%s", $home, $amp_url, $url_path);
            } else if ($amp_type == 'ampfolder_postfix') {
                if (substr($permalink, -1, 1) == '/') {
                    $amp_url .= '/';
                }
                $amphtml_url = sprintf("%s/%s/%s", $home, $url_path, $amp_url);
            }
        }
        return $amphtml_url;
    }

    /**
     * Get amphtml link
     *
     * @return string
     */
    public static function getAmpLink(): string
    {
        $obj = static::getInstance();

        $blacklisted = $obj->isBlacklistedPage();
        $posts = LocationType::isPost() && Options::get("posts") == 1;
        $pages = LocationType::isPage() && Options::get("pages") == 1;
//        $amp_opt = Options::get("useamp");
        if (!$blacklisted && ($posts || $pages)) {
            return $obj->buildAmpLink();
        }
        return '';
    }

    /**
     * Get current url
     *
     * @return string
     */
    protected function getRequestedUrl(): string
    {
        $raw_url = SafeAccess::fromArray($_SERVER, "REQUEST_URI", 'string', '');
        $parsed_url = wp_parse_url($raw_url);
        $path = SafeAccess::fromArray($parsed_url, 'path', 'string', '/');
        return $path;
    }

    /**
     * Rules under which the prism page cannot be shown
     *
     * @param string $url current url
     *
     * @return string
     */
    public function applyPrismRules(string $url): string
    {
        $blacklist_param = false;
        $err = '';
        $blacklist = apply_filters('_clickio_prism_url_blacklist_params', $this->param_blacklist);
        foreach ($blacklist as $param) {
            $blacklist_param = preg_match("/(?:".$param.")/", $url);
            if ($blacklist_param) {
                $err = sprintf("Rule %s", $param);
                break ;
            }
        }

        $checks = [
            "blacklisted_param" => $blacklist_param
        ];
        $checks = apply_filters('_clickio_apply_prism_rules', $checks);
        foreach ($checks as $err_code => $result) {
            if ($result) {
                return static::getErrorMessage($err_code, $err);
            }
        }
        return '';
    }

    /**
     * Get error text
     *
     * @param string $err_code Error code
     * @param string $extra Optional. Additional error description
     *
     * @return string
     */
    public static function getErrorMessage(string $err_code, string $extra = ''): string
    {
        $_tpl = "Prism error: %s; %s;";
        $msg = $_tpl;
        if ($err_code == 'blacklisted_param') {
            $msg = sprintf($msg, "Blacklisted param in url", $extra);
        } else {
            $code = var_export($err_code, true);
            $msg = sprintf($msg, "Undefined error; Code: $code", $extra);
        }
        $msg = apply_filters('_clickio_get_prism_error_msg', $msg, $_tpl);
        return $msg;
    }
}
