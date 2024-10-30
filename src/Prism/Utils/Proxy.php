<?php

/**
 * Listen for manifest.json request
 */

namespace Clickio\Prism\Utils;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Clickio\Prism\Cache\CacheRepo;
use Clickio\Request\Request;
use Clickio\Utils\CacheUtils;
use Clickio\Utils\SafeAccess;
use Exception;

/**
 * Handling requests to manifest.json
 *
 * @package Prism
 */
final class Proxy
{
    /**
     * Logger methods
     */
    use LoggerAccess;

    /**
     * Setup manifest listners
     *
     * @return void
     */
    public static function init()
    {
        $integration = Options::get('integration_scheme');
        if ($integration != 'cms') {
            return ;
        }

        static::addManifestProxy();
        static::addClickioJsProxy();
        static::addClickioPluginJsProxy();
        static::addClWidgetProxy();
    }

    /**
     * Add rewrite rule for manifest
     *
     * @return void
     */
    protected static function addManifestProxy()
    {
        $url_re = "^manifest\.cljson$";
        $target = 'index.php?cl_manifest=1';
        $place = 'top';
        add_rewrite_rule($url_re, $target, $place);

        add_filter(
            'query_vars',
            function ($vars) {
                $vars[] = 'cl_manifest';
                return $vars;
            }
        );

        add_action("wp", [static::class, 'manifestCallback']);
    }

    /**
     * Manifest route callback
     *
     * @return void
     */
    public static function manifestCallback()
    {
        $is_manifest = get_query_var('cl_manifest');
        if (empty($is_manifest)) {
            return ;
        }

        $w3total = IntegrationServiceFactory::getService('w3total');
        $w3total::disable();

        $params = [
            "domain" => SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost')
        ];

        $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        $parsed = wp_parse_url($url);
        $path = SafeAccess::fromArray($parsed, 'path', 'string', '/');
        $manifest = static::getCache($path);
        if (empty($manifest)) {
            try {
                $req = Request::create(["timeout" => 5, "signed" => false]);
                $manifest_res = $req->get("https://pwa.clickiocdn.com/manifest.json", $params);
                $manifest = $manifest_res->body;
                static::setCache($path, $manifest);
                CacheUtils::setCacheStatusHeader(false);
            } catch (Exception $err) {
                static::logError($err->getMessage());
                return ;
            }
        } else {
            CacheUtils::setCacheStatusHeader(true);
        }

        header("Content-type: application/manifest+json");
        echo $manifest;
        exit(0);
    }

    /**
     * Add rewrite rule for clickio.js
     *
     * @return void
     */
    protected static function addClickioJsProxy()
    {
        $url_re = "^clickio\.cljs$";
        $target = 'index.php?cl_clickiojs=1';
        $place = 'top';
        add_rewrite_rule($url_re, $target, $place);

        add_filter(
            'query_vars',
            function ($vars) {
                $vars[] = 'cl_clickiojs';
                return $vars;
            }
        );

        add_action("wp", [static::class, 'clickioJsCallback'], 10);
    }

    /**
     * Clickio.js route callback
     *
     * @return void
     */
    public static function clickioJsCallback()
    {
        $is_clickiojs = get_query_var('cl_clickiojs');
        if (empty($is_clickiojs)) {
            return ;
        }

        $w3total = IntegrationServiceFactory::getService('w3total');
        $w3total::disable();

        $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        $parsed = wp_parse_url($url);
        $path = SafeAccess::fromArray($parsed, 'path', 'string', '/');
        $script = static::getCache($path);
        if (empty($script)) {
            try {
                $req = Request::create(["timeout" => 3, "signed" => false]);
                $script_res = $req->get("https://all.stage.clickio.com/clickio_plugin.js");
                $script = $script_res->body;
                static::setCache($path, $script);
                CacheUtils::setCacheStatusHeader(false);
            } catch (Exception $err) {
                static::logError($err->getMessage());
                return ;
            }
        } else {
            CacheUtils::setCacheStatusHeader(true);
        }

        header("Content-type: application/javascript");
        echo $script;
        exit(0);
    }

    /**
     * Get data from cache
     *
     * @param string $url url path
     *
     * @return string
     */
    protected static function getCache(string $url): string
    {
        $repo = CacheRepo::getInstance();
        $raw_url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        if (preg_match("/lx_force_nocache/", $raw_url)) {
            $repo->purge($url, false, true);
            return '';
        }

        $cache = $repo->get($url);
        if (!empty($cache)) {
            static::logDebug("Page from cache", ["url" => $url]);
            return $cache;
        }

        return '';
    }

    /**
     * Put data to cache
     *
     * @param string $url url path
     * @param mixed $data cache content
     *
     * @return void
     */
    protected static function setCache(string $url, $data)
    {
        $raw_url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        if (preg_match("/lx_force_nocache/", $raw_url)) {
            return ;
        }

        $repo = CacheRepo::getInstance();
        $repo->set($url, $data);
    }

    /**
     * Add rewrite rule for cl_widget param
     *
     * @return void
     */
    protected static function addClWidgetProxy()
    {
        add_action("wp", [static::class, 'clWidgetCallback'], 10);
    }

    /**
     * Clickio.js route callback
     *
     * @return void
     */
    public static function clWidgetCallback()
    {
        $cl_widget = SafeAccess::fromArray($_GET, 'cl_widget', 'string', '');
        if (empty($cl_widget)) {
            return ;
        }

        $w3total = IntegrationServiceFactory::getService('w3total');
        $w3total::disable();

        add_filter("http_request_redirection_count", function () {return 0;});

        $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        $parsed = wp_parse_url($url);
        $path = SafeAccess::fromArray($parsed, 'path', 'string', '/');
        $query = SafeAccess::fromArray($parsed, 'query', 'string', 'cl_widget=1');
        $query .= "&domain=".SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');

        $req = Request::create(["timeout" => 20, "signed" => false]);
        $req->setHeader('swipe', 1);

        $req_url = "https://pwa.clickiocdn.com/a$path?$query";
        try {
            $widget = $req->get($req_url, []);
        } catch (Exception $err) {
            header("Content-type: application/json");
            $empty_data = [
                "Html" => "",
                "Next" => "",
                "NextPages" => [],
                "Prev" => "",
                "PrevPages" => [],
                "NoContent" => true
            ];
            echo wp_json_encode($empty_data);
            header("x-clickio-prism-error: ".$err->getMessage());
            header("x-clickio-prism-error-url: $req_url");
            exit;
        }
        // $widget = $req->get("https://wp8.adlabsnetworks.ru/a$path?$query");
        if ($widget->response >= 300) {
            http_response_code(400);
            header("x-clickio-prism-error: Prism service responds with ".$widget->response." code");
            header("x-clickio-prism-error-url: $req_url");
        } else {
            header("Content-type: application/json");
            echo wp_json_encode($widget->body);
        }
        exit(0);
    }

    /**
     * Add rewrite rule for clickio.js
     *
     * @return void
     */
    public static function addClickioPluginJsProxy()
    {
        $url_re = "^clickio_plugin\.cljs$";
        $target = 'index.php?cl_clickio_pluginjs=1';
        $place = 'top';
        add_rewrite_rule($url_re, $target, $place);

        add_filter(
            'query_vars',
            function ($vars) {
                $vars[] = 'cl_clickio_pluginjs';
                return $vars;
            }
        );

        add_action("wp", [static::class, 'clickioPluginJsCallback'], 10);
    }

    /**
     * Clickio_plugin.js route callback
     *
     * @return void
     */
    public static function clickioPluginJsCallback()
    {
        $is_clickio_pluginjs = get_query_var('cl_clickio_pluginjs');
        if (empty($is_clickio_pluginjs)) {
            return ;
        }

        $w3total = IntegrationServiceFactory::getService('w3total');
        $w3total::disable();

        $url = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '');
        $parsed = wp_parse_url($url);
        $path = SafeAccess::fromArray($parsed, 'path', 'string', '/');
        $script = static::getCache($path);
        if (empty($script)) {
            try {
                $req = Request::create(["timeout" => 3, "signed" => false]);
                $script_res = $req->get("https://all.stage.clickio.com/clickio_plugin.js");
                $script = $script_res->body;
                static::setCache($path, $script);
                CacheUtils::setCacheStatusHeader(false);
            } catch (Exception $err) {
                static::logError($err->getMessage());
                return ;
            }
        } else {
            CacheUtils::setCacheStatusHeader(true);
        }

        header("Content-type: application/javascript");
        echo $script;
        exit(0);
    }
}
