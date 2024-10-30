<?php

/**
 * Delayed task
 */

namespace Clickio\Tasks\Tasks;

use Clickio\CacheControl\CacheManager;
use Clickio\Tasks\Interfaces\ISyncTask;

/**
 * Purge cache
 *
 * @package Tasks\Tasks
 */
class ClearCache extends AbstractTask implements ISyncTask
{
    /**
     * Task name
     *
     * @var string
     */
    const NAME = "purge_cache";

    /**
     * Cleaners to start
     *
     * @var array
     */
    protected $cleaners = [];

    /**
     * Urls to be purged
     *
     * @var array
     */
    protected $urls = [];

    /**
     * Flag. Purge all
     *
     * @var bool
     */
    protected $all = false;

    /**
     * Flag. Purge canonical
     *
     * @var bool
     */
    protected $canonical = false;

    /**
     * Flag. Purge internal cache
     *
     * @var bool
     */
    protected $internal = false;

    /**
     * Constructor
     *
     * @param array $cleaners List of cache cleaners
     * @param array $url_list Urls to be purged
     * @param bool $purge_all Purge all
     * @param bool $canonical Purge canonical
     * @param bool $internal Purge internal cache
     */
    public function __construct(
        array $cleaners = [],
        array $url_list = [],
        bool $purge_all = false,
        bool $canonical = false,
        bool $internal = false
    ) {
        $this->cleaners = $cleaners;
        $this->urls = $url_list;
        $this->all = $purge_all;
        $this->canonical = $canonical;
        $this->internal = $internal;
    }

    /**
     * Task entrypoint
     *
     * @return void
     */
    public function run()
    {
        CacheManager::purge($this->all, $this->urls, $this->canonical, $this->internal, $this->cleaners);
    }
}
