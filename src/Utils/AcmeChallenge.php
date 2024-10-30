<?php

/**
 * Acme challenge tools
 */

namespace Clickio\Utils;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Logger\LoggerAccess;
use Clickio\Options;
use Exception;

/**
 * Start Acme challenge
 *
 * @package Utils
 */
final class AcmeChallenge
{
    /**
     * Logger interface
     */
    use LoggerAccess;

    /**
     * Start of Clickio block in .htaccess file
     *
     * @var string
     */
    const HTACCESS_SIGN_START = "#BEGIN Clickio prism";

    /**
     * End of Clickio block in .htaccess file
     *
     * @var string
     */
    const HTACCESS_SIGN_END = "#END Clickio prism";

    /**
     * Options key
     *
     * @var string
     */
    const OPT = "acme_challenge";

    /**
     * Get all active challenges without extra info
     *
     * @return array
     */
    public static function getRawList()
    {
        return Options::get(static::OPT);
    }

    /**
     * Get public info about active challenges
     *
     * @return array
     */
    public static function getList(): array
    {
        $opts = static::getRawList();
        foreach ($opts as &$opt) {
            $file = static::url2file($opt['url']);
            $opt['delete_candidat'] = 0;
            $opt['delete_reason'] = '';
            $opt['content'] = '';

            if (!is_file($file) || !is_readable($file)) {
                $opt['delete_candidat'] = 1;
                $opt['delete_reason'] = 'not_file_or_not_readable';
                continue ;
            }

            $content = file_get_contents($file);
            if (empty($content)) {
                $opt['delete_candidat'] = 1;
                $opt['delete_reason'] = 'no_content';
                continue ;
            }
            $opt['content'] = $content;
        }
        return $opts;
    }

    /**
     * Add new acme challenge
     *
     * @param string $url url path where to find acme code
     * @param string $body response body
     *
     * @return string
     */
    public static function addRule(string $url, string $body)
    {
        $url_path = static::url2file($url);
        $file_created = static::writeChallengeFile($url_path, $body);

        if (!$file_created) {
            static::logWarning("Acme challenge file was not created.");
        }

        $opt = static::getRawList();
        $key = md5("$url|$body");
        $opt[$key] = [
            "url" => $url,
            "body" => $body
        ];
        static::saveChallenge($opt);
        return $key;
    }

    /**
     * Delete acme challenge
     *
     * @param string $key challenge id
     *
     * @return bool
     */
    public static function remove(string $key): bool
    {
        $list = static::getRawList();
        if (!array_key_exists($key, $list)) {
            return false;
        }
        $challenge = $list[$key];

        unset($list[$key]);
        static::saveChallenge($list);

        $file = static::url2file($challenge['url']);
        $deleted = @unlink($file);
        if (!$deleted) {
            static::logNotice("Challenge file is not deleted");
        }
        return true;
    }

    /**
     * Save challnges to DB and regenerate .htaccess rules
     *
     * @param array $challenges challenges to be saved
     *
     * @return void
     */
    protected static function saveChallenge(array $challenges)
    {
        Options::set(static::OPT, $challenges);
        Options::save();

        static::updateHtaccess();
    }

    /**
     * Put challenge code to file
     *
     * @param string $full_path file path e.g. .well-known/acme-challenge/12345-5678-0987-23f23f23f2f
     * @param string $body file content
     *
     * @return bool
     */
    protected static function writeChallengeFile(string $full_path, string $body): bool
    {
        $dir = dirname($full_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            chmod($dir, 0777);
        }
        $bytes = file_put_contents($full_path, $body);
        if (empty($bytes)) {
            $bytes = 0;
        }

        $debug_data = [
            "bytes" => $bytes,
            "is_file" => is_file($full_path),
            "is_readable" => is_readable($full_path)
        ];
        static::logDebug("File status:", $debug_data);
        return $bytes && is_file($full_path) && is_readable($full_path);
    }

    /**
     * Convert url path to file path
     *
     * @param string $url full url e.g. https://example.com/.well-known/.....
     *
     * @return string
     */
    public static function url2file(string $url): string
    {
        $path_parts = static::getUrlPathParts($url);
        return \ABSPATH.implode(DIRECTORY_SEPARATOR, $path_parts);
    }

    /**
     * Split url and sanize parts
     *
     * @param string $url http url
     *
     * @return array
     */
    protected static function getUrlPathParts(string $url): array
    {
        $parsed = parse_url($url);
        $path = SafeAccess::fromArray($parsed, "path", "string", null);
        if (empty($path) || $path == '/') {
            throw new Exception("Url path cannot be empty");
        }

        $path_parts = array_values(array_filter(explode("/", $path))); // remove first and last slashes
        if (!preg_match("/well-known/", $path_parts[0])) {
            throw new Exception("The path must start with '.well-known'");
        }

        return $path_parts;
    }

    /**
     * Replace Clickio block in .htaccess
     *
     * @return void
     */
    protected static function updateHtaccess()
    {
        $htaccess_file = apply_filters("_clickio_htaccess_file_path", \ABSPATH."/.htaccess");
        if (empty($htaccess_file)) {
            return ;
        }

        $htaccess = '';
        if (@is_file($htaccess_file) && @is_readable($htaccess_file)) {
            $htaccess = file_get_contents($htaccess_file);
        }
        $sign_start = static::HTACCESS_SIGN_START;
        $sign_end = static::HTACCESS_SIGN_END;

        $htaccess = preg_replace("/$sign_start.*$sign_end".PHP_EOL."/s", "", $htaccess);

        $challenges = static::getRawList();
        $content = "";
        if (!empty($challenges)) {
            $content .= $sign_start.PHP_EOL;
            $content .= "<IfModule mod_rewrite.c>".PHP_EOL;
            foreach ($challenges as $challenge) {
                $path_parts = static::getUrlPathParts($challenge['url']);
                $rule = implode('/', $path_parts);
                $content .= '    RewriteRule ^'.$rule.'$ - [L,NC]'.PHP_EOL;
            }
            $content .= "</IfModule>".PHP_EOL;
            $content .= $sign_end.PHP_EOL;
        }

        $htaccess = $content.$htaccess;
        file_put_contents($htaccess_file, $htaccess);
    }

    /**
     * Add rewrite rule and set up a listener
     *
     * @return void
     */
    public static function setupRewrite()
    {
        $list = static::getRawList();
        $listener_active = apply_filters('_clickio_setup_rewrite', true);
        if (empty($list) || !$listener_active) {
            return ;
        }

        $url_re = "(\.{0,1}well-known)\/([a-zA-Z0-9_\-]+)\/([a-zA-Z0-9_\-]+)$";
        $target = 'index.php?acme_prefix=$matches[1]&acme_folder=$matches[2]&acme_file=$matches[3]';
        $place = 'top';
        add_rewrite_rule($url_re, $target, $place);

        add_filter(
            'query_vars',
            function ($vars) {
                $vars[] = 'acme_prefix';
                $vars[] = 'acme_folder';
                $vars[] = 'acme_file';
                return $vars;
            }
        );

        add_action("wp", [static::class, 'acmeCallback'], 0);
    }

    /**
     * Rewrite rule callback
     *
     * @return void
     */
    public static function acmeCallback()
    {
        $acme_prefix = get_query_var('acme_prefix');
        $acme_folder = get_query_var('acme_folder');
        $acme_file = get_query_var('acme_file');
        if (empty($acme_prefix) || empty($acme_folder) || empty($acme_file)) {
            return ;
        }

        $list = static::getRawList();
        $url = implode('\/', [$acme_prefix, $acme_folder, $acme_file]);
        foreach ($list as $acme) {
            if (preg_match("/$url/", $acme['url'])) {

                $w3total = IntegrationServiceFactory::getService('w3total');
                $w3total::disable();

                header("Content-type: application/octet-stream", true);
                header("Content-length: ".strlen($acme['body']), true);
                echo $acme['body'];
                exit(0);
            }
        }
    }
}
