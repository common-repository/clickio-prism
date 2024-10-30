<?php

/**
 * Custom post fields
 */

namespace Clickio\ExtraContent\Services;

use Clickio\ExtraContent\Interfaces\IExtraContentService;
use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Meta\PostMeta;
use Clickio\Options;
use Clickio\PageInfo\RulesManager;
use Clickio\Utils\LocationType;
use Clickio\Utils\OEmbed;
use Clickio\Utils\SafeAccess;

/**
 * Custom fields content
 *
 * @package ExtraContent\Services
 */
class FieldsContent extends ContentServiceBase implements IExtraContentService
{
    /**
     * Service uniq name
     *
     * @var string
     */
    const NAME = 'fields';

    /**
     * Key in Options
     *
     * @var string
     */
    const OPT_KEY = "extra_fields";

    /**
     * Label for settings page
     *
     * @var string
     */
    const LABEL = "Custom Fields";

    /**
     * Custom fields list
     *
     * @var array
     */
    private static $_custom_fields = [];

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

        if (!LocationType::isPost() && !LocationType::isHome()) {
            return [];
        }

        wp_reset_postdata();
        wp_reset_query();
        $post = get_post(null, 'OBJECT', 'edit');
        if (empty($post)) {
            return [];
        }
        $meta = get_post_meta($post->ID);
        $custom = Options::get(static::OPT_KEY);
        $sources = Options::get('extra_sources');
        foreach ($sources as $src) {
            $exploded = static::extractSourceId($src);
            if (empty($exploded)) {
                continue ;
            }
            $custom[] = $exploded[1];
        }
        $custom = array_filter($custom);
        $extra = [];
        foreach ($meta as $meta_name => $meta_value) {
            if (strpos($meta_name, '_') === 0 || empty($meta_value)) {
                continue ;
            }

            if (in_array($meta_name, $custom) || $force) {
                // if field has custom rules, skip it.
                if (array_key_exists($meta_name, $this->rules)) {
                    continue ;
                }
                $val = array_shift($meta_value);
                if (empty($val)) {
                    $val = "";
                }

                if (!is_string($val)) {
                    $val = var_export($val, true);
                }
                $extra[$meta_name] = $this->prepareValue($val);
            }
        }

        // fill fields with custom rules
        foreach ($this->rules as $rule_field => $rule) {
            $val = $this->applyRule($rule, $post->ID);

            if (empty($val)) {
                $extra[$rule_field] = ["content" => "", "raw" => ""];
                continue ;
            }

            if (is_array($val)) {
                $first_key = array_keys($val)[0];
                $val = $val[$first_key];
            }

            if (in_array($rule_field, $custom) || $force) {
                $extra[$rule_field] = $this->prepareValue($val);
            }
        }

        $oembed = OEmbed::getInstance();
        $custom_video_html = $oembed->getEmbedHtml($post->ID);
        $custom_video_raw = $oembed->getOembedUrl($post->ID);
        if (!empty($custom_video_raw)) {
            $extra['_extra_fields_video'] = ["content" => $custom_video_html, "raw" => $custom_video_raw];
        }

        if (LocationType::isHome()) {
            $front_content = $this->getMainPageContent();
            $extra['_home_page_content'] = ["content" => $front_content, "raw" => ""];
        }

        $acf_fields = $this->getACFObjects($post->ID);
        $extra = array_merge($extra, $acf_fields);

        return $extra;
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
        $parts = \explode(".", $source_id);
        if (empty($parts) || $parts[0] != 'fields') {
            return [];
        }

        return $parts;
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
        $fields = $this->getCustomFields();
        $settings_fields = [];
        foreach ($fields as $fld) {
            $settings_fields[$fld] = sprintf("<b>%s</b>", $fld);
        }

        return $settings_fields;
    }

    /**
     * Get all custom fields
     *
     * @return array
     */
    protected function getCustomFields(): array
    {
        if (empty(static::$_custom_fields)) {
            global $wpdb;
            $q="SELECT meta_key FROM wp_postmeta WHERE substring(meta_key, 1,1) != '_' GROUP BY meta_key;";
            static::$_custom_fields = $wpdb->get_col($q);
        }
        return static::$_custom_fields;
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

    /**
     * Apply rule to field
     *
     * @param array $rule field rule
     * @param int $post_id post id
     *
     * @return mixed
     */
    protected function applyRule(array $rule, int $post_id)
    {
        $rule['post_id'] = $post_id;
        return RulesManager::apply($rule);
    }

    /**
     * Prepare value
     * Check if value require filter to apply and wrap it into array
     *
     * @param string $value value to be prepared
     *
     * @return array
     */
    protected function prepareValue(string $value): array
    {
        $filtered = $value;
        if (strlen($value) > 15) {
            $filtered = apply_filters("the_content", $value);
        }
        return ["content" => $filtered, "raw" => $value];
    }

    /**
     * Get main page content if it's a Page
     *
     * @return string
     */
    protected function getMainPageContent(): string
    {
        $show_on_front = get_option("show_on_front");
        $front_page = get_option("page_on_front");
        if ($show_on_front == 'page' && !empty($front_page)) {
            $page = get_post($front_page);
            $field_content = get_post_field('post_content', $page->ID);
            $content = apply_filters('the_content', $field_content);
            if (empty($content)) {
                $content = '';
            }
            return $content;
        }
        return '';
    }

    /**
     * Format ACF fields
     *
     * @param int $post_id post id
     *
     * @return array
     */
    protected function getACFObjects(int $post_id): array
    {
        $prefix = "acf_";
        $extra = [];

        $acf = IntegrationServiceFactory::getService('acf');
        $fields = $acf::getPostFields($post_id);
        foreach ($fields as $field_name => $fld) {
            $type = SafeAccess::fromArray($fld, 'type', 'string', '');
            $mult = SafeAccess::fromArray($fld, 'multiple', 'integer', 0);
            $value = SafeAccess::fromArray($fld, 'value', 'array', []);
            $link_fld_name = sprintf("%s%s", $prefix, $field_name);
            if ($type == 'post_object' && $mult && !empty($value)) {
                $links = [];
                $titles = [];
                foreach ($fld['value'] as $post_obj) {
                    $post = new PostMeta($post_obj);

                    $template = '<a class="extra_field_link" href="%s">%s</a>';
                    $links[] = sprintf($template, $post->getPermalink(), $post->getPost()->post_title);
                    $titles[] = $post->getPost()->post_title;

                    // $extra[$link_fld_name] = $post->getPermalink();
                    // $extra[$title_fld_name] = $post->getPost()->post_title;
                }
                if (!empty($links)) {
                    $extra[$link_fld_name] = [
                        "content" => '<div class="extra_links">'.implode('', $links)."</div>",
                        "raw" => implode(",", $titles)
                    ];
                }
            }
        }
        return $extra;
    }
}
