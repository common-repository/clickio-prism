<?php

/**
 * Check the policy for the page
 */

namespace Clickio\Utils;

use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Clickio\Request\Request;
use Exception;

/**
 * Check the page for prohibited content
 *
 * @package Utils
 */
final class PolicyCheck
{
    /**
     * Logger methods
     */
    use LoggerAccess;

    /**
     * The page contain prohibeted content
     *
     * @var int
     */
    const STATUS_TRUE = 1;

    /**
     * Regular page
     *
     * @var int
     */
    const STATUS_FALSE = -1;

    /**
     * This means - somthing went wrong
     * It can be a network problems or daemon is down or somthing like
     *
     * @var int
     */
    const STATUS_EMPTY = 0;

    /**
     * Default policy struct
     *
     * @var array
     */
    protected $post_struct = [
        "status" => self::STATUS_EMPTY,
        "valid_until" => 0
    ];

    /**
     * Policy daemon url
     *
     * @var string
     */
    protected $policy_daemon = "https://clickiocdn.com/hbadx/";

    /**
     * Singletone container
     *
     * @var self
     */
    protected static $inst = null;

    /**
     * Status check frequency
     *
     * @var int
     */
    protected $status_ttl = DAY_IN_SECONDS;

    /**
     * Post meta field name
     *
     * @var string
     */
    const META_KEY = 'clickio_policy_status';

    /**
     * Protected constructor
     */
    protected function __construct()
    {

    }

    /**
     * Singletone constructor
     *
     * @return self
     */
    public static function getInstance()
    {
        if (empty(static::$inst)) {
            static::$inst = new static();
        }

        return static::$inst;
    }

    /**
     * Check article for policy errors
     *
     * @param int $id post->ID
     *
     * @return array
     */
    protected function verify(int $id): int
    {
        static::logDebug("Verify post start", ["post_id" => $id]);
        $post = get_post($id);
        if (empty($post)) {
            return static::STATUS_EMPTY;
        }
        $status = $this->post_struct['status'];
        try {
            $site_id = Options::get('site_id');
            $title = $post->post_title;
            if (empty($site_id) || empty($title)) {
                return static::STATUS_EMPTY;
            }

            $domain = get_permalink($post);
            $url = $this->getDaemonUrl($site_id, $title, $domain);
            $body = $this->makeRequest($url);
            $status = $this->_getStatus($body);

            $debug = [
                "request" => $url,
                "response" => $body,
                "is_policy" => $status
            ];
            static::logDebug("Verify post end", $debug);
        } catch (Exception $err) {
            static::logError($err->getMessage());
        }
        return $status;
    }

    /**
     * Make http request
     *
     * @param string $url url string
     *
     * @return string
     */
    protected function makeRequest(string $url): string
    {
        $body = '';
        $req = Request::create(['timeout' => 2]);
        $resp = $req->get($url);
        $body = $resp->body;
        if (empty($body) || !is_string($body)) {
            $body = '';
        }
        return $body;
    }

    /**
     * Build daemon url
     *
     * @param int $site_id real site id
     * @param string $title article title
     * @param string $url post permalink
     *
     * @return string
     */
    protected function getDaemonUrl(int $site_id, string $title, string $url): string
    {
        $params = [
            "site_id" => $site_id,
            "title" => $title,
            "l" => $url
        ];
        $query_str = \http_build_query($params);
        return sprintf("%s?%s", $this->policy_daemon, $query_str);
    }

    /**
     * Get status check frequency
     *
     * @return int
     */
    public function getFrequency(): int
    {
        return apply_filters("_clickio_policy_check_frequency", $this->status_ttl);
    }

    /**
     * Get policy status from body
     *
     * @param string $body http response body
     *
     * @return int
     */
    private function _getStatus(string $body = ''): int
    {
        if (preg_match('/adxAllowed\(1\)/', $body)) {
            return static::STATUS_FALSE;
        } elseif (preg_match('/adxAllowed\(0\)/', $body)) {
            return static::STATUS_TRUE;
        }

        return static::STATUS_EMPTY;
    }

    /**
     * Get valid_until timestamp
     *
     * @return int
     */
    protected function getStatusTtl(): int
    {
        $ttl = $this->getFrequency();
        $now = time();
        return $now + $ttl;
    }

    /**
     * Replace post status
     *
     * @param int $id post->ID
     * @param int $status policy status
     * @param int $ttl valid until
     *
     * @return array
     */
    public function setStatus(int $id, int $status, int $ttl): array
    {
        // post_struct must be readonly
        $struct = $this->post_struct;
        $struct['status'] = $status;
        $struct['valid_until'] = $ttl;
        update_post_meta($id, static::META_KEY, $struct);
        return $struct;
    }

    /**
     * Check the status is up to date
     *
     * @param mixed $struct status_struct
     *
     * @return bool
     */
    protected function validateStruct($struct): bool
    {
        if (empty($struct) || is_wp_error($struct)) {
            return false;
        }

        $keys = array_keys($this->post_struct);
        foreach ($keys as $key) {
            if (!array_key_exists($key, $struct)) {
                return false;
            }
        }

        $ttl = (int)$struct['valid_until'];
        $now = time();
        if ($ttl <= $now) {
            return false;
        }

        return true;
    }

    /**
     * Get policy status struct
     *
     * @param int $id $post->ID
     *
     * @return array
     */
    public static function getStatus(int $id): array
    {
        $struct = get_post_meta($id, static::META_KEY, true);
        $obj = static::getInstance();
        if (!$obj->validateStruct($struct)) {
            $status = $obj->verify($id);
            $ttl = $obj->getStatusTtl();
            $struct = $obj->setStatus($id, $status, $ttl);
        }
        return $struct;
    }

    /**
     * Check for prohibitet content
     *
     * @param int $id $post->ID
     *
     * @return bool
     */
    public static function isPolicy(int $id): bool
    {
        $struct = static::getStatus($id);
        return $struct['status'] > static::STATUS_EMPTY;
    }
}
