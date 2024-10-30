<?php
/**
 * Cyclone slider
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\SafeAccess;

/**
 * Integration with Cyclone slider
 *
 * @package Integration\Services
 */
final class CycloneSlider extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'cyclone-slider-2/cyclone-slider.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'cyclone';

    /**
     * Disable captcha check
     *
     * @param int $id post id
     *
     * @return array
     */
    public static function getSlider(int $id = 0): array
    {
        if (!static::integration()) {
            return [];
        }

        $meta = get_post_meta($id);
        $meta_info = SafeAccess::fromArray($meta, '_cycloneslider_metas', 'array', []);
        if (empty($meta_info)) {
            return [];
        }
        $meta_info = unserialize(array_pop($meta_info));

        $attach = [];
        foreach ($meta_info as $single_meta) {
            $attach[] = [
                "attachment" => wp_get_attachment_url($single_meta['id']),
                "caption_title" => $single_meta['title'],
                "caption_description" => $single_meta['description'],
                "link" => $single_meta['link'],
                "link_target" => $single_meta['link_target'],
                "img_alt" => $single_meta['img_alt'],
                "img_title" => $single_meta['img_title'],
            ];
        }
        return $attach;
    }
}