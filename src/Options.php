<?php
/**
 * WP options wrapper
 */

namespace Clickio;

use Clickio\Logger\Interfaces\ILogger;
use Clickio\Logger\LoggerAccess;
use Clickio\Prism\Cache\CacheManager;
use Clickio\Request\Request;
use Clickio\Utils\SafeAccess;

/**
 * Wrapper around WP options
 *
 * @package Clickio
 */
class Options
{
    use LoggerAccess;
    /**
     * External settings endpoint
     *
     * @var string
     */
    const PLATFORM_ENDPOINT = "https://platform.clickio.com/PublicRestApi/getPluginSettings";

    /**
     * Application key endpoint
     *
     * @var string
     */
    const APPKEY_SETTER = "https://platform.clickio.com/PublicRestApi/setApplicationKey";

    /**
     * Options backup endpoint
     *
     * @var string
     */
    const OPTIONS_BACKUP = "https://platform.clickio.com/PublicRestApi/setPluginSettings";

    /**
     * Options
     *
     * @var array
     */
    protected static $opt = [];

    protected static $ignore_restore = [
        'addons',
        'acme_challenge'
    ];

    protected static $remoteOptions = [
        "plugin_advanced_mode",
        "integration_scheme",
        "use_amp_on_policy_pages",
        "status",
        "amp_status",
        "enable_seo",
        "disable_pages",
        "extra_sources",
        "pageinfo_config",
        "purge_canonical",
        "log_widgets",
        "disable_auth_redirect",
        "force_extra",
        "force_full_content",
        "words_per_minute",
        "prism_version_rotation_percent",
        "deffered_purge",
        "replace_callback",
        "uri_strip_last_slash",
        "wp_cache_lists",
        "site_id",
        "feed_enabled",
        "addons",
        "websub_enabled",
        "cache_warmup_enabled",
        "ignore",
        "domain",
        "type",
        "amp_url",
        "useamp",
        "allow_styles",
        "allow_indexing",
//        "co_author",
    ];

    /**
     * Default config
     *
     * @var array
     */
    protected static $defaults = [
        // Clickio options
        "plugin_advanced_mode" => 0,
        "integration_scheme" => 'dns',
        "use_amp_on_policy_pages" => 0,
        "status" => "disabled",
        "amp_status" => "disabled",
        "enable_seo" => 0,
        "disable_pages" => 0,
        "extra_sources" => [],
        "pageinfo_config" => "",
        "purge_canonical" => 0,
        "log_widgets" => 0,
        "disable_auth_redirect" => 0,
        "force_extra" => 0,
        "force_full_content" => 0,
        "words_per_minute" => 300,
        "prism_version_rotation_percent" => 0,
        "deffered_purge" => 0,
        "replace_callback" => '',
        "uri_strip_last_slash" => 1,
        "wp_cache_lists" => 0,
        "site_id" => null,
        "feed_enabled" => 0,
        "websub_enabled" => 0,
        "cache_warmup_enabled" => 1,
        "ignore" => "",
        "type" => 'ampfolder',
        "domain" => "",
        "amp_url" => "/amp",
        "useamp" => 0,
        "allow_styles" => 0,
        "allow_indexing" => 0,

        // plugin options
        "mobile" => 0,
        "customtypes" => "",
        "posts" => 0,
        "pages" => 0,
        "redir" => 0,
        "is_debug" => 0,
        "cleaners" => ["ClickIoCDN"],
        "local_cache" => 0,
        "daemon_version" => "Master",
        "extra_content" => 0,
        "extra_actions" => [],
        "extra_widgets" => [],
        "extra_fields" => [],
        "extra_shortcodes" => [],
        "extra_custom_actions" => "",
        "db_version" => 0,
        "settings_version" => 1,
        "log_level" => ILogger::LOGGER_ON,
        "cache" => 0,
        "cache_lifetime" => 3600,
        "addons" => [],
        "acme_challenge" => [],
        "co_author" => '',
    ];

    /**
     * PWA hosts map
     *
     * @var array
     */
    protected static $pwa_hosts = [
        "Develop" => "pwatest.clickiocdn.com",
        "Release" => "prism-release.clickiocdn.com",
        "Master" => "pwa.clickiocdn.com"
    ];

    /**
     * AMP hosts map
     *
     * @var array
     */
    protected static $amp_hosts = [
        "Develop" => "amptest.clickiocdn.com",
        "Release" => "amprelease.clickiocdn.com",
        "Master" => "amp.clickiocdn.com"
    ];

    /**
     * WP options key
     *
     * @var string
     */
    const OPT_KEY = "clickio_opt";

    /**
     * Application key
     *
     * @var string
     */
    const APP_KEY = 'clickio_appkey';

    /**
     * Singletone
     * Prevent creating Options
     */
    protected function __construct()
    {

    }

    /**
     * Get all options
     *
     * @return array
     */
    public static function getOptions()
    {
        if (empty(static::$opt)) {
            static::loadOptions();
        }

        return static::$opt;
    }

    /**
     * Get single option
     *
     * @param string $name option key
     * @param mixed $default default value if $name doesn't exists
     *
     * @return mixed
     */
    public static function get(string $name, $default = '')
    {
        if (empty($default)) {
            $default = static::$defaults[$name];
        }

        if (empty(static::$opt)) {
            static::loadOptions();
        }

        if (array_key_exists($name, static::$opt)) {
            return static::$opt[$name];
        }
        return $default;
    }

    /**
     * Update sigle option
     *
     * @param string $name option key
     * @param mixed $value new value
     *
     * @return void
     */
    public static function set(string $name, $value)
    {
        if (empty(static::$opt)) {
            static::loadOptions();
        }

        static::$opt[$name] = $value;
    }

    /**
     * Update options from key => value array
     * where key is option name and value is options value
     *
     * @param array $values options array
     *
     * @return void
     */
    public static function replaceOptions(array $values)
    {
        foreach (array_keys(static::$defaults) as $opt_name) {
            if (array_key_exists($opt_name, $values)) {
                static::$opt[$opt_name] = $values[$opt_name];
            } else {
                static::$opt[$opt_name] = static::$defaults[$opt_name];
            }
        }
    }

    /**
     * Save current options
     *
     * @return void
     */
    public static function save()
    {
        CacheManager::exportConfig();
        update_option(static::OPT_KEY, static::$opt);
    }

    /**
     * Load local options
     *
     * @return void
     */
    public static function loadOptions()
    {
        $db_options = get_option(static::OPT_KEY, []);
        if (!empty($db_options)) {
            static::replaceOptions($db_options);
        }
    }

    /**
     * Getting pwa hosts by daemon version
     *
     * @param string $daemon daemon name
     *
     * @return string
     */
    public static function getPwaHost(string $daemon = ''): string
    {
        if (empty($daemon)) {
            $daemon = static::get("daemon_version", "Master");
        }

        if (array_key_exists($daemon, static::$pwa_hosts)) {
            return static::$pwa_hosts[$daemon];
        }
        return "";
    }

    /**
     * Getting amp hosts by daemon version
     *
     * @return string
     */
    public static function getAmpHost(): string
    {
        $daemon = static::get("daemon_version", "Master");
        if (array_key_exists($daemon, static::$amp_hosts)) {
            return static::$amp_hosts[$daemon];
        }
        return "";
    }

    /**
     * Fires after options page was saved
     *
     * @param array $options settings page values
     *
     * @return array
     */
    public static function validateOptions(array $options)
    {
        if (array_key_exists('domain', $options) && $options['domain']) {
            $new = wp_parse_url($options['domain'], PHP_URL_HOST);
            if ($new) {
                $options['domain'] = $new;
            }
        }

        $version = static::get('settings_version');
        $version++;
        $options['settings_version'] = $version;

        foreach (static::$opt as $opt_name => $opt_value) {
            if (in_array($opt_name, static::$remoteOptions)) {
                $options[$opt_name] = $opt_value;
                continue ;
            }

            if (in_array($opt_name, static::$ignore_restore)) {
                $options[$opt_name] = $opt_value;
            }
        }

        static::replaceOptions($options);
        static::backupOptions();
        CacheManager::exportConfig();
        return static::getOptions();
    }

    /**
     * Application key setter
     *
     * @param string $key app key
     *
     * @return void
     */
    public static function setApplicationKey(string $key)
    {
        \update_option(static::APP_KEY, $key);

        // for a some reason non blocking request not sending after plugin activation
        $args = [
            'timeout'   => 2,
            'blocking'  => true,
            'sslverify' => false,
            'user-agent' => 'WP',
            'body' => [
                'appkey' => $key,
                'domain' => SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'default')
            ]
        ];
        try {
            \wp_remote_post(static::APPKEY_SETTER, $args);
        } catch (\Exception $err) {
            static::logError("Unable to set application key. ".$err->getMessage());
        }
    }

    /**
     * Application key getter
     *
     * @return string
     */
    public static function getApplicationKey(): string
    {
        return get_option(static::APP_KEY, '');
    }

    /**
     * Retrieve remote options
     *
     * @param string $domain connected to prism site
     *
     * @return void
     */
    public static function loadRemoteOptions(string $domain = '')
    {
        $remote = static::getRemoteOptions($domain);
        $debug = static::get("is_debug");
        if (!empty($remote)) {
            foreach ($remote as $opt_name => $opt_val) {

                if (in_array($opt_name, static::$ignore_restore)) {
                    continue ;
                }

                if (!empty($debug) && !in_array($opt_name, static::$remoteOptions)) {
                    continue ;
                }

                if (array_key_exists($opt_name, static::$defaults)) {
                    if ($opt_name == 'extra_sources' && !empty($opt_val)) {
                        static::$opt["extra_content"] = 1;
                    }
                    static::$opt[$opt_name] = $opt_val;
                }
            }
        }
    }

    /**
     * Get options from clickio server
     *
     * @param string $domain current domain name
     *
     * @return array
     */
    protected static function getRemoteOptions(string $domain = ''): array
    {
        if (empty($domain)) {
            $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        }

        $query = ["domain" => $domain];
        $req = Request::create();
        try {
            $resp = $req->get(static::PLATFORM_ENDPOINT, $query);
        } catch (\Exception $err) {
            static::logError("Error while loding remote options: {$err->getMessage()}");
            return [];
        }

        $body = $resp->body;
        if (is_string($body)) {
            static::logError("Remote options is not an array.\n\n$body");
            return [];
        }

        if (empty($body)) {
            $body = [];
        }
        return $body;
    }

    /**
     * Backup options
     *
     * @return void
     */
    public static function backupOptions()
    {
        $options = static::getOptions();
        $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        static::logDebug("Starting options backup", ["url" => self::OPTIONS_BACKUP, "opt" => $options]);
        $req = Request::create(['timeout' => 2]);
        try {
            $req->post(self::OPTIONS_BACKUP, $options, ['domain' => $domain]);
        } catch (\Exception $err) {
            static::logError("Error when making options backup: {$err->getMessage()}");
        }
    }
}
