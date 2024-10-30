<?php

/**
 * Locale utils
 */

namespace Clickio\Utils;

/**
 * Locale utils
 *
 * @package Utils
 */
class Locale
{

    protected static $locales = [
        'af_ZA', // Afrikaans.
        'ak_GH', // Akan.
        'am_ET', // Amharic.
        'ar_AR', // Arabic.
        'as_IN', // Assamese.
        'ay_BO', // Aymara.
        'az_AZ', // Azerbaijani.
        'be_BY', // Belarusian.
        'bg_BG', // Bulgarian.
        'bp_IN', // Bhojpuri.
        'bn_IN', // Bengali.
        'br_FR', // Breton.
        'bs_BA', // Bosnian.
        'ca_ES', // Catalan.
        'cb_IQ', // Sorani Kurdish.
        'ck_US', // Cherokee.
        'co_FR', // Corsican.
        'cs_CZ', // Czech.
        'cx_PH', // Cebuano.
        'cy_GB', // Welsh.
        'da_DK', // Danish.
        'de_DE', // German.
        'el_GR', // Greek.
        'en_GB', // English (UK).
        'en_PI', // English (Pirate).
        'en_UD', // English (Upside Down).
        'en_US', // English (US).
        'em_ZM',
        'eo_EO', // Esperanto.
        'es_ES', // Spanish (Spain).
        'es_LA', // Spanish.
        'es_MX', // Spanish (Mexico).
        'et_EE', // Estonian.
        'eu_ES', // Basque.
        'fa_IR', // Persian.
        'fb_LT', // Leet Speak.
        'ff_NG', // Fulah.
        'fi_FI', // Finnish.
        'fo_FO', // Faroese.
        'fr_CA', // French (Canada).
        'fr_FR', // French (France).
        'fy_NL', // Frisian.
        'ga_IE', // Irish.
        'gl_ES', // Galician.
        'gn_PY', // Guarani.
        'gu_IN', // Gujarati.
        'gx_GR', // Classical Greek.
        'ha_NG', // Hausa.
        'he_IL', // Hebrew.
        'hi_IN', // Hindi.
        'hr_HR', // Croatian.
        'hu_HU', // Hungarian.
        'ht_HT', // Haitian Creole.
        'hy_AM', // Armenian.
        'id_ID', // Indonesian.
        'ig_NG', // Igbo.
        'is_IS', // Icelandic.
        'it_IT', // Italian.
        'ik_US',
        'iu_CA',
        'ja_JP', // Japanese.
        'ja_KS', // Japanese (Kansai).
        'jv_ID', // Javanese.
        'ka_GE', // Georgian.
        'kk_KZ', // Kazakh.
        'km_KH', // Khmer.
        'kn_IN', // Kannada.
        'ko_KR', // Korean.
        'ks_IN', // Kashmiri.
        'ku_TR', // Kurdish (Kurmanji).
        'ky_KG', // Kyrgyz.
        'la_VA', // Latin.
        'lg_UG', // Ganda.
        'li_NL', // Limburgish.
        'ln_CD', // Lingala.
        'lo_LA', // Lao.
        'lt_LT', // Lithuanian.
        'lv_LV', // Latvian.
        'mg_MG', // Malagasy.
        'mi_NZ', // Maori.
        'mk_MK', // Macedonian.
        'ml_IN', // Malayalam.
        'mn_MN', // Mongolian.
        'mr_IN', // Marathi.
        'ms_MY', // Malay.
        'mt_MT', // Maltese.
        'my_MM', // Burmese.
        'nb_NO', // Norwegian (bokmal).
        'nd_ZW', // Ndebele.
        'ne_NP', // Nepali.
        'nl_BE', // Dutch (Belgie).
        'nl_NL', // Dutch.
        'nn_NO', // Norwegian (nynorsk).
        'nr_ZA', // Southern Ndebele.
        'ns_ZA', // Northern Sotho.
        'ny_MW', // Chewa.
        'om_ET', // Oromo.
        'or_IN', // Oriya.
        'pa_IN', // Punjabi.
        'pl_PL', // Polish.
        'ps_AF', // Pashto.
        'pt_BR', // Portuguese (Brazil).
        'pt_PT', // Portuguese (Portugal).
        'qc_GT', // Quiché.
        'qu_PE', // Quechua.
        'qr_GR',
        'qz_MM', // Burmese (Zawgyi).
        'rm_CH', // Romansh.
        'ro_RO', // Romanian.
        'ru_RU', // Russian.
        'rw_RW', // Kinyarwanda.
        'sa_IN', // Sanskrit.
        'sc_IT', // Sardinian.
        'se_NO', // Northern Sami.
        'si_LK', // Sinhala.
        'su_ID', // Sundanese.
        'sk_SK', // Slovak.
        'sl_SI', // Slovenian.
        'sn_ZW', // Shona.
        'so_SO', // Somali.
        'sq_AL', // Albanian.
        'sr_RS', // Serbian.
        'ss_SZ', // Swazi.
        'st_ZA', // Southern Sotho.
        'sv_SE', // Swedish.
        'sw_KE', // Swahili.
        'sy_SY', // Syriac.
        'sz_PL', // Silesian.
        'ta_IN', // Tamil.
        'te_IN', // Telugu.
        'tg_TJ', // Tajik.
        'th_TH', // Thai.
        'tk_TM', // Turkmen.
        'tl_PH', // Filipino.
        'tl_ST', // Klingon.
        'tn_BW', // Tswana.
        'tr_TR', // Turkish.
        'ts_ZA', // Tsonga.
        'tt_RU', // Tatar.
        'tz_MA', // Tamazight.
        'uk_UA', // Ukrainian.
        'ur_PK', // Urdu.
        'uz_UZ', // Uzbek.
        've_ZA', // Venda.
        'vi_VN', // Vietnamese.
        'wo_SN', // Wolof.
        'xh_ZA', // Xhosa.
        'yi_DE', // Yiddish.
        'yo_NG', // Yoruba.
        'zh_CN', // Simplified Chinese (China).
        'zh_HK', // Traditional Chinese (Hong Kong).
        'zh_TW', // Traditional Chinese (Taiwan).
        'zu_ZA', // Zulu.
        'zz_TR', // Zazaki.
    ];

    /**
     * Current locale e.g. en_US or fr_FR
     *
     * @return string
     */
    public static function getCurrentLocale(): string
    {
        $locale = get_locale();
        return static::normalizeLocale($locale);
    }

    /**
     * Convert locale string to locale slug e.g. en-US
     *
     * @param string $locale locale string like en_US or en-US
     *
     * @return string
     */
    public static function localeToSlug(string $locale): string
    {
        if (strlen($locale) == 2) {
            return $locale;
        }

        // if dashed locale string like en-US
        if (preg_match("/\w{2}-\w{2}/", $locale)) {
            $locale = str_replace("-", "_", $locale);
        }

        $exploded = explode("_", $locale);
        if (count($exploded) >= 2) {
            $slug = SafeAccess::fromArray($exploded, 0, 'string', '');
            return strtolower($slug);
        }
        return $locale;
    }

    /**
     * Get locale by slug
     *
     * @param string $slug locale slug e.g. example.com/de/article-name
     *
     * @return string
     */
    public static function getLocaleBySlug(string $slug): string
    {
        $locale = $slug;
        foreach (static::$locales as $locale_string) {
            if (preg_match("/".$slug."_/i", $locale_string)) {
                $locale = $locale_string;
                break;
            }
        }
        return $locale;
    }

    /**
     * Fix broken locale
     *
     * @param string $locale locale string
     *
     * @return string
     */
    public static function normalizeLocale(string $locale): string
    {
        $fix_locales = [
            'ca' => 'ca_ES',
            'en' => 'en_US',
            'el' => 'el_GR',
            'et' => 'et_EE',
            'ja' => 'ja_JP',
            'sq' => 'sq_AL',
            'uk' => 'uk_UA',
            'vi' => 'vi_VN',
            'zh' => 'zh_CN',
            'bel' => 'be_BY',
            'vn' => 'vi_VN'
        ];

        if (SafeAccess::arrayKeyExists($locale, $fix_locales)) {
            $locale = $fix_locales[$locale];
        }

        // Convert locales like "es" to "es_ES", in case that works for the given locale (sometimes it does).
        if (strlen($locale) === 2) {
            $locale = sprintf("%s_%s", strtolower($locale), strtoupper($locale));
        }


        // Check to see if the locale is a valid FB one, if not, use en_US as a fallback.
        if (!in_array($locale, static::$locales, true)) {
            $parts = explode('_', $locale);
            $first = SafeAccess::fromArray($parts, 0, 'string', 'en');
            $second = SafeAccess::fromArray($parts, 1, 'string', 'US');

            $locale = sprintf("%s_%s", strtolower($first), strtoupper($second));
            if (!in_array($locale, static::$locales, true)) {
                $locale = 'en_US';
            }
        }
        return $locale;
    }
}
