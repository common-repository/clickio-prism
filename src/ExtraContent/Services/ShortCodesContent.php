<?php

/**
 *  Content from short-code
 */

namespace Clickio\ExtraContent\Services;

use Clickio\ExtraContent\Interfaces\IExtraContentService;
use Clickio\Options;
use Clickio\Utils\LocationType;
use Clickio\Utils\SafeAccess;

/**
 * Collect data from short-codes
 *
 * @package ExtraContent\Services
 */
class ShortCodesContent extends ContentServiceBase implements IExtraContentService
{

    /**
     * Label for settings page
     *
     * @var string
     */
    const LABEL = "Shortcode";

    /**
     * Service uniq name
     *
     * @var string
     */
    const NAME = 'shortcode';

    /**
     * Where service store own settings
     *
     * @var string
     */
    const OPT_KEY = 'extra_shortcodes';

    /**
     * Widgets Extra content
     *
     * @var array
     */
    protected $extra = [];

    /**
     * Service rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Entry point
     * Get extra content
     *
     * @param bool $force ignore settings
     *
     * @return array
     */
    public function getExtraContent(bool $force = false): array
    {
        if (!LocationType::isPost()) {
            return [];
        }

        $this->collectInfo($force);
        return $this->extra;
    }

    /**
     * Get options key
     *
     * @return string
     */
    public function getOptionsContainer(): string
    {
        return static::OPT_KEY;
    }

    /**
     * Get extra content source
     *
     * @return array
     */
    public function getExtraContentSource(): array
    {
        global $shortcode_tags;
        $settings_page = [];
        foreach ($shortcode_tags as $code => $callback) {
            $settings_page[$code] = sprintf("<b>%s</b>", $code);
        }
        return $settings_page;
    }

    /**
     * Collect data
     *
     * @param bool $force ignore selected
     *
     * @return void
     */
    protected function collectInfo(bool $force = false)
    {
        $codes = $this->getShortCodesList($force);

        foreach ($codes as $code_struct) {
            $code = SafeAccess::fromArray($code_struct, 'code', 'string', '');
            $code_tag = SafeAccess::fromArray($code_struct, 'tag', 'string', '');
            $callback = SafeAccess::fromArray($code_struct, 'callback', 'array', []);

            if (empty($callback)) {
                continue ;
            }
            if (!SafeAccess::arrayKeyExists($code_tag, $this->extra)) {
                $this->extra[$code_tag] = [];
            }

            try {
                $content = do_shortcode($code);
            } catch (\Exception $e) {
                continue ;
            }

            $this->extra[$code_tag][] = $content;
        }
    }

    /**
     * Extract shortcodes from content
     *
     * @param bool $force ignore selected
     *
     * @return array
     */
    protected function getShortCodesList(bool $force = false): array
    {
        global $shortcode_tags;

        $post = get_post(null, 'OBJECT', 'edit');

        if (!is_object($post) || empty($post) || empty($post->post_content)) {
            return [];
        }

        $content = $post->post_content;

        $codes = [];
        $regex = sprintf('/%s/', get_shortcode_regex());
        @preg_match_all($regex, $content, $matches, PREG_SET_ORDER);

        if (!is_array($matches)) {
            return $codes;
        }
        $selected = Options::get(static::OPT_KEY);

        $sources_raw = Options::get("extra_sources");

        $sources = array_map(
            function ($item) {
                $extracted = $this->extractSourceId($item);
                array_pop($extracted); // extract number
                return array_pop($extracted);
            },
            $sources_raw
        );

        $selected = array_merge($selected, $sources);

        foreach ($matches as $match) {
            $code_struct = [];
            $code = SafeAccess::fromArray($match, 0, 'string', '');

            $shortcode_tag = SafeAccess::fromArray($match, 2, 'string', '');
            if (!in_array($shortcode_tag, $selected) && !$force) {
                continue ;
            }


            $callback = SafeAccess::fromArray($shortcode_tags, $shortcode_tag, 'array', []);
            if (empty($callback)) {
                $callback = SafeAccess::fromArray($shortcode_tags, $shortcode_tag, 'string', '');
                if (!empty($callback)) {
                    $callback = [$callback];
                } else {
                    continue ;
                }
            }

            if (!empty($code) && !empty($callback)) {
                $code_struct['code'] = $code;
                $code_struct['callback'] = $callback;
                $code_struct['tag'] = $shortcode_tag;
            }

            $codes[] = $code_struct;
        }
        return $codes;
    }

    /**
     * Parse source id
     *
     * @param string $source_id source id
     *
     * @return array
     */
    public static function extractSourceId(string $source_id): array
    {
        $parts = explode(".", $source_id);
        if (empty($parts) || $parts[0] != 'shortcode') {
            return [];
        }

        return $parts;
    }

    /**
     * Setter
     * Set service rules
     *
     * @param array $rules service rules
     *
     * @return void
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }
}
