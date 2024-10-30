<?php

/**
 * Reading time wp
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with Reading time wp
 *
 * @package Integration\Services
 */
final class ReadingTimeWp extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'reading-time-wp/rt-reading-time.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'reading_time';

    /**
     * Get reading time in minutes
     *
     * @param int $id post id
     *
     * @return int|null
     */
    public static function getReadingTime(int $id)
    {
        if (!static::integration()) {
            return null;
        }

        global $reading_time_wp;
        if (empty($reading_time_wp) || !method_exists($reading_time_wp, 'rt_calculate_reading_time')) {
            return null;
        }

        $opt = get_option('rt_reading_time_options');
        $time = $reading_time_wp->rt_calculate_reading_time($id, $opt);
        if (empty($time)) {
            return null;
        }

        if (!empty($time) && is_string($time) && !is_numeric($time)) {
            $time = 0;
        }
        return $time;
    }
}