<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;

/**
 * Get taxonomies
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/taxonomies
 *
 * @package RestApi\Actions
 */
class Taxonomies extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{

    /**
     * Extra taxonomies
     *
     * @var array
     */
    protected $extra = [
        "post_format",
        "post_tag",
        "wp_template",
        "wp_template_part",
        "wp_global_styles",
        "wp_navigation",
    ];
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $taxes = get_taxonomies(['public' => true]);
        $out = [];
        foreach ($taxes as $tax) {
            $tax_info = get_taxonomy($tax);
            $out[] = [
                "name" => $tax_info->name,
                "label" => $tax_info->label,
                "terms" => array_values(get_terms(['taxonomy' => $tax]))
            ];
        }
        $extra_tax = $this->getExtraTaxes();
        $out = array_merge($out, $extra_tax);

        $existed = [];
        $out = array_filter(
            $out,
            function ($item) use (&$existed) {
                if (in_array($item['name'], $existed)) {
                    return false;
                }
                $existed[] = $item['name'];
                return true;
            }
        );
        return $out;
    }

    /**
     * Get extra taxonomies
     *
     * @return array
     */
    protected function getExtraTaxes(): array
    {
        $out = [];
        foreach ($this->extra as $tax_name) {
            $tax_array = get_taxonomies(["name" => $tax_name], 'objects');
            if (!empty($tax_array)) {
                $tax = array_pop($tax_array);
                $out[] = [
                    "name" => $tax->name,
                    "label" => $tax->label,
                    "terms" => array_values(get_terms(['taxonomy' => $tax_name]))
                ];
            }
        }
        return $out;
    }
}
