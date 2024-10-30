<?php

/**
 * WebSub publisher
 */

namespace Clickio\Utils;

use Clickio\Integration\Services\GNPublisher;
use Clickio\Integration\Services\PushPress;
use Clickio\Integration\Services\WebSub as ServicesWebSub;
use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Clickio\Request\Request;
use Exception;

/**
 * Send PuSH notification
 *
 * @package Utils
 */
final class WebSub
{
    /**
     * Logging
     */
    use LoggerAccess;

    /**
     * Hubs list to send notifications
     *
     * @var array
     */
    protected static $default_hubs = [
        "https://pubsubhubbub.appspot.com",
        "https://pubsubhubbub.superfeedr.com"
    ];

    /**
     * List of plugins that implements WebSub
     *
     * @var array
     */
    protected static $disable_plugin = [
        ServicesWebSub::class,
        PushPress::class,
        GNPublisher::class
    ];

    /**
     * List of feed types
     *
     * @var array
     */
    protected static $feed_types = ['atom', 'rss2'];

    /**
     * Send publication notification
     *
     * @return void
     */
    public static function publish()
    {
        $enabled = static::isWebSubEnabled();
        if (empty($enabled)) {
            return ;
        }

        $hubs = static::getHubList();
        $feed_types = apply_filters("_clickio_websub_feed_types", static::$feed_types);

        $body = "hub.mode=publish";
        foreach ($feed_types as $type) {
            $link = get_feed_link($type);
            $body .= "&hub.url=$link";
        }

        foreach ($hubs as $hub) {
            static::_sendPushMessage($hub, $body);
        }
    }

    /**
     * Send http request to hub
     *
     * @param string $hub hub full url
     * @param string $body http post body
     *
     * @return void
     */
    private static function _sendPushMessage(string $hub, string $body)
    {
        $headers = [
            "Content-Type" => "application/x-www-form-urlencoded"
        ];

        $timeout = apply_filters("_clickio_websub_publish_timeout", 5);
        try {
            $req = Request::create(["signed" => false, 'headers' => $headers, "timeout" => $timeout]);
            $resp = $req->post($hub, $body);
            $debug = [
                "url" => $hub,
                "body" => $body,
                "resp" => $resp->toArray()
            ];
            static::logDebug("Push notification", $debug);
        } catch (Exception $e) {
            static::logError($e->getMessage());
        }
    }

    /**
     * Insert rss2 header hub link
     *
     * @return void
     */
    public static function addRssHeadLinks()
    {
        $enabled = static::isWebSubEnabled();
        if (empty($enabled)) {
            return ;
        }

        foreach (static::getHubList() as $hub_url) {
            printf('<atom:link rel="hub" href="%s"/>', $hub_url).PHP_EOL;
        }
    }

    /**
     * Insert atom header hub link
     *
     * @return void
     */
    public static function addAtomHeadLinks()
    {
        $enabled = static::isWebSubEnabled();
        if (empty($enabled)) {
            return ;
        }

        foreach (static::getHubList() as $hub_url ) {
            printf('<link rel="hub" href="%s" />', $hub_url).PHP_EOL;
        }
    }

    /**
     * Add links to http headers
     *
     * @return void
     */
    public static function addHeadersLinks()
    {
        $enabled = static::isWebSubEnabled();
        if (empty($enabled)) {
            return ;
        }

        $host = wp_parse_url(home_url());

        $uri = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', 'localhost');
        $self_url = esc_url(apply_filters('self_link', set_url_scheme('http://'.$host['host'].wp_unslash($uri))));

        foreach (static::getHubList() as $hub_url) {
            $link = sprintf('Link: <%s>; rel="hub"', $hub_url);
            header($link, false);
        }

        $self_link = sprintf('Link: <%s>; rel="self"', $self_url);
        header($self_link, false);
    }

    /**
     * Check the WebSub is enabled
     *
     * @return bool
     */
    public static function isWebSubEnabled(): bool
    {
        $status = Options::get('websub_enabled');
        foreach (static::$disable_plugin as $plugin) {
            if ($plugin::isWebSubEnabled()) {
                $status = false;
                break;
            }
        }

        return apply_filters('_clickio_websub_enabled', $status);
    }

    /**
     * Get hubs to send notifications
     *
     * @return array
     */
    public static function getHubList(): array
    {
        return apply_filters("_clickio_websub_hubs", static::$default_hubs);
    }
}
