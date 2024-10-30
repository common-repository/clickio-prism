<?php

/**
 * Breadcrummbs NavXT
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use DOMDocument;

/**
 * Integration with Breadcrummbs NavXT
 *
 * @package Integration\Services
 */
final class BreadcrumbsNavXt extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'breadcrumb-navxt/breadcrumb-navxt.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'navxt';

    /**
     * Get breadcrumbs
     *
     * @return array
     */
    public static function getBreadcrumbs(): array
    {
        if (!static::integration() || !class_exists("DomDocument")) {
            return [];
        }

        $breadcrumbs = [];
        try {
            ob_start();
            $args = [
                "before_widget" => "",
                "after_widget" => "",
                "before_title" => "",
                "after_title" => ""
            ];
            \the_widget('bcn_widget', [], $args);
            $raw_html = ob_get_clean();
            ob_end_clean();
        } catch (\Exception $err) {
            $raw_html = '';
        }

        $dom = new DOMDocument();
        $dom->loadHTML($raw_html);
        $urls = $dom->getElementsByTagName('a');
        if (empty($urls) || empty($urls->length)) {
            return [];
        }

        foreach ($urls as $node) {
            $struct = [];
            $struct['link'] = $node->attributes->getNamedItem('href')->value;
            $struct['name'] = $node->textContent;
            $breadcrumbs[] = $struct;
        }

        return $breadcrumbs;
    }
}