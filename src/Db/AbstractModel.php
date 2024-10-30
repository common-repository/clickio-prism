<?php

/**
 * Abstract model
 */

namespace Clickio\Db;

use Clickio\Logger\Interfaces\ILogger;

/**
 * Abstract model
 *
 * @package Db
 */
abstract class AbstractModel
{
    /**
     * Logger instance
     *
     * @var ILogger
     */
    protected $logger = null;

    /**
     * Table name
     *
     * @var string
     */
    protected $table = '';

    /**
     * Database obj
     *
     * @var ddd
     */
    protected $db = null;

    /**
     * Constructor
     *
     * @param ILogger $logger logger inst
     */
    public function __construct(ILogger $logger)
    {
        global $wpdb;

        $this->logger = $logger;
        $this->db = $wpdb;
    }

    /**
     * Write into info log
     *
     * @param string $msg log text
     *
     * @return void
     */
    protected function log(string $msg)
    {
        $msg = sprintf("%s: %s", static::class, $msg);
        $this->logger->info($msg);
    }

    /**
     * Write into debug log
     *
     * @param string $msg log text
     * @param array $debug_info additional debug info
     *
     * @return void
     */
    protected function debug(string $msg, array $debug_info = [])
    {
        $msg = sprintf("%s: %s", static::class, $msg);
        $this->logger->debug($msg, $debug_info);
    }

    /**
     * Table name getter
     *
     * @return string
     */
    public function getTableName(): string
    {
        global $wpdb;
        if (empty($this->table)) {
            throw new \Exception("Table name can't be empty");
        }
        return sprintf("%s%s", $wpdb->prefix, $this->table);
    }
}