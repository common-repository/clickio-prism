<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio as org;
use Clickio\RestApi as rest;
use Clickio\Utils\SafeAccess;

/**
 * Get plugin settings
 * For historical reasons, the endpoint name does not reflect the result.
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/version/
 *
 * @package RestApi\Actions
 */
class Version extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $options = org\Options::getOptions();
        $theme = wp_get_theme();
        $name = $theme->get('Name');
        $_url = $theme->get('ThemeURI');
        $url = empty($_url)? $theme->get('AuthorURI') : $theme->get('ThemeURI');
        $ver = $theme->get('Version');

        $options["version"] = CLICKIO_PRISM_VERSION;
        $options['theme'] = sprintf("%s/%s (%s)", $name, $ver, $url);
        return $options;
    }
}
