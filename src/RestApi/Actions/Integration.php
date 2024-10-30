<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Integration\IntegrationManager;
use Clickio\RestApi as rest;

/**
 * Show integration with thirdparty plugins
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/integration/
 *
 * @package RestApi\Actions
 */
class Integration extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        $mngr = new IntegrationManager();
        return $mngr->getIntegrationList();
    }
}