<?php
/**
 * Quiz Maker
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Actions with Quiz Maker plugin
 *
 * @package Integration\Services
 */
final class QuizMaker extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'quiz-maker/quiz-maker.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'quiz_maker';

    /**
     * Listen for shortcodes content
     *
     * @return void
     */
    public static function listenShortcodes()
    {
        if (!static::integration()) {
            return ;
        }

        add_action("do_shortcode_tag", [static::class, 'allowScriptStyles'], PHP_INT_MAX, 2);
    }

    /**
     * Add permissive tags
     *
     * @param string $content shortcode content
     * @param string $tag shortcode name
     *
     * @return string
     */
    public static function allowScriptStyles($content = "", $tag = "")
    {
        if ($tag != 'ays_quiz') {
            return $content;
        }
        $patterns = ["<script", "<style", "<form", "<input"];
        $replace = [
            "<script data-allow-script",
            "<style data-allow-style",
            "<form data-allow-tag",
            "<input data-allow-tag",
        ];
        $content = str_replace($patterns, $replace, $content);
        return '<div data-no-lazy class="cl-no-lazy">'.$content.'</div>';
    }
}
