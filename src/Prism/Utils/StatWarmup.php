<?php

/**
 * Service utils
 */

namespace Clickio\Prism\Utils;

use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Clickio\Request\Request;
use Clickio\Utils\SafeAccess;
use WP_Post;

/**
 * Service utils
 *
 * @package Prism
 */
final class StatWarmup
{
    /**
     * Logger trait
     * Easier creation of journal entries
     */
    use LoggerAccess;

    /**
     * Warmup endpoint
     *
     * @var string
     */
    const NEW_POST_EVENT_URL = "https://all.stage.clickio.com/clickioUtils/cacheWarmup";

    /**
     * Key name used to store data if warmup was failed
     *
     * @var string
     */
    const TRANSIENT_KEY = "_clickio_failed_warmup";

    /**
     * Singletone container
     *
     * @var self
     */
    protected static $inst;

    /**
     * Setup event listners
     *
     * @return void
     */
    public static function setupEventListners()
    {
        $status = Options::get('cache_warmup_enabled');
        if ($status) {
            $obj = static::getInstance();
            add_action("transition_post_status", [$obj, 'notifyPostPublished'], 10, 3);
        }
    }

    /**
     * Factory method.
     * Create a Singletone
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (empty(static::$inst)) {
            static::$inst = new static();
        }

        return static::$inst;
    }

    /**
     * Notify clickio server that post was published
     *
     * @param string $new_status post ID
     * @param string $old_status post ID
     * @param WP_Post $post_obj post instance
     *
     * @return void
     */
    public function notifyPostPublished($new_status, $old_status, $post_obj)
    {
        if ($new_status != 'publish' || $old_status == 'publish' || !($post_obj instanceof WP_Post)) {
            return ;
        }

        $daemon = Options::get("daemon_version");
        $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $parts = wp_parse_url(get_permalink($post_obj));
        $current_url = SafeAccess::fromArray($parts, 'path', 'string', '/');
        $urllist = [$current_url];

        $transient_urls = get_transient(static::TRANSIENT_KEY);
        if (!empty($transient_urls)) {
            $urllist = array_merge($urllist, $transient_urls);
            $urllist = array_unique($urllist);
            delete_transient(static::TRANSIENT_KEY);
        }
        set_transient(static::TRANSIENT_KEY, array_values($urllist), 2 * WEEK_IN_SECONDS);
        foreach ($urllist as $url) {
            $url_data = [
                "daemon" => $daemon,
                "domain" => $domain,
                "url" => $url,
            ];
            $this->_notify($url_data);
        }
    }

    /**
     * Send request to stat server
     *
     * @param array $data http body
     *
     * @return void
     */
    private function _notify(array $data)
    {
        $appkey = Options::getApplicationKey();
        $request = Request::create(["timeout" => 10, "signed" => false, 'ua' => 'WP']);
        $request->setHeader('Authorization', "bearer $appkey");
        $request->setHeader('Content-Type', "application/json");

        try {
            $resp = $request->post(static::NEW_POST_EVENT_URL, wp_json_encode($data));
            $debug_data = [
                "url" => static::NEW_POST_EVENT_URL,
                "body" => $data,
                "resp" => $resp->body
            ];

            $this->logDebug("Warmup cache", $debug_data);
            if ($resp->response >= 400) {
                $msg = is_array($resp->body)? $resp->body['msg'] : 'Strange response body, it looks like it was a network issue';
                $this->logError(sprintf("Warmup cache: Error: %s", $msg));
                return ;
            }
            $this->logInfo("Warmup cache: Success");
            delete_transient(static::TRANSIENT_KEY);
        } catch (\Exception $err) {
            $this->logError(sprintf("Warmup cache: Error: %s", $err->getMessage()));
        }
    }
}
