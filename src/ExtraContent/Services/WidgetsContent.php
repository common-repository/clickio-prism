<?php
/**
 * Widgets extra
 */

namespace Clickio\ExtraContent\Services;

use Clickio as org;
use Clickio\ExtraContent\Interfaces\IExtraContentService;
use Clickio\Logger\Interfaces\ILogger;
use Clickio\Utils\Widgets;

/**
 * Widgets extra content service
 *
 * @package ExtraContent\Services
 */
class WidgetsContent extends ContentServiceBase implements IExtraContentService
{

    /**
     * Label for settings page
     *
     * @var string
     */
    const LABEL = "Widgets";

    /**
     * Service uniq name
     *
     * @var string
     */
    const NAME = 'widgets';

    /**
     * Where service store own settings
     *
     * @var string
     */
    const OPT_KEY = 'extra_widgets';

    /**
     * List of active widgets
     *
     * @var array
     */
    protected $widgets = [];

    /**
     * Widgets Extra content
     *
     * @var array
     */
    protected $extra = [];

    /**
     * Flag to use source list
     *
     * @var bool
     */
    protected $use_source = false;

    /**
     * Service rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $widgets = Widgets::getActiveWidgets();
        $this->widgets = $widgets;
        return $this->extra;
    }

    /**
     * Entry point
     * Get extra content from all active widgets
     *
     * @param bool $force ignore settings
     *
     * @return array
     */
    public function getExtraContent(bool $force = false): array
    {
        if (empty($this->extra)) {
            $this->extractFromWidgets($force);
        }
        return $this->extra;
    }

    /**
     * Get extra content source
     *
     * @return array
     */
    public function getExtraContentSource(): array
    {
        $settings_page = [];
        foreach ($this->widgets as $wgt_id => $widget) {
            $pattern = "<b>%s</b> <div>Type: %s</div><div>ID: %s</div>";
            $label = sprintf($pattern, $widget['title'], $widget['obj']->name, $wgt_id);
            $settings_page[$wgt_id] = $label;
        }
        return $settings_page;
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
     * Collecting content
     *
     * @param bool $force ignore config
     *
     * @return void
     */
    protected function extractFromWidgets(bool $force = false)
    {
        add_filter('widget_title', '__return_empty_string');

        $widgets = $this->getWidgets($force);

        foreach ($widgets as $id => $widget) {

            ob_start();
            try{
                $args = [
                    "before_title" => '',
                    "after_title" => '',
                    "before_widget" => '',
                    "after_widget" => ''
                ];
                $widget['obj']->widget($args, $widget['params']);
            } catch (\Exception $e) {
                // do nothing
            }
            $content = ob_get_contents();
            ob_end_clean();

            if (!empty($content)) {
                $this->extra[$id]['title'] = $widget['title'];
                $this->extra[$id]['content'] = $content;
            }
        }
    }

    /**
     * Get widgets list
     *
     * @param bool $force use all widgets
     *
     * @return array
     */
    protected function getWidgets(bool $force = false): array
    {
        $widgets = [];

        if ($force) {
            $widgets = Widgets::getActiveWidgets();
        } else {
            $selected_widgets = org\Options::get('extra_widgets');
            $sources = org\Options::get("extra_sources");
            foreach ($sources as $src) {
                $widget_source = static::extractSourceId($src);
                $widget_name = array_pop($widget_source);
                if (!empty($widget_name)) {
                    $selected_widgets[] = $widget_name;
                }
            }

            foreach ($selected_widgets as $id) {
                if (!array_key_exists($id, $this->widgets)) {
                    continue;
                }
                $widgets[$id] = $this->widgets[$id];
            }
        }
        return $widgets;
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
        if (empty($parts) || $parts[0] != 'widgets') {
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
