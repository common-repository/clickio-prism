<?php

/**
 * Abstract builder
 */

namespace Clickio\Listings\Builders;

use Clickio\Utils\ImageInfo;
use Clickio\Utils\SafeAccess;
use Clickio\Utils\Shortcodes;

/**
 * Abstract builder
 *
 * @package Listings\Builders
 */
abstract class AbstractBuilder
{
    /**
     * List of replaced shortcodes
     *
     * @var array
     */
    protected static $replace = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        Shortcodes::setRemoverStatus(true);
    }

    /**
     * Get thumbnail image by size
     *
     * @param int $image_id post thumbnail id
     * @param string $size image size, can be found in get_id, parameter ImageInfo
     * @param int $desired desired image width
     * @param int $min minimal image width
     * @param bool $crop use cropped images
     *
     * @return array
     */
    protected function getFeatureImage(int $image_id, string $size, int $desired, int $min, bool $crop = false): array
    {
        $common = ImageInfo::getCommonInfo($image_id);
        $res = [];
        if ($size == 'custom') {
            $res = ImageInfo::getImageClosestWidth($image_id, $desired, $min, $crop);
        } else {
            $images = ImageInfo::getImageSizes($image_id);
            if (array_key_exists($size, $images)) {
                $res = $images[$size];
            }
        }

        if (empty($res)) {
            $res = ImageInfo::getLargestImage($image_id, 600, $crop);
        }
        $original = ImageInfo::getFullThumbnail($image_id);
        return [
            'Original' => $original['Src'],
            'Src' => SafeAccess::fromArray($res, 'Src', 'string', ''),
            'Width' => SafeAccess::fromArray($res, 'Width', 'mixed', 0),
            'Height' => SafeAccess::fromArray($res, 'Height', 'mixed', 0),
            'Alt' => SafeAccess::fromArray($common, 'Alt', 'string', '')
        ];
    }
}