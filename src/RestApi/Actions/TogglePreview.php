<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\RestApi as rest;

/**
 * Toggle preview mode
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/toggle_preview/
 *
 * @package RestApi\Actions
 */
class TogglePreview extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{

    protected $preview_key = 'cl_debug';

    /**
     * Handle http get method
     *
     * @return array
     */
    public function get()
    {
        $msg = $this->toggleCookie($this->preview_key);
        return [$msg];
    }

    /**
     * Toggle preview in cookie
     *
     * @param string $preview_key cookie name
     *
     * @return string
     */
    protected function toggleCookie(string $preview_key): string
    {
        if (!empty($_COOKIE[$preview_key])) {
            setcookie($preview_key, "0", time() - 43200, '/');
            return "Cookie preview disabled";
        } else {
            setcookie($preview_key, "1", time() + 43200, '/');
            return "Cookie preview enabled";
        }
    }
}