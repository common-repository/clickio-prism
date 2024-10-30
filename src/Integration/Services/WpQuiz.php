<?php

/**
 * WP Quiz
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Options;

/**
 * Integration with WP Quiz
 *
 * @package Integration\Services
 */
final class WpQuiz extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wp-quiz/wp-quiz.php';

    /**
     * Premium plugin if
     *
     * @var string
     */
    const PRO_PLUGIN_ID = 'wp-quiz-pro/wp-quiz-pro.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wpquiz';

    /**
     * Fix the issue when the quiz result return 403 error
     *
     * @return void
     */
    public static function fixPlayDataIssue()
    {
        if (!static::integration()) {
            return ;
        }

        $_SERVER['HTTP_X_WP_NONCE'] = null;
        $_REQUEST['_wpnonce'] = null;
    }

    /**
     * Test integration is available
     *
     * @return bool
     */
    public static function integration(): bool
    {
        $parent = parent::integration();
        $quiz_req = preg_match("~quiz\/v2\/play_data~", $_SERVER["REQUEST_URI"]);
        $quiz_collected = false;

        $cfg = Options::get("extra_sources", []);
        foreach ($cfg as $src) {
            $free_plugin = preg_match("~plugins\.wp-quiz\/wp-quiz_php~", $src);
            $pro_plugin = preg_match("~plugins\.wp-quiz-pro\/wp-quiz-pro_php~", $src);
            if ($free_plugin || $pro_plugin) {
                $quiz_collected = true;
                break;
            }
        }

        return $parent && $quiz_req && $quiz_collected;
    }

    /**
     * Get list of plugins with which the service can integrate
     *
     * @return array
     */
    protected static function getIntegrationList(): array
    {
        return [static::PLUGIN_ID, static::PRO_PLUGIN_ID];
    }
}
