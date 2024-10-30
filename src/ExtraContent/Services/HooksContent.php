<?php
/**
 * Hooks service
 */

namespace Clickio\ExtraContent\Services;

use Clickio as org;
use Clickio\ExtraContent\Interfaces\IExtraContentService;
use Clickio\Utils\Plugins;

/**
 * Collect extra content from hooks
 *
 * @package ExtraContent\Services
 */
class HooksContent extends ContentServiceBase implements IExtraContentService
{
    /**
     * Service uniq name
     *
     * @var string
     */
    const NAME = 'hooks';

    /**
     * Label for settings page
     *
     * @var string
     */
    const LABEL = "Hooks";

    /**
     * Where service store own settings
     *
     * @var string
     */
    const OPT_KEY = 'extra_actions';

    /**
     * Extra content
     *
     * @var array
     */
    protected $extra = [];

    /**
     * Available hooks
     *
     * @var array
     */
    protected $hooks = [
        // "wp_head",
        "template_redirect",
        "wp_footer",
        "the_title",
        "the_content_feed",
        "the_excerpt",
        "the_excerpt_rss",
        "document_title_parts",
        "wp_enqueue_scripts",
        "wp_print_styles",
        "wp_print_scripts",
        "dynamic_sidebar",
        "wp_meta",
        "wp_print_footer_scripts",
        "sidebars_widgets",
    ];

    /**
     * Return only specified hooks
     *
     * @var bool
     */
    protected $use_sources = false;

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
        $settings_page = [];
        foreach ($this->hooks as $hook) {
            $settings_page[$hook] = sprintf("<b>%s</b>", $hook);
        }
        return $settings_page;
    }

    /**
     * Parse id to array
     *
     * @param string $id source id
     *
     * @return array
     */
    public static function extractSourceId(string $id): array
    {
        $path = explode(".", $id);

        if (empty($path) || $path[0] != 'hooks') {
            return [];
        }

        $ext = array_pop($path);
        $file = array_pop($path);
        $plugin_id = sprintf("%s.%s", $file, $ext);
        $path[] = $plugin_id;

        return $path;
    }

    /**
     * Build source id
     *
     * @param string $hook hook name
     * @param string $plugin plugin name
     *
     * @return string
     */
    public function getSourceId(string $hook, string $plugin): string
    {
        return sprintf("hooks.%s.%s", $hook, $plugin);
    }

    /**
     * Collecting extra content from wp hooks
     *
     * @param bool $force ignore settings
     *
     * @return void
     */
    protected function collectInfo(bool $force = false)
    {
        $hooks = $this->filterHooks($force);
        $sources = org\Options::get("extra_sources");

        foreach ($hooks as $hook_name => $hook) {
            foreach ($hook->callbacks as $priority) {
                foreach ($priority as $name => $callback) {
                    $callback = $callback['function'];
                    if (!Plugins::pluginCallbackIsSafe($callback)) {
                        continue;
                    }

                    $plugin = Plugins::getPluginByCallback($callback);
                    if (empty($plugin)) {
                        $plugin['PluginFile'] = $this->_callbackToString($callback);
                        $plugin['Name'] = $this->_callbackToString($callback);
                    }

                    ob_start();
                    try{
                        $ret_val = call_user_func($callback);
                    } catch(\Exception $e){
                        $ret_val = null;
                    }
                    $echo_val = ob_get_contents();
                    @ob_end_clean();

                    $extra = '';
                    if (!empty($ret_val)) {
                        if (is_string($ret_val)) {
                            $extra = trim($ret_val);
                        } else {
                            $extra = '';
                        }
                    } else {
                        $extra = trim($echo_val);
                    }

                    if (!empty($extra)) {
                        if ($this->use_sources) {
                            $source_id = $this->getSourceId($hook_name, $plugin['PluginFile']);
                            if (!in_array($source_id, $sources)) {
                                continue ;
                            }
                        }

                        if (!array_key_exists($hook_name, $this->extra)) {
                            $this->extra[$hook_name] = [];
                        }

                        $this->extra[$hook_name][$plugin['PluginFile']]['name'] = $plugin['Name'];

                        if (!empty($this->extra[$hook_name][$plugin['PluginFile']]['content'])) {
                            $this->extra[$hook_name][$plugin['PluginFile']]['content'] .= "\n".$extra;
                        } else {
                            $this->extra[$hook_name][$plugin['PluginFile']]['content'] = $extra;
                        }
                    }
                }
            }
        }
    }

    /**
     * Get only selected in config hooks
     *
     * @param bool $force ignore settings
     *
     * @return array
     */
    protected function filterHooks(bool $force = false): array
    {
        global $wp_filter;

        $hooks = [];
        $sources = org\Options::get("extra_sources");
        if ($force) {
            $hooks_list = $this->hooks;
        } elseif (!empty($sources)) {
            $hooks_list = $this->hooks;
            $this->use_sources = true;
        } else {
            $conf_act = org\Options::get('extra_actions');
            $hooks_list = $conf_act;
        }
        $custom_hooks = str_getcsv(org\Options::get('extra_custom_actions'));
        $hooks_list = array_merge($hooks_list, $custom_hooks);

        foreach ($hooks_list as $hook) {
            if (array_key_exists($hook, $wp_filter)) {
                $hooks[$hook] = $wp_filter[$hook];
            }
        }
        return $hooks;
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
     * Convert callback to string
     *
     * @param mixed $callback callback
     *
     * @return string
     */
    private function _callbackToString($callback): string
    {
        $callback_str = "wp/undefined.php";

        if (gettype($callback) == 'string') {
            $callback_str = sprintf("wp/%s.php", $callback);
        } else if (is_array($callback)) {
            if (gettype($callback[0]) == 'string') {
                $callback_str = sprintf('wp/%s.php', implode('::', $callback));
            } else if (is_object($callback[0])) {
                $callback_str = sprintf('wp/%s::%s.php', get_class($callback[0]), $callback[1]);
            }
        }

        return $callback_str;
    }
}
