<?php

/**
 * Check for updates
 */

namespace Clickio\RestApi\Actions;

use Clickio\ClickioPlugin;
use Clickio\Request\Request;
use Clickio\RestApi\BaseRestAction;
use Clickio\RestApi\Interfaces\IRestApi;

/**
 * Check for updates
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/check_update/
 *
 * @package RestApi\Actions
 */
class CheckUpdate extends BaseRestAction implements IRestApi
{
    /**
     * Plugin updates endpoint
     *
     * @var string
     */
    protected $url = "https://api.wordpress.org/plugins/update-check/1.1/";

    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        if (empty($wp_version)) {
            include ABSPATH . WPINC . '/version.php';
        }

        $plugin = [
            'plugins' =>
            [
                'clickio-prism/clickioprism.php' =>
                [
                    'Name' => 'Clickio Prism Plugin',
                    'PluginURI' => '',
                    'Version' => \CLICKIO_PRISM_VERSION,
                    'Description' => 'Transform your website with Clickio Prism',
                    'Author' => 'Clickio',
                    'AuthorURI' => 'https://clickio.com',
                    'TextDomain' => 'clickioprism',
                    'DomainPath' => '/languages',
                    'Network' => false,
                    'RequiresWP' => '',
                    'RequiresPHP' => '',
                    'Title' => 'Clickio Prism Plugin',
                    'AuthorName' => 'Clickio',
                ],
                'active' =>
                [
                    0 => 'clickio-prism/clickioprism.php',
                ],
            ]
        ];
        $body = [
            'plugins' => wp_json_encode($plugin),
            'translations' => '[]',
            'locale' => '[]',
            'all' => 'true',
        ];

        $req = Request::create();
        $req->ua = 'WordPress/'.$wp_version.'; '.home_url('/');
        $req->signed = false;
        $req->timeout = 12;
        $resp = $req->post($this->url, $body);
        $parsed = json_decode($resp->body, true);

        $dbg_data = ($body['plugins'] = json_decode($body['plugins'], true));
        $this->_addDebugInfo($this->url, $dbg_data, $parsed);
        return $parsed;
    }

    /**
     * Write debug info
     *
     * @param string $url api.wordpress.org endpoint
     * @param array $request request body
     * @param array $response response body
     *
     * @return void
     */
    private function _addDebugInfo(string $url, array $request, array $response)
    {
        $debug_data = [
            "request" => [
                "url" => $url,
                "body" => $request
            ],
            "response" => $response
        ];
        static::logDebug("Check updates", $debug_data);
    }
}
