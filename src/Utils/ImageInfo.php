<?php

/**
 * Image info
 */

namespace Clickio\Utils;

use Clickio\ClickioPlugin;

/**
 * Get image information
 *
 * @package Utils
 */
class ImageInfo
{

    /**
     * Get complite image info
     *
     * @param int $image_id img id
     *
     * @return array
     */
    public static function getImageInfo(int $image_id): array
    {
        $img_info = static::getCommonInfo($image_id);
        $img_sizes = static::getImageSizes($image_id);
        return array_merge($img_info, $img_sizes);
    }

    /**
     * Get all size for image
     *
     * @param int $image_id img id
     *
     * @return array
     */
    public static function getImageSizes(int $image_id): array
    {
        $attach_meta = wp_get_attachment_metadata($image_id);
        $images = [];

        if (empty($attach_meta)) {
            return $images;
        }

        // resized images
        if (array_key_exists('sizes', $attach_meta) && !empty($attach_meta['sizes'])) {
            $path = dirname(wp_get_attachment_url($image_id));
            foreach ($attach_meta['sizes'] as $size => $img) {
                if (empty($img) || !SafeAccess::arrayKeyExists('file', $img)) {
                    continue ;
                }
                $images[$size] = [
                    "Src" => sprintf("%s/%s", $path, $img['file']),
                    "Width" => (int)$img['width'],
                    "Height" => (int)$img['height'],
                    "Cropped" => static::isImageCropped($img, $attach_meta)
                ];
            }
        }

        // source image
        if (!array_key_exists('full', $images)) {
            $images['full'] = static::getFullThumbnail($image_id);
        }
        uasort(
            $images,
            function($last, $next) {
                $last_width = (int)$last['Width'];
                $current_width = (int)$next['Width'];
                return $last_width > $current_width? 1 : ($last_width == $current_width? 0 : -1);
            }
        );
        return $images;
    }

    /**
     * Check if image was cropped
     * if current ratio != original ratio this means image was cropped
     *
     * @param array $current target image
     * @param array $original canonical image
     *
     * @return bool
     */
    public static function isImageCropped(array $current, array $original): bool
    {
        $orig_width = (int)$original['width'];
        $orig_height = (int)$original['height'];
        $orig_ratio = round($orig_width / $orig_height, 1);

        $target_width = (int)$current['width'];
        $target_height = (int)$current['height'];
        $target_ratio = round($target_width / $target_height, 1);

        $cropped = $orig_ratio != $target_ratio;
        return $cropped;
    }

    /**
     * Get common info about picture
     *
     * @param int $image_id img id
     *
     * @return array
     */
    public static function getCommonInfo(int $image_id): array
    {
        $featured_img = get_post($image_id);
        $img_info = [
            'Alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
            'Caption' => $featured_img->post_excerpt,
            'Description' => $featured_img->post_content,
            'Title' => $featured_img->post_title
        ];
        return $img_info;
    }

    /**
     * Get rest url for image
     *
     * @param int $image_id img id
     *
     * @return string
     */
    public static function getMediaRestUrl(int $image_id): string
    {
        $rest_url = ClickioPlugin::getRestUrl();
        return sprintf("%s/media/%s", $rest_url, $image_id);
    }

    /**
     * Get largest not cropped image
     *
     * @param int $image_id thumbnail id
     * @param int $border_value min width
     * @param bool $cropped use cropped images
     *
     * @return array
     */
    public static function getLargestImage(int $image_id, int $border_value = 600, bool $cropped = false): array
    {
        if ($cropped) {
            $sizes = static::getImageSizes($image_id);
        } else {
            $sizes = static::getNotCroppedImages($image_id);
        }
        $img = array_filter(
            $sizes,
            function($el) use ($border_value) {
                return $el["Width"] >= $border_value;
            }
        );
        if (!empty($img)) {
            $img = array_shift($img);
        } else {
            $img = static::getFullThumbnail($image_id);
        }
        return $img;
    }

    /**
     * Get full size thumbnail
     *
     * @param int $image_id thumbnail id
     *
     * @return array
     */
    public static function getFullThumbnail(int $image_id): array
    {
        $attach_meta = wp_get_attachment_metadata($image_id);
        $width = SafeAccess::fromArray($attach_meta, 'width', 'mixed', 0);
        $height = SafeAccess::fromArray($attach_meta, 'height', 'mixed', 0);
        $img_url = wp_get_attachment_url($image_id, 'full');
        $img = [
            "Src" => empty($img_url)? "" : $img_url,
            "Width" => (int)$width,
            "Height" => (int)$height,
            "Cropped" => false
        ];
        return $img;
    }

    /**
     * Get image closest to desired size
     *
     * @param int $image_id thumbnail id
     * @param int $desired desired width
     * @param int $min minimum desired width
     * @param bool $cropped use cropped images
     *
     * @return array
     */
    public static function getImageClosestWidth(int $image_id, int $desired, int $min, bool $cropped = false): array
    {
        if ($cropped) {
            $sizes = static::getImageSizes($image_id);
        } else {
            $sizes = static::getNotCroppedImages($image_id);
        }
        $imgs = array_filter(
            $sizes,
            function ($value, $key) use ($desired) {
                return $key != 'full' && $value['Width'] >= $desired;
            },
            \ARRAY_FILTER_USE_BOTH
        );

        if (empty($imgs)) {
            $imgs = array_filter(
                $sizes,
                function ($value, $key) use ($min) {
                    return $key != 'full' && $value['Width'] >= $min;
                },
                \ARRAY_FILTER_USE_BOTH
            );
        }
        $image = [];
        if (!empty($imgs)) {
            $image = array_shift($imgs);
        }
        return $image;
    }

    /**
     * Get not cropped images
     *
     * @param int $image_id image id
     *
     * @return array
     */
    public static function getNotCroppedImages(int $image_id): array
    {
        $sizes = static::getImageSizes($image_id);
        $images = array_filter(
            $sizes,
            function($el) {
                return !$el["Cropped"];
            }
        );
        return $images;
    }
}
