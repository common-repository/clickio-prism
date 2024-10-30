<?php

/**
 * Permalink helper
 */

namespace Clickio\Utils;

use Clickio\Meta\PostMeta;
use Clickio\Meta\TermMeta;

/**
 * Wrapper to get permalink of any object
 *
 * @package Utils
 */
final class Permalink
{

    /**
     * Get url of current location
     *
     * @return string
     */
    public static function getCurrentLocationUrl(): string
    {
        global $wp;
        $url = '';
        switch(LocationType::getType()){
            case "home":
                $url = self::getHomeUrl();
                break;
            case "post":
                $url = self::getPostUrl();
                break;
            case 'taxonomy':
                $url = self::getTaxonomyUrl();
                break;
            case 'archive':
                $url = self::getArchiveUrl();
                break;
            default:
                $url = home_url($wp->request);
        }
        return $url;
    }

    /**
     * Get home page url
     *
     * @return string
     */
    public static function getHomeUrl(): string
    {
        return home_url();
    }

    /**
     * Post url
     *
     * @param int $id post id, leave empty when in the loop
     *
     * @return string
     */
    public static function getPostUrl(int $id = 0):string
    {
        if (empty($id)) {
            $id = get_the_ID();
        }
        $post_meta = PostMeta::createFromId($id);
        return $post_meta->getPermalink();
    }

    /**
     * Get url to taxonomy
     *
     * @param int $term_id taxonomy term id, leave empty when in the loop
     *
     * @return string
     */
    public static function getTaxonomyUrl(int $term_id = 0): string
    {
        if (empty($term_id)) {
            $term_id = get_queried_object_id();
        }
        $term = TermMeta::createFromId($term_id);
        return $term->getPermalink();
    }

    /**
     * Get url to post archive
     *
     * @param string $type archive type, leave empty when in the loop
     *
     * @return string
     */
    public static function getArchiveUrl(string $type = ''): string
    {
        if (empty($id)) {
            $id = get_the_ID();
            $post = get_post($id);
            $type = $post->post_type;
        }

        return get_post_type_archive_link($type);
    }

    /**
     * Get the full requested url with protocol, domain and query
     * e.g. https://example.com/some-url-section/url-section2/?param1=value1&param2=value2
     *
     * @return string
     */
    public static function getFullCurrentUrl(): string
    {
        $https = SafeAccess::fromArray($_SERVER, 'HTTPS', 'mixed', 'on');
        $server_port = SafeAccess::fromArray($_SERVER, 'SERVER_PORT', 'mixed', 443);
        $domainName = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $request_uri = SafeAccess::fromArray($_SERVER, 'REQUEST_URI', 'string', '/');
        $protocol = (!empty($https) && $https !== 'off' || $server_port == 443) ? "https://" : "http://";

        return $protocol.$domainName.$request_uri;
    }

    /**
     * Get full url with or without trailing slash
     *
     * @param string $url url for which an alternative is needed
     *
     * @return string
     */
    public static function getTralingSlashUrl(string $url = ''): string
    {
        if (empty($url)) {
            $url = static::getFullCurrentUrl();
        }

        $parsed_url = parse_url($url);
        $query = array_key_exists('query', $parsed_url)? '?'.$parsed_url['query'] : '';
        $path = '';

        if (substr($parsed_url['path'], -1) == '/') {
            // without trailing slash
            $path = substr($parsed_url['path'], 0, (strlen($parsed_url['path']) - 1)).$query;
        } else {
            $path = $parsed_url['path'].'/'.$query;
        }

        return sprintf("%s://%s%s", $parsed_url['scheme'], $parsed_url['host'], $path);
    }

    /**
     * Parse url to array
     *
     * @param string $url url string
     *
     * @return array
     */
    public static function parseUrl(string $url)
    {
        $parsed_url = parse_url($url);
        $protocol = SafeAccess::fromArray($parsed_url, 'scheme', 'string', 'https');
        $host = SafeAccess::fromArray($parsed_url, 'host', 'string', 'localhost');
        $path = SafeAccess::fromArray($parsed_url, 'path', 'string', '/');
        $query = [];
        $raw_query = SafeAccess::fromArray($parsed_url, 'query', 'string', '');
        if (!empty($raw_query)) {
            $query = static::parseQuery($raw_query);
        }
        return [
            "protocol" => $protocol,
            "host" => $host,
            "path" => $path,
            "query" => $query
        ];
    }

    /**
     * Parse query string to array
     *
     * @param string $query query string without ? (question mark)
     *
     * @return array
     */
    public static function parseQuery(string $query): array
    {
        if (empty($query)) {
            [];
        }

        $args = [];
        foreach (explode("&", $query) as $pair) {
            $exploded = explode("=", $pair);
            if (count($exploded) >= 2) {
                list($name, $value) = $exploded;
            } else {
                $name = $exploded[0];
                $value = "";
            }

            $name = trim($name);
            $value = trim(urldecode($value));
            $args[$name] = $value;
        }
        return $args;
    }

    public static function getAnticache(): string
    {
        $length = random_int(5, 25);
        $value = base64_encode(random_bytes($length));
        return md5($value);
    }
}
