<?php

/**
 * Plugins data
 */

namespace Clickio\ExtraContent\Services;

use Clickio\ExtraContent\Interfaces\IExtraContentService;
use Clickio\ExtraContent\Services\ContentServiceBase;
use Clickio\Options;
use Clickio\Utils\Plugins;
use Clickio\Utils\SafeAccess;
use Clickio\Utils\Widgets;
use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;
use WP_Widget;

/**
 * Collect plugins data
 *
 * @package ExtraContent
 */
class PluginsContent extends ContentServiceBase implements IExtraContentService
{

    /**
     * Label for settings page
     *
     * @var string
     */
    const LABEL = "Plugins";

    /**
     * Service uniq name
     *
     * @var string
     */
    const NAME = 'plugins';

    /**
     * Service rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Plugins list to collect data
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * Collected content items
     *
     * @var array
     */
    protected $content = [];

    /**
     * Get options key
     *
     * @return string
     */
    public function getOptionsContainer(): string
    {
        return '';
    }

    /**
     * Get collected content
     *
     * @param array $force ignore settings
     *
     * @return array
     */
    public function getExtraContent(bool $force = false): array
    {
        $sources = Options::get('extra_sources');
        foreach ($sources as $src) {
            $parsed = $this->extractSourceId($src);
            if (empty($parsed) || count($parsed) < 2) {
                continue ;
            }
            $this->plugins[] = $parsed[1];
        }
        if (empty($this->plugins) && empty($force)) {
            return [];
        }
        return $this->getContent($force);
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
        if (empty($parts) || $parts[0] != 'plugins') {
            return [];
        }

        return $parts;
    }

    /**
     * Get extra content source
     *
     * @return array
     */
    public function getExtraContentSource(): array
    {
        return [];
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
     * Main method
     * Get all content from plugins
     *
     * @param bool $force ignore settings
     *
     * @return array
     */
    protected function getContent(bool $force): array
    {
        // START content collection
        $this->generateShortcodeContent($force);
        $this->generateHooksContent($force);
        $this->generateWidgetContent($force);
        $this->generateJsContent($force);
        $this->generateCssContent($force);
        // END content collection

        do_action('_clickio_after_plugins_extra_generated', $this);
        $this->setPluginsData();
        return $this->content;
    }

    /**
     * Collect content from hooks
     * $this->content['plugin_name']['content']
     *
     * @param array $force ignore settings
     *
     * @return void
     */
    protected function generateHooksContent(bool $force)
    {
        $hooks = [
            // "wp_head",
            "template_redirect",
            "wp_footer",
            "the_title",
            "the_content",
            "the_excerpt",
            "document_title_parts",
            "dynamic_sidebar",
            "wp_meta",
            "sidebars_widgets",
        ];

        global $wp_filter;
        foreach ($hooks as $hook) {
            if (!array_key_exists($hook, $wp_filter)) {
                continue;
            }

            foreach ($wp_filter[$hook]->callbacks as $prior_val => $priority) {
                foreach ($priority as $callback_key => $callback) {
                    $target = $callback['function'];
                    if (!Plugins::pluginCallbackIsSafe($target)) {
                        continue;
                    }

                    $plugin = Plugins::getPluginByCallback($target);
                    if (empty($plugin)) {
                        continue;
                    }

                    $file = str_replace(['.php', 'class.'], ['_php', 'class_'], $plugin['PluginFile']);
                    if (!in_array($file, $this->plugins) && empty($force)) {
                        continue;
                    }
                    ob_start();
                    try{
                        $ret_val = call_user_func($target);
                    } catch(\Exception $e){
                        static::logError($e->getMessage());
                        $ret_val = null;
                    }
                    $echo_val = ob_get_contents();
                    @ob_end_clean();

                    $cont = '';
                    if (!empty($ret_val)) {
                        if (is_string($ret_val)) {
                            $cont = trim($ret_val);
                        } else {
                            $cont = '';
                        }
                    } else {
                        $cont = trim($echo_val);
                    }

                    if (empty($cont)) {
                        continue;
                    }

                    $method = Plugins::getCallbackMethodName($target);
                    $key = sprintf("%s_%s_%s", $method, $hook, $prior_val);

                    $js_content = preg_match("/\<script/", $cont);
                    $link_css_content = preg_match("/\<link.*rel=[\"']stylesheet[\"'].*>/", $cont);
                    $inline_css_content = preg_match("/\<style/", $cont);
                    if ($hook == 'wp_head') {
                        $this->pushContent('head', $file, $key, $cont, $hook);
                    } else if ($js_content) {
                        $this->pushContent('script', $file, $key, $cont, $hook);
                    } else if ($link_css_content || $inline_css_content) {
                        $this->pushContent('css', $file, $key, $cont, $hook);
                    } else {
                        $this->pushContent('content', $file, $key, $cont, $hook);
                    }
                }
            }
        }
    }

    /**
     * Collect js from registered script
     * $this->content['plugin_name']['script']
     *
     * @param array $force ignore settings
     *
     * @return void
     */
    protected function generateJsContent(bool $force)
    {
        $js = wp_scripts();
        $in_footer = $js->in_footer;
        $plugin_relative_path = str_replace(ABSPATH, '', WP_PLUGIN_DIR);
        foreach ($js->registered as $script_id => $script) {
            if (strpos($script->src, $plugin_relative_path) === false) {
                $plugin = ["id" => "wp/wordpress_script.php"];
            } else {
                $plugin = Plugins::getPluginByPath($script->src);
            }

            $plugin_id = str_replace(['.php', 'class.'], ['_php', 'class_'], $plugin['id']);
            if (!in_array($plugin_id, $this->plugins) && empty($force)) {
                continue;
            }
            $extra_pattern = "<script type=\"text/javascript\">%s</script>";
            $source_pattern = "<script type=\"text/javascript\" src=\"%s\"></script>";

            $src = $script->src;
            if (!empty($script->ver)) {
                $src = sprintf("%s?ver=%s", $src, $script->ver);
            }
            $source_html = sprintf($source_pattern, $src);

            $loc = in_array($script_id, $in_footer)? 'wp_footer' : 'wp_head';

            $script_id = str_replace(['.js', 'jquery.'], ['_js', 'jquery_'], $script_id);
            if (empty($script->extra)) {
                $this->pushContent('script', $plugin_id, $script_id, $source_html, $loc);
                continue;
            }

            $extra_keys = ['data', 'before', 'after'];
            foreach ($extra_keys as $key) {
                $extra_html = '';
                if (array_key_exists($key, $script->extra)) {
                    $extra = $script->extra[$key];
                    if (in_array($key, ['before', 'after'])) {
                        $extra_html = sprintf($extra_pattern, $extra[1]);
                    } else {
                        $extra_html = sprintf($extra_pattern, $extra);
                    }

                    if ($key == 'before') {
                        $source_html = $extra_html.$source_html;
                    } else {
                        $source_html .= $extra_html;
                    }
                }
            }

            $this->pushContent('script', $plugin_id, $script_id, $source_html, $loc);
        }
    }

    /**
     * Collect css from registered styles
     * $this->content['plugin_name']['css']
     *
     * @param array $force ignore settings
     *
     * @return void
     */
    protected function generateCssContent(bool $force)
    {
        $css = wp_styles();
        $plugin_relative_path = str_replace(ABSPATH, '', WP_PLUGIN_DIR);
        foreach ($css->registered as $style_id => $style) {
            if (strpos($style->src, $plugin_relative_path) === false) {
                continue;
            }

            $plugin = Plugins::getPluginByPath($style->src);
            $plugin_id = str_replace(['.php', 'class.'], ['_php', 'class_'], $plugin['id']);
            if (!in_array($plugin_id, $this->plugins) && empty($force)) {
                continue;
            }
            $extra_pattern = "<style>%s</style>";
            $source_pattern = "<link rel=\"stylesheet\" href=\"%s\"></link>";

            $src = $style->src;
            if (!empty($style->ver)) {
                $src = sprintf("%s?ver=%s", $src, $style->ver);
            }
            $source_html = sprintf($source_pattern, $src);
            if (empty($style->extra)) {
                $this->pushContent('css', $plugin_id, $style_id, $source_html, 'wp_head');
                continue;
            }

            $extra_keys = ['data', 'before', 'after'];
            foreach ($extra_keys as $key) {
                $extra_html = '';
                if (array_key_exists($key, $style->extra)) {
                    $extra = $style->extra[$key];
                    if (in_array($key, ['before', 'after']) && array_key_exists(1, $extra)) {
                        $extra_html = sprintf($extra_pattern, $extra[1]);
                    } else {
                        $content = '';
                        if (is_array($extra)) {
                            $content = array_shift($extra);
                        } else {
                            $content = $extra;
                        }
                        $extra_html = sprintf($extra_pattern, $content);
                    }

                    if ($key == 'before') {
                        $source_html = $extra_html.$source_html;
                    } else {
                        $source_html .= $extra_html;
                    }
                }
            }
            $this->pushContent('css', $plugin_id, $style_id, $source_html, 'wp_head');
        }
    }

    /**
     * Build content struct
     *
     * @param string $section content type e.g. content, css, script
     * @param string $plugin plugin id e.g. my_plugin/main_file_php
     * @param string $key content unique id e.g. clickio_add_amp_link_wp_head_10
     * @param string $content extra content
     * @param string $location where content found
     *
     * @return void
     */
    public function pushContent(string $section, string $plugin, string $key, string $content, string $location)
    {
        if (!array_key_exists($plugin, $this->content)) {
            $this->content[$plugin] = [];
        }

        if (!array_key_exists($section, $this->content[$plugin])) {
            $this->content[$plugin][$section] = [];
        }

        if (!array_key_exists($key, $this->content[$plugin][$section])) {
            $this->content[$plugin][$section][$key] = '';
        }

        $this->content[$plugin][$section][$key] = ["content" => $content, "location" => $location];
    }

    /**
     * Collect widgets content from "page widgets"
     * $this->content['plugin_name']['content']
     *
     * @param array $force ignore settings
     *
     * @return void
     */
    protected function generateWidgetContent(bool $force)
    {
        $widgets = Widgets::getActiveWidgets();

        foreach ($widgets as $w_array) {
            if (empty($w_array)) {
                continue ;
            }

            $w_obj = SafeAccess::fromArray($w_array, 'obj', 'mixed', null);
            if (empty($w_obj) || !($w_obj instanceof WP_Widget)) {
                continue ;
            }

            $ref_cls = new ReflectionClass($w_obj);
            $path = $ref_cls->getFileName();
            $plugin = Plugins::getPluginByPath($path);

            if (!isset($plugin['id'])) {
                continue ;
            }
            $plugin_id = str_replace(['.php', 'class.'], ['_php', 'class_'], $plugin['id']);
            if (!in_array($plugin_id, $this->plugins) && empty($force)) {
                continue;
            }

            ob_start();
            try{
                $args = [
                    "before_title" => '<span class="widgettitle">',
                    "after_title" => '</span>',
                    "before_widget" => sprintf('<div class="widget %s">', $w_obj->widget_options['classname']),
                    "after_widget" => '</div>'
                ];
                $w_obj->widget($args, $w_array['params']);
            } catch (\Exception $e) {
                // silence is golden
            }
            $content = ob_get_contents();
            ob_end_clean();
            $this->pushContent('content', $plugin_id, $w_obj->id, $content, "widget");
        }
    }

    /**
     * Get shortcodes content
     *
     * @param bool $force ignore collection rules
     *
     * @return void
     */
    protected function generateShortcodeContent(bool $force)
    {
        global $shortcode_tags;

        $post = get_post(null, 'OBJECT', 'raw');

        if (!is_object($post) || empty($post) || empty($post->post_content)) {
            return ;
        }

        $post_content = $post->post_content;

        $codes = [];
        $regex = sprintf('/%s/', get_shortcode_regex());
        @preg_match_all($regex, $post_content, $matches, PREG_SET_ORDER);


        if (!is_array($matches)) {
            return ;
        }

        foreach ($matches as $match) {

            $code = SafeAccess::fromArray($match, 0, 'string', '');
            $tag = SafeAccess::fromArray($match, 2, 'string', '');
            $callback = SafeAccess::fromArray($shortcode_tags, $tag, 'array', []);
            if (empty($callback)) {
                $callback = SafeAccess::fromArray($shortcode_tags, $tag, 'string', '');
                if (empty($callback)) {
                    $callback = [];
                } else {
                    $callback = [$callback];
                }
            }

            if (empty($callback)) {
                continue ;
            }

            $_callback = SafeAccess::fromArray($callback, 0, 'mixed', null);
            $ref = null;
            if (is_object($_callback)) {
                $ref = new ReflectionObject($_callback);
            } elseif (class_exists($_callback)) {
                $ref = new ReflectionClass($_callback);
            } elseif (function_exists($_callback)) {
                $ref = new ReflectionFunction($_callback);
            } else {
                continue ;
            }

            $file = $ref->getFileName();
            $plugin = Plugins::getPluginByPath($file);
            if (empty($plugin) || !array_key_exists('id', $plugin)) {
                continue ;
            }

            $plugin_id = str_replace(['.php', 'class.'], ['_php', 'class_'], $plugin['id']);
            if (!in_array($plugin_id, $this->plugins) && empty($force)) {
                continue;
            }

            try {
                $content = do_shortcode($code);
            } catch (\Exception $e) {
                continue ;
            }

            $this->pushContent('content', $plugin_id, $tag, $content, "shortcode");
        }

    }

    /**
     * Final step, set readable names for plugins
     *
     * @return void
     */
    protected function setPluginsData()
    {
        $_content_copy = $this->content;
        foreach ($_content_copy as $plugin_id => $plugin_content) {
            $plugin_file = str_replace(['_php', 'class_'], ['.php', 'class.'], $plugin_id);
            $data = Plugins::getPluginById($plugin_file);
            $name = array_key_exists('Name', $data)? $data['Name'] : 'Not plugin or plugin could not be detected';
            $plugin_content['name'] = $name;
            $this->content[$plugin_id] = $plugin_content;
        }
    }
}
