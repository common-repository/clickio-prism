<?php

/**
 * User Photo
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\SafeAccess;

/**
 * Actions with User Photo plugin
 *
 * @package Integration\Services
 */
final class UserPhoto extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'user-photo/user-photo.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'uphoto';

    /**
     * Get user avatar
     *
     * @param int $uid user id
     *
     * @return string
     */
    public static function getAvatar(int $uid = 0): string
    {
        if (!static::integration()) {
            return '';
        }

        if (empty($uid)) {
            $uid = get_current_user_id();
            if (empty($uid)) {
                return '';
            }
        }

        $meta = get_user_meta($uid);
        $image_file_list = SafeAccess::fromArray($meta, "userphoto_image_file", 'array', []);
        $image_file = SafeAccess::fromArray($image_file_list, 0, 'string', '');
        $upload_dir = wp_upload_dir();
        $err = SafeAccess::fromArray($upload_dir, 'error', 'mixed', '');
        if (empty($image_file) || is_wp_error($upload_dir) || !empty($err)) {
            return '';
        }

        $src = trailingslashit($upload_dir['baseurl']) . 'userphoto/' . $image_file;
        return $src;
    }
}
