<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;

/**
 * Set ab
 * Setting up "clab" cookie
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/ab/(?P<id>\d+)/
 *
 * @package RestApi\Actions
 */
class SetAB extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $ab = $this->request->get_param('id');
        if ($ab != '1') {
            $ab = '0';
        }
        setcookie("clab", $ab, time()+3600, '/');
        return ['Ok', $ab ];
    }
}