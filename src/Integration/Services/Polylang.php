<?php

/**
 * Polylang
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\Locale;
use Clickio\Utils\Plugins;

/**
 * Integration with Polylang
 *
 * @package Integration\Services
 */
final class Polylang extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'polylang/polylang.php';

    /**
     * Premium version
     *
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PREMIUM_PLUGIN_ID = 'polylang-pro/polylang.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'polylang';

    /**
     * Get list of plugins with which the service can integrate
     *
     * @return array
     */
    protected static function getIntegrationList(): array
    {
        return [static::PLUGIN_ID, static::PREMIUM_PLUGIN_ID];
    }

    /**
     * Normalize code to match pll code
     *
     * @param string $code language code
     *
     * @return string
     */
    public static function normalizeLangCode(string $code): string
    {
        if (!static::integration() || !function_exists("pll_languages_list")) {
            return $code;
        }

        $pll_code = static::_searchPllCode($code);
        if (!empty($pll_code)) {
            return $pll_code;
        }

        $locale = Locale::getLocaleBySlug($code);
        $exploded = explode("_", strtolower($locale));
        if (count($exploded) != 2) {
            return $code;
        }

        $fixed_code = static::_searchPllCode($exploded[0]);
        if (empty($fixed_code)) {
            $fixed_code = static::_searchPllCode($exploded[1]);
            if (empty($fixed_code)) {
                $fixed_code = $code;
            }
        }

        return $fixed_code;
    }

    /**
     * Compare pll code with requested
     *
     * @param string $code language code
     *
     * @return string
     */
    private static function _searchPllCode(string $code): string
    {
        $slug_list = \pll_languages_list(["fields" => 'slug']);
        $fixed_code = '';
        foreach ($slug_list as $slug) {
            if ($slug == $code) {
                $fixed_code = $slug;
                break ;
            }
        }

        return $fixed_code;
    }

    /**
     * Disable plugin for a single request
     *
     * @param int $postid WP_Post->ID
     *
     * @return void
     */
    public static function getPostLocale(int $postid): string
    {
        if (!static::integration() || !function_exists("pll_get_post_language")) {
            return Locale::getCurrentLocale();
        }
        $locale =\pll_get_post_language($postid, 'locale');
        return Locale::normalizeLocale($locale);
    }

    /**
     * Get links to post translation
     *
     * @param string int $post_id $post->ID
     *
     * @return array
     */
    public static function getPostTranslations(int $post_id): array
    {
        if (!static::integration()
            || !function_exists("pll_get_post")
            || !function_exists('pll_languages_list')
            || !function_exists('pll_get_post_language')
        ) {
            return [];
        }

        $current_lang = \pll_get_post_language($post_id, 'slug');
        $lang_list = \pll_languages_list();

        $posts = [];
        foreach ($lang_list as $lang) {
            if ($lang == $current_lang) {
                continue ;
            }

            $translated_post_id = \pll_get_post($post_id, $lang);
            if (empty($translated_post_id)) {
                continue ;
            }
            $locale = \pll_get_post_language($translated_post_id, 'locale');
            $locale = Locale::normalizeLocale($locale);
            $struct = [
                "locale" => str_replace("_", "-", $locale),
                "url" => get_permalink($translated_post_id)
            ];
            $posts[] = $struct;
        }
        return $posts;
    }

    /**
     * Get all languages
     *
     * @return array
     */
    public static function getLanguageList(): array
    {
        if (!static::integration() || !function_exists('pll_languages_list')) {
            return [];
        }

        $languages = \pll_languages_list(['fields' => 'locale']);
        $languages = array_map(
            function ($el) {
                $el = Locale::normalizeLocale($el);
                return str_replace("_", '-', $el);
            },
            $languages
        );
        return $languages;
    }

    /**
     * Disable redirects to canonical urls
     *
     * @return void
     */
    public static function disableCanonicalRedirect()
    {
        add_filter('pll_check_canonical_url', '__return_false');
    }
}
