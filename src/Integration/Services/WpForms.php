<?php
/**
 * WPForms Lite
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Actions with WPForms Lite plugin
 *
 * @package Integration\Services
 */
final class WpForms extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wpforms-lite/wpforms.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wpforms';

    /**
     * Fix form action and set "ajax submit" setting
     *
     * @return void
     */
    public static function adaptFormForPrism()
    {
        if (!static::integration()) {
            return ;
        }

        add_action('wpforms_frontend_form_atts', [static::class, 'adaptFormForPrismAction'], 1000, 1);
    }

    /**
     * WpForms internal acation. Don't use this directly.
     *
     * @param mixed $form_data html form attributes
     *
     * @return mixed
     */
    public static function adaptFormForPrismAction($form_data)
    {
        $exploded = explode('?', $form_data['atts']['action']);
        $query = html_entity_decode($exploded[1]);
        $query = explode('&', $query);
        $new_query = [];
        foreach ($query as $param) {
            list($name, $val) = explode("=", $param);
            if (in_array($name, ['get_id', 'get_extra_content'])) {
                continue ;
            }

            $new_query[$name] = $val;
        }

        $url = sprintf("%s?%s", $exploded[0], http_build_query($new_query));
        $form_data['atts']['action'] = $url;
        $form_data['class'][] = 'wpforms-ajax-form';
        return $form_data;
    }
}