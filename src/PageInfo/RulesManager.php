<?php

/**
 * Rules manager
 */

namespace Clickio\PageInfo;

use Clickio\Logger\Interfaces\ILogger;
use Clickio\Logger\Logger;
use Clickio\PageInfo\Services\PostMetaService;
use Clickio\Utils\SafeAccess;

/**
 * Manage field rules
 *
 * @package PageInfo
 */
class RulesManager
{

    /**
     * Where to search value
     *
     * @var string
     */
    protected $source = "";

    /**
     * Logger Instance
     *
     * @var ILogger
     */
    protected $log;

    /**
     * Constructor
     *
     * @param ILogger $logger logger instance
     * @param string $source field source
     */
    public function __construct(ILogger $logger, string $source)
    {
        $this->source = $source;
        $this->log = $logger;
    }

    /**
     * Apply rule
     *
     * @param array $rule field rule
     *
     * @return mixed
     */
    public function applyRule(array $rule)
    {
        $value = '';
        if ($this->source == 'post_meta') {
            $serv = PostMetaService::create($rule);
            if ($serv->isValid()) {
                $value = $serv->apply();
            } else {
                $msg = sprintf('%s: Invalid field rule', PostMetaService::class);
                $this->log->error($msg);
            }
        }
        return $value;
    }

    /**
     * Shorthand for construct and then applyRule
     *
     * @param array $rule field rule
     *
     * @return mixed
     */
    public static function apply(array $rule)
    {
        $source = SafeAccess::fromArray($rule, 'source', 'string', '');
        $host = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $logger = Logger::getLogger($host);
        $obj = new static($logger, $source);

        return $obj->applyRule($rule);
    }
}