<?php
/**
 * Show wp-cron status
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;
use Clickio\Cron as cron;

/**
 * Show cron tasks status
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/cron_status/
 *
 * @package RestApi\Actions
 */
class CronStatus extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http post method
     *
     * @return mixed
     */
    public function get()
    {
        $info = [
            "cron_status" => !(defined('DISABLE_WP_CRON') && \DISABLE_WP_CRON),
            "events" => [
            [
                "event" => cron\Events::HOURLY_EVENT,
                "is_active" => $this->cronTaskStarted(cron\Events::HOURLY_EVENT),
                "next" => $this->nextRun(cron\Events::HOURLY_EVENT),
            ]
            ]
        ];

        return $info;
    }

    /**
     * Get next execution datetime
     *
     * @param string $sheduler sheduler name
     *
     * @return string
     */
    protected function nextRun(string $sheduler): string
    {
        $ts = wp_next_scheduled($sheduler);
        $dt = date('Y-m-d H:i:s O', $ts);
        return $dt;
    }

    /**
     * Sheduler status
     *
     * @param string $sheduler sheduler name
     *
     * @return bool
     */
    protected function cronTaskStarted(string $sheduler): bool
    {
        foreach (get_option("cron") as $row) {
            $shedulers = array_keys($row);
            if (in_array($sheduler, $shedulers)) {
                return true;
            }
        }
        return false;
    }
}