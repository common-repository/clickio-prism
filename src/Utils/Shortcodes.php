<?php

/**
 * Shortcode manipulation utilities
 */

namespace Clickio\Utils;

use Clickio\Logger\LoggerAccess;
use Clickio\Options;

/**
 * Shortcode manipulation utilities
 *
 * @package Utils
 */
final class Shortcodes
{
    /**
     * Logger trait
     * Simple logger usage
     */
    use LoggerAccess;

    /**
     * Shortcodes to be disabled
     *
     * @var array
     */
    protected $blacklist = [];

    /**
     * Singletone container
     *
     * @var self
     */
    protected static $sigletone = null;

    /**
     * Constructor
     *
     * @param array $disabled shortcodes to be disabled
     */
    public function __construct(array $disabled)
    {
        $this->blacklist = $disabled;
    }

    /**
     * Set shortcode usage status
     * true - blacklisted shortcodes will not be executed
     * false - all shortcodes will be executed
     *
     * @param array $status remover status
     *
     * @return void
     */
    public static function setRemoverStatus(bool $status)
    {
        $inst = static::getInstance();
        if (empty($inst->blacklist)) {
            return ;
        }

        if ($status) {
            add_action("pre_do_shortcode_tag", [$inst, 'disableAction'], 1, 2);
        } else {
            remove_action("pre_do_shortcode_tag", [$inst, 'disableAction'], 1, 2);
        }
    }

    /**
     * Singletone constructor
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (!static::$sigletone) {
            $disabled = Options::get('replace_callback');
            $cleaned = static::_parse($disabled);
            static::$sigletone = new static($cleaned);
        }

        return static::$sigletone;
    }

    /**
     * Action to disable shortcodes
     *
     * @param mixed $shortcode shortcode data
     * @param mixed $tag shortcode name
     *
     * @return mixed
     */
    public function disableAction($shortcode, $tag)
    {
        foreach ($this->blacklist as $item) {
            if (preg_match("/".$item."/i", $tag)) {
                return '';
            }
        }
        return $shortcode;
    }

    /**
     * Getter.
     * Get disabled shortcodes
     *
     * @return array
     */
    public static function getDisabledItems(): array
    {
        $inst = static::getInstance();
        return $inst->blacklist;
    }

    /**
     * Parse raw shortcodes string
     *
     * @param string $raw_disabled comma separated shortcodes
     *
     * @return array
     */
    private static function _parse(string $raw_disabled): array
    {
        if (empty($raw_disabled)) {
            return [];
        }

        $hooks_list = explode(',', $raw_disabled);
        $filtered = array_map(
            function ($el) {
                return strtolower(trim($el));
            },
            $hooks_list
        );
        $filtered = array_unique($filtered);
        $filtered = array_filter($filtered);
        return array_values($filtered);
    }
}
