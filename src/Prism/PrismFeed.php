<?php
/**
 * Prism feed
 */

namespace Clickio\Prism;

use Clickio\Options;

/**
 * Prism Feed
 *
 * @package Prism
 */
final class PrismFeed
{

    /**
     * Prism feed init script
     *
     * @var string
     */
    const FEED_SCRIPT_PATTERN = "<script src='https://s.clickiocdn.com/t/%s/prism_feed.js'></script>";

    /**
     * Check settings for feed is enabled
     *
     * @return string
     */
    public static function getInitScript(): string
    {
        $enabled = Options::get('feed_enabled');
        $site = Options::get('site_id');
        if (empty($enabled) || empty($site)) {
            return '';
        }

        $code = sprintf(static::FEED_SCRIPT_PATTERN, $site);
        return $code;
    }
}
