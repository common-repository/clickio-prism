<?php

/**
 * Notifications
 */

namespace Clickio;

/**
 * Admin notifications
 *
 * @package Clickio
 */
class Notification
{
    /**
     * Display warning notification
     *
     * @param string $text html message
     *
     * @return void
     */
    public static function warning(string $text)
    {
        $closure = function () use ($text) {
            echo "<div class=\"notice notice-warning is-dismissible\"><p>$text</p></div>";
        };
        static::pushMessage($closure);
    }

    /**
     * Display info notification
     *
     * @param string $text html message
     *
     * @return void
     */
    public static function info(string $text)
    {
        $closure = function () use ($text) {
            echo "<div class=\"notice notice-info is-dismissible\"><p>$text</p></div>";
        };
        static::pushMessage($closure);
    }

    /**
     * Display error notification
     *
     * @param string $text html message
     *
     * @return void
     */
    public static function error(string $text)
    {
        $closure = function () use ($text) {
            echo "<div class=\"notice notice-error is-dismissible\"><p>$text</p></div>";
        };
        static::pushMessage($closure);
    }

    /**
     * Send message
     *
     * @param callable $closure wp "add_action" callback
     *
     * @return void
     */
    protected static function pushMessage($closure)
    {
        add_action('admin_notices', $closure);
    }
}
