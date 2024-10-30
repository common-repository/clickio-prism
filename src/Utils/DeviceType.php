<?php

/**
 * Detect device type
 */

namespace Clickio\Utils;

/**
 * Device type
 *
 * @package Utils
 */
class DeviceType
{

    /**
     * Mobile user agent
     *
     * @return bool
     */
    public static function isMobile(): bool
    {
        $ua_devices = [
            'Android',
            'iPhone',
            'iPad',
            'iPod'
        ];
        return static::checkDevice($ua_devices);
    }

    /**
     * Check for current UA in list
     *
     * @param array $ua_list list of patterns to check
     *
     * @return bool
     */
    protected static function checkDevice(array $ua_list): bool
    {
        $ua = SafeAccess::fromArray($_SERVER, 'HTTP_USER_AGENT', 'string', 'no user agent');
        foreach ($ua_list as $device) {
            if (stripos($ua, $device) === false) {
                continue ;
            }
            return true;
        }
        return false;
    }

    /**
     * The bot
     *
     * @return bool
     */
    public static function isBot(): bool
    {
        $ua_devices = [
            'googlebot'
        ];

        return static::checkDevice($ua_devices);
    }
}
