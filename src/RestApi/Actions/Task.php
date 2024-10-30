<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;
use Clickio\Tasks\TaskManager;
use WP_REST_Response;

/**
 * Delayed tasks
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/task/
 *
 * @package RestApi\Actions
 */
class Task extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{

    /**
     * Handle http get method
     *
     * @return array
     */
    public function get()
    {
        return TaskManager::getTaskQueue();
    }

    /**
     * Handle http delete method
     *
     * @return array
     */
    public function delete()
    {
        $body = $this->request->get_body();
        $params = json_decode($body, true);
        if (empty($params) || !array_key_exists('id', $params) || empty($params['id'])) {
            return new WP_REST_Response(["code" => 400, "msg" => 'invalid_request'], 400);
        }

        $id = $params['id'];
        $task = TaskManager::getTask($id);
        if (!empty($task)) {
            TaskManager::cancel($id);
        }

        return new WP_REST_Response(null, 202);
    }
}
