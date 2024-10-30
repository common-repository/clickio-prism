<?php

/**
 * Model factory
 */

namespace Clickio\Db;

use Clickio\Db\Interfaces\IModel;
use Clickio\Logger\Logger;
use Clickio\Utils\SafeAccess;

/**
 * Model factory
 *
 * @package Db
 */
class ModelFactory
{

    /**
     * Factory method
     *
     * @param string $cls model class
     * @param array $args extra args
     *
     * @return IModel
     */
    public static function create(string $cls, array $args = []): IModel
    {
        $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $logger = Logger::getLogger($domain);

        $obj = new $cls($logger, ...$args);
        return $obj;
    }
}