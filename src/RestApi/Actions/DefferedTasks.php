<?php
/**
 * Run deffered tasks if DISABLED_WP_CRON == true
 */

namespace Clickio\RestApi\Actions;

use Clickio\Cron as cron;
use Clickio\Cron\CronTaskManager;
use Clickio\RestApi as rest;
use WP_HTTP_Response;

/**
 * Run deffered tasks if DISABLED_WP_CRON == true
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/run_deffered/
 *
 * @package RestApi\Actions
 */
class DefferedTasks extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{

    /**
     * Handle http post method
     *
     * @return mixed
     */
    public function get()
    {
        return new WP_HTTP_Response(null, 423);
        // $task = $this->request->get_param("task");
        // if (empty($task)) {
        //     return ["status" => false, "message" => "Empty task"];
        // }

        // $event = cron\Events::getEvent($task);
        // if (empty($event)) {
        //     return ["status" => false, "message" => "Unknown event"];
        // }

        // try {
        //     @do_action($task);
        // } catch (\Exception $e) {
        //     return ["status" => false, "message" => $e->getMessage()];
        // }
        // return ["status" => true, "message" => "Success"];
    }
}
