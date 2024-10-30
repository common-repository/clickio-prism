<?php

/**
 * Listen all queries to find listings params
 */

namespace Clickio\Utils;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Logger\LoggerAccess;
use WP_Query;

/**
 * Listen queries
 *
 * @package Utils
 */
final class QueryMonitor
{
    use LoggerAccess;
    /**
     * Singletone container
     *
     * @var self
     */
    protected static $inst = null;

    /**
     * Transient name
     *
     * @var string
     */
    const TRANSIENT_NAME = '_clickio_query_monitor_items';


    /**
     * Monitor directory
     *
     * @var string
     */
    const MONITOR_PATH = ABSPATH.'clickio_logs';

    /**
     * Monitor file
     *
     * @var string
     */
    const MONITOR_FILE = self::MONITOR_PATH.'/listings';

    /**
     * Catched queries
     *
     * @var array
     */
    protected $queries = [];

    /**
     * Ignore post types
     *
     * @var array
     */
    protected $post_type_blacklist = [
        "wp_template",
        "wp_theme",
        "nav_menu_item"
    ];

    /**
     * Get singletone instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (empty($inst)) {
            static::$inst = new static();
        }

        return static::$inst;
    }

    /**
     * Setup event listners
     *
     * @return void
     */
    public static function setupListners()
    {
        $param = SafeAccess::fromArray($_REQUEST, 'query_monitor', 'string', '');

        if (empty($param)) {
            return ;
        }

        $inst = static::getInstance();
        add_action("the_posts", [$inst, 'listenGetPost'], 10, 2);
        add_action("pre_get_posts", [$inst, 'preGetPosts'], 10, 1);
        add_action("shutdown", [$inst, 'saveQueries']);

        $docket_cache = IntegrationServiceFactory::getService('docket_cache');
        $docket_cache::disable();
    }

    /**
     * Set query params
     *
     * @param WP_Query $query next query params
     *
     * @return void
     */
    public function preGetPosts($query)
    {
        $query->query_vars['suppress_filters'] = false;
        $query->query['suppress_filters'] = false;
    }

    /**
     * Listner.
     * Handler for the_posts
     *
     * @param array $posts selected items
     * @param WP_Query $query query to be executed
     *
     * @return void
     */
    public function listenGetPost($posts, $query)
    {
        $type = SafeAccess::fromArray($query->query, 'post_type', 'string', 'post');
        if (in_array($type, $this->post_type_blacklist)) {
            return $posts;
        }

        $params['query'] = $query->query;
        $params['sql'] = $query->request;
        $params['posts'] = [];

        $sc_re = get_shortcode_regex();
        foreach ($posts as $post) {

            $thumbnail = get_post_thumbnail_id($post->ID);
            $title = $post->post_title;
            $id = $post->ID;
            $content = preg_replace('/'.$sc_re.'/', '', $post->post_content);
            $params['posts'][] = [
                "id" => $id,
                "title" => $title,
                "excerpt" => substr(strip_tags($content), 0, 100),
                "img" => ImageInfo::getFullThumbnail($thumbnail)
            ];
        }
        $this->queries[] = $params;
        return $posts;
    }

    /**
     * Save founded queries
     *
     * @return void
     */
    public function saveQueries()
    {
        $this->cleanMonitoringData();
        $res = set_transient(static::TRANSIENT_NAME, $this->queries, DAY_IN_SECONDS);
        if (!$res && is_writable(self::MONITOR_PATH)) {
            file_put_contents(self::MONITOR_FILE, serialize($this->queries));
        }
    }

    /**
     * Getter.
     * Get saved queries
     *
     * @return array
     */
    public function getQueries(): array
    {
        if (is_readable(self::MONITOR_FILE)) {
            $_data = file_get_contents(self::MONITOR_FILE);
            $data = unserialize($_data);
        } else {
            $data = get_transient(static::TRANSIENT_NAME);
        }
        if (empty($data)) {
            $data = [];
        }
        $this->cleanMonitoringData();
        return $data;
    }

    /**
     * Clean monitoring data
     *
     * @return void
     */
    protected function cleanMonitoringData()
    {
        if (is_readable(self::MONITOR_FILE)) {
            wp_delete_file(self::MONITOR_FILE);
        }

        delete_transient(self::TRANSIENT_NAME);
    }
}
