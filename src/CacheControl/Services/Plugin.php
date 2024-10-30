<?php
/**
 * Plugin cache
 */

namespace Clickio\CacheControl\Services;

use Clickio\CacheControl as cc;
use Clickio\Prism\Cache\CacheRepo;
use Clickio\Utils\SafeAccess;

/**
 * Plugin cache service
 *
 * @package CacheControl\Services
 */
class Plugin extends cc\ServiceBase
{

    /**
     * Label
     *
     * @var string
     */
    protected $label = "Plugin cache";

    /**
     * Description
     *
     * @var string
     */
    protected $desc = "Purge plugin cache";

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

        $first = SafeAccess::fromArray($urllist, 0, 'string', '');
        $parsed = wp_parse_url($first);
        $query = SafeAccess::fromArray($parsed, 'query', 'string', '');
        if (preg_match('/purge_all/', $query)) {
            $this->clearAll();
        } else {
            $this->clearUrls($urllist);
        }
    }

    /**
     * Purge all
     *
     * @return void
     */
    protected function clearAll()
    {
        static::logDebug("Purge all", []);
        $repo = CacheRepo::getInstance();
        $repo->purgeAll();
    }

    /**
     * Clear url list
     *
     * @param array $urllist list of urls
     *
     * @return void
     */
    protected function clearUrls(array $urllist)
    {
        $purge_urls = $this->rewriteUrls($urllist);
        static::logDebug("Urls to be purged", $purge_urls);

        $repo = CacheRepo::getInstance();
        foreach ($purge_urls as $url) {
            $repo->purge($url, true);
        }
    }

    /**
     * Rewrite urls for all cache versions
     *
     * @param array $urls url list
     *
     * @return array
     */
    protected function rewriteUrls(array $urls): array
    {
        $rewrited_urls = [];
        foreach ($urls as $url) {
            $parsed = wp_parse_url($url);
            $path = SafeAccess::fromArray($parsed, 'path', 'string', '/');
            $trailing_list = $this->getTrailingSlashUrl($path);
            foreach ($trailing_list as $trailing_url) {
                $amp_url = sprintf("/amp%s", $trailing_url);
                $swipe_url = sprintf("%s?lx_sh=1", $trailing_url);
                $rewrited_urls[] = $trailing_url;
                $rewrited_urls[] = $amp_url;
                $rewrited_urls[] = $swipe_url;
            }
        }
        $purge_urls = [];
        foreach ($rewrited_urls as $r_url) {
            $url_keys = $this->addKeyToUrl($r_url);
            $purge_urls = array_merge($purge_urls, $url_keys);
        }

        return array_unique($purge_urls);
    }

    /**
     * Add both urls, with trailing slash and without
     *
     * @param string $url url
     *
     * @return array
     */
    protected function getTrailingSlashUrl(string $url): array
    {
        $trailing_slash = substr($url, -1, 1);
        $urls = [];
        if ($trailing_slash === '/') {
            $new_url = substr($url, 0, strlen($url) - 1);
            $urls[] = $new_url;
        } else {
            $new_url = $url.'/';
            $urls[] = $new_url;
        }
        $urls[] = $url;
        return $urls;
    }

    /**
     * Add cache version key to the url
     *
     * @param string $url url
     *
     * @return array
     */
    protected function addKeyToUrl(string $url): array
    {
        $urls = [
            sprintf("%s:0:0", $url),
            sprintf("%s:0:1", $url),
            sprintf("%s:1:0", $url),
            sprintf("%s:1:1", $url),
        ];
        return $urls;
    }

}
