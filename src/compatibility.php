<?php

/**
 * Backward compatibility with older WP versions
 * @codingStandardsIgnoreFile
 */

/**
 * Added in wordpress 5.3.0
 */
if (!function_exists('\wp_timezone_string')) {
    function wp_timezone_string()
    {
        $timezone_string = get_option('timezone_string');

        if ($timezone_string) {
            return $timezone_string;
        }

        $offset  = (float)get_option('gmt_offset');
        $hours   = (int)$offset;
        $minutes = ($offset - $hours);

        $sign      = ($offset < 0) ? '-' : '+';
        $abs_hour  = abs($hours);
        $abs_mins  = abs($minutes * 60);
        $tz_offset = sprintf('%s%02d:%02d', $sign, $abs_hour, $abs_mins);

        return $tz_offset;
    }
}

/**
 * Added in wordpress 5.3.0
 */
if (!function_exists('\wp_timezone')) {
    function wp_timezone()
    {
        return new DateTimeZone(wp_timezone_string());
    }
}

/**
 * Added in wordpress 5.3.0
 */
if (!function_exists('\get_post_datetime'))
{
    function get_post_datetime($post = null, $field = 'date', $source = 'local')
    {
        $post = get_post($post);

        if (! $post) {
            return false;
        }

        $wp_timezone = wp_timezone();

        if ('gmt' === $source) {
            $time     = ('modified' === $field) ? $post->post_modified_gmt : $post->post_date_gmt;
            $timezone = new DateTimeZone('UTC');
        } else {
            $time     = ('modified' === $field) ? $post->post_modified : $post->post_date;
            $timezone = $wp_timezone;
        }

        if (empty($time) || '0000-00-00 00:00:00' === $time) {
            return false;
        }

        $datetime = date_create_immutable_from_format('Y-m-d H:i:s', $time, $timezone);

        if (false === $datetime) {
            return false;
        }

        return $datetime->setTimezone($wp_timezone);
    }
}
