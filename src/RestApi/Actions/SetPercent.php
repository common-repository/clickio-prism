<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio as org;
use Clickio\RestApi as rest;

/**
 * Set percent option
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/percent/(?P<percent>\d+)/
 *
 * @package RestApi\Actions
 */
class SetPercent extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $percent = $this->request->get_param('percent');
        org\Options::set('percent', $percent);
        org\Options::save();
        return ['Ok', $percent];
    }
}