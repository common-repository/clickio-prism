<?php

/**
 * User utils
 */

namespace Clickio\Utils;

/**
 * User utils
 *
 * @package Utils
 */
class UserUtils
{

    /**
     * Key to encrypt | decrypt user id
     *
     * @var int
     */
    private static $_key = 456729983;

    /**
     * Get current user
     *
     * @return WP_User|null
     */
    public static function getCurrentUser()
    {
        $uid = apply_filters('determine_current_user', false);
        if (empty($uid)) {
            return null;
        }

        $user = get_user_by('ID', $uid);
        if (empty($user)) {
            return null;
        }

        return $user;
    }

    /**
     * Encrypt user id
     *
     * @param int $uid user id
     *
     * @return int
     */
    public static function encryptUserId(int $uid): int
    {
        return static::$_key ^ $uid;
    }

    /**
     * Decrypt user id
     *
     * @param int $uid user id
     *
     * @return int
     */
    public static function decryptUserId(int $uid): int
    {
        return $uid ^ static::$_key;
    }
}