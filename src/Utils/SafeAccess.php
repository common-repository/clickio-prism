<?php
/**
 * Safe access to variables
 */

namespace Clickio\Utils;

/**
 * Safe access to variables
 *
 * @package Utils
 */
final class SafeAccess
{

    /**
     * Get value from array
     *
     * @param mixed $var from array
     * @param mixed $key key or index in array
     * @param string $type return type, use "mixed" when return type is undefined
     * @param mixed $default default value
     *
     * @return mixed
     */
    public static function fromArray($var, $key, $type, $default)
    {
        if (empty($var) || (empty($key) && $key !== 0) || !is_array($var) || !array_key_exists($key, $var)) {
            return $default;
        }
        $value = $var[$key];

        switch ($type) {
            case 'mixed': // do nothing
                break;
            default: $value = (gettype($value) != $type) ? $default : $value;
                break;
        }

        return $value;
    }

    /**
     * Checks if the given key or index exists in the array
     *
     * @param mixed $key Value to check
     * @param mixed $value variable to check
     *
     * @return bool
     */
    public static function arrayKeyExists($key, $value): bool
    {
        if ((empty($key) && $key !== 0) || empty($value) || !is_array($value)) {
            return false;
        }

        return array_key_exists($key, $value) && isset($value[$key]);
    }
}