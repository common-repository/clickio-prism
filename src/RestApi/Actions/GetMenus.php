<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;

/**
 * Get menu list
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/menus/
 *
 * @package RestApi\Actions
 */
class GetMenus extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        return wp_get_nav_menus();
    }
}