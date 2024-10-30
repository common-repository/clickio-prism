<?php
/**
 * Manage Clickio CDN cache
 */

namespace Clickio\CacheControl\Services;

use Clickio as org;
use Clickio\CacheControl as cc;
use Clickio\Prism\Cache\CacheFactory;
use Clickio\Prism\Cache\CacheRepo;
use Clickio\Prism\Cache\Engine\Internal;
use Clickio\Utils\Permalink;
use Clickio\Utils\SafeAccess;

/**
 * Clickio cdn cache service
 *
 * @package CacheControl\Services
 */
class ClickIoCDN extends cc\ServiceBase
{

    /**
     * Label
     *
     * @var string
     */
    protected $label = "Clickio CDN";

    /**
     * Description
     *
     * @var string
     */
    protected $desc = "Purge Clickio CDN cache";

    /**
     * Cleaner url
     *
     * @var string
     */
    const CLEAN_URL = 'https://all.stage.clickio.com/clickioUtils/clearUrlSyncBatch/';

    /**
     * Key to store transient urls
     *
     * @var string
     */
    const TRANSIENT_KEY = '_clickio_not_purget_urls';

    /**
     * Interface method
     * For more information see method defenition
     *
     * @param array $urllist list of urls
     *
     * @return void
     */
    public function clear(array $urllist)
    {
        if (empty($urllist)) {
            return ;
        }
        static::logInfo("Purge cache - start.\n\nWP urls: ".wp_json_encode($urllist, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->purgeInternalCache();

        $transient_urls = get_transient(static::TRANSIENT_KEY);
        $enc_trans_urls = wp_json_encode($transient_urls, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        static::logInfo(sprintf("Transient:\n\nUrls: %s", $enc_trans_urls));
        if (!empty($transient_urls)) {
            static::logInfo("Merge transient and wp urls.");
            $urllist = array_merge($urllist, $transient_urls);
            $urllist = array_values(array_unique($urllist));
            delete_transient(static::TRANSIENT_KEY);
        }
        set_transient(static::TRANSIENT_KEY, $urllist, 2 * WEEK_IN_SECONDS);
        static::logInfo("Final url list.\n\nUrls: ".wp_json_encode(array_values($urllist), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $params = $this->getRequestParams($urllist);
        $delay = org\Options::get('deffered_purge');
        // we don't need unexpected values like '' or false or NULL
        if (empty($delay)) {
            $delay = 0;
        }
        $req_id = $this->generateRequiestId();
        $url = sprintf("%s?deffered_purge=%s&request_id=%s", static::CLEAN_URL, $delay, $req_id);
        $data = wp_remote_post($url, $params);

        $params['body'] = json_decode($params['body']);

        $log_data = ["url" => $url, "params" => $params, "response" => $data, "delay" => $delay];
        static::logInfo("Purge CDN cache\n\n".wp_json_encode($log_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (is_wp_error($data) || $data['response']['code'] >= 400) {
            if (is_wp_error($data)) {
                $err = $data->get_error_message();
            } else {
                $encoded_params = wp_json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $encoded_data = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $err = sprintf("url: %s\n\nparams: %s\n\nresp: %s", $url, $encoded_params, $encoded_data);
            }
            $urls = "\n\nUrls: ".wp_json_encode($urllist, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            static::logError($err.$urls);
            static::logInfo("Purge cache - end.");
            if (!empty($transient_urls)) {
                $transient_urls_info = get_transient(static::TRANSIENT_KEY);
                $enc_trans_urls_info = wp_json_encode($transient_urls_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                static::logInfo('Saved urls: '.$enc_trans_urls_info);
            }
            return ;
        }

        delete_transient(static::TRANSIENT_KEY);
        static::logInfo("Purge cache - end.");
        if (!empty($transient_urls)) {
            static::logInfo('Transient urls succefully sended.');
        }
    }

    /**
     * Build request params
     *
     * @param array $urls list of urls to be purged
     *
     * @return array
     */
    protected function getRequestParams(array $urls): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.org\Options::getApplicationKey()
        ];

        $body = $this->buildBody($urls);

        $params = [
            'timeout' => 15,
            'user-agent' => 'WP',
            'followlocation' => true,
            'sslverify' => false,
            'headers' => $headers,
            'body' => wp_json_encode($body)
        ];
        return $params;
    }

    /**
     * Build http body
     *
     * @param array $urls list of urls to be purged
     *
     * @return array
     */
    protected function buildBody(array $urls): array
    {
        $body = [];

        foreach ($urls as $url) {
            if (empty($url)) {
                continue ;
            }
            $params = $this->getUrlRequestParams($url);
            $body = array_merge($body, $params);
        }
        return $body;
    }

    /**
     * Build url struct
     *
     * @param string $url single url
     *
     * @return array
     */
    protected function getUrlRequestParams(string $url): array
    {
        $urls = [];

        $parsed_url = wp_parse_url($url);
        $_server_host = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'default');
        $host = SafeAccess::fromArray($parsed_url, 'host', 'string', $_server_host);
        $path = SafeAccess::fromArray($parsed_url, 'path', 'string', '/');
        $query = SafeAccess::fromArray($parsed_url, 'query', 'string', '');
        $parsed_query = Permalink::parseQuery($query);

        $purge_all = false;
        if (in_array('purge_all', array_keys($parsed_query))) {
            $purge_all = true;
        }

        $canonical = org\Options::get("purge_canonical");
        if (in_array('purge_canonical', array_keys($parsed_query))) {
            $canonical = 1;
        }

        $mark_deleted = 0;
        if (in_array('deleted', array_keys($parsed_query))) {
            $mark_deleted = 1;
        }

        $urls[] = $this->_getUrlRequestParam($host, $path, $purge_all, $canonical, $mark_deleted);

        if ($purge_all) {
            return $urls;
        }

        if (substr($path, -1)=== '/') {
            $path_without_slash = substr($path, 0, (strlen($path) - 1));
            if (!empty($path_without_slash)) {
                $urls[] = $this->_getUrlRequestParam($host, $path_without_slash, false, $canonical, $mark_deleted);
            }
        }

        return $urls;
    }

    /**
     * Build url struct
     *
     * @param string $host FQDN
     * @param string $path url
     * @param bool $purge_all flag, purge all pages
     * @param int $canonical flag, purge canonical caches
     * @param int $mark_deleted add flag if post was deleted
     *
     * @return array
     */
    private function _getUrlRequestParam(
        string $host,
        string $path,
        bool $purge_all = false,
        int $canonical = 0,
        int $mark_deleted = 0
    ) : array {
        $url_param = [
            'daemon' => org\Options::get("daemon_version", "Master"),
            'domain' =>  $host,
            'url' => $path,
            'desktop' => 1,
            'mobile' => 1
        ];

        if ($purge_all) {
            $url_param['url'] = '/';
            $url_param['canonical'] = $canonical;
            $url_param['all'] = 1;
        }

        if (!empty($mark_deleted)) {
            $url_param['deleted'] = 1;
        }
        return $url_param;
    }

    /**
     * Generate unique sequence
     *
     * @return string
     */
    protected function generateRequiestId()
    {
        $length = random_int(10, 30);
        $bytes = bin2hex(random_bytes($length));
        return md5($bytes);
    }

    /**
     * Purge internal caches
     *
     * @return void
     */
    protected function purgeInternalCache()
    {
        static::logDebug("Purge internal cache", []);
        $engine = CacheFactory::make(Internal::class, []);
        $cache = new CacheRepo($engine);
        $cache->purgeAll();
    }
}
