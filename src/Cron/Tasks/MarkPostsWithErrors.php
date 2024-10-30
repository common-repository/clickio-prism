<?php

/**
 * Cron task
 */

namespace Clickio\Cron\Tasks;

use Clickio as org;
use Clickio\Cron as cron;
use Clickio\Utils\SafeAccess;
use WP_Error;

/**
 * Cron task.
 * Mark all invalid posts with special key
 *
 * @package Cron\Tasks
 */
class MarkPostsWithErrors extends cron\CronTaskBase implements cron\Interfaces\ICronTask
{
    /**
     * ClickHouse errors endpoint
     *
     * @var string
     */
    const CLICKHOUSE_ERRORS = "https://platform.clickio.com/PublicRestApi/getClickHouseErrors";

    /**
     * ClickHouse policy pages
     *
     * @var string
     */
    const POLICY_ERRORS = "https://platform.clickio.com/PublicRestApi/getPolicyErrors";

    /**
     * Entry point
     *
     * @return void
     */
    public function run()
    {
        $plugin_amp = org\Options::get("useamp");
        $clickio_amp = org\Options::get("amp_status");
        if (empty($plugin_amp) && $clickio_amp != 'active') {
            $this->info("Unable to mark posts with amp errors - AMP is disabled. ");
            return ;
        }
        $this->info("Start marking posts with amp errors");

        $this->purgeErrors();
        $ids_list = $this->getTargetPostsIds();
        $this->markPosts($ids_list);
    }

    /**
     * Get invalid posts ids
     *
     * @return array
     */
    protected function getTargetPostsIds(): array
    {
        $ids_list = [];
        $host = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $validation_pages = $this->getValidationPages($host);

        $policy_pages = [];
        if (!org\Options::get("use_amp_on_policy_pages", 0)) {
            $policy_pages = $this->getPolicyPages($host);
        }
        $url_list = array_merge($validation_pages, $policy_pages);

        $cleaned_list = array_unique($url_list);

        foreach ($cleaned_list as $link) {
            $post_id = url_to_postid($link);
            if (!empty($post_id)) {
                $ids_list[] = $post_id;
            }
        }
        return $ids_list;
    }

    /**
     * Set marks on each post
     *
     * @param array $id_list posts id list
     *
     * @return void
     */
    protected function markPosts(array $id_list)
    {
        foreach ($id_list as $post_id) {
            update_post_meta($post_id, org\ClickioPlugin::CLICKHOUSE_ERROR_KEY, 1);
        }
    }

    /**
     * Purge previous errors
     *
     * @return void
     */
    protected function purgeErrors()
    {
        $query = [
            "meta_key" => org\ClickioPlugin::CLICKHOUSE_ERROR_KEY
        ];

        $posts = get_posts($query);
        foreach ($posts as $post) {
            $id = $post->ID;
            delete_post_meta($id, org\ClickioPlugin::CLICKHOUSE_ERROR_KEY);
        }
    }

    /**
     * List of urls with errors
     *
     * @param string $domain FQDN
     *
     * @return array
     */
    protected function getValidationPages(string $domain): array
    {
        $url_list = [];
        $query = ["domain" => $domain];

        $args = [
            'timeout'   => 10,
            'blocking'  => true,
            'sslverify' => false,
            'user-agent' => 'WP'
        ];

        $url = sprintf("%s?%s", static::CLICKHOUSE_ERRORS, http_build_query($query));
        $req = wp_remote_get($url, $args);

        if (!($req instanceof WP_Error)
            && !empty($req)
            && !empty($req['response'])
            && $req['response']['code'] == 200
        ) {
            $data = json_decode($req['body'], true);
            if ($data["error_id"] == 200) {
                $url_list = $data['data'];
            }
        }
        return $url_list;
    }

    /**
     * List of policy pages
     *
     * @param string $domain FQDN
     *
     * @return array
     */
    protected function getPolicyPages(string $domain): array
    {
        $url_list = [];
        $query = ["domain" => $domain];

        $args = [
            'timeout'   => 120,
            'blocking'  => true,
            'sslverify' => false,
            'user-agent' => 'WP'
        ];

        $url = sprintf("%s?%s", static::POLICY_ERRORS, http_build_query($query));
        $req = wp_remote_get($url, $args);

        if (!($req instanceof WP_Error)
            && !empty($req)
            && !empty($req['response'])
            && $req['response']['code'] == 200
        ) {
            $data = json_decode($req['body'], true);
            if ($data["error_id"] == 200) {
                $url_list = $data['data'];
            }
        }
        return $url_list;
    }
}
