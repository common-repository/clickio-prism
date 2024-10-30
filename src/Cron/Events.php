<?php
/**
 * Plugin events
 */

namespace Clickio\Cron;

/**
 * Cron events
 *
 * @package Cron
 */
final class Events
{
    /**
     * Hourly Event
     *
     * @var string
     */
    const HOURLY_EVENT = "_clickioamp_run_hourly_task";

    /**
     * Defined events
     *
     * @var array
     */
    protected static $events = [
        self::HOURLY_EVENT => [
            "name" => self::HOURLY_EVENT,
            "interval" => 'hourly'
        ],
    ];

    /**
     * Event getter
     *
     * @param string $name get event by name
     *
     * @return array
     */
    public static function getEvent(string $name): array
    {
        if (array_key_exists($name, static::$events)) {
            return static::$events[$name];
        }
        return [];
    }

    /**
     * Events getter
     *
     * @return array
     */
    public static function getEvents(): array
    {
        return static::$events;
    }
}