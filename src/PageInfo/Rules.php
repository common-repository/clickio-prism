<?php
/**
 * PageInfo config
 */

namespace Clickio\PageInfo;

use Clickio\Logger\Interfaces\ILogger;
use Clickio\Logger\Logger;
use Clickio\Options;
use Clickio\Utils\SafeAccess;

/**
 * Additional rules how to create page info array
 *
 * @package PageInfo
 */
class Rules
{

    /**
     * Raw rulles array
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Logger
     *
     * @var ILogger
     */
    protected $log;

    /**
     * Singletone container
     *
     * @var Rules
     */
    private static $_inst = null;

    /**
     * Constructor
     *
     * @param ILogger $logger logger instance
     * @param array $cfg raw page rules
     */
    public function __construct(ILogger $logger, array $cfg)
    {
        $this->log = $logger;
        $this->rules = $cfg;
    }

    /**
     * Factory method
     *
     * @return Rules
     */
    public static function create(): self
    {
        if (empty(static::$_inst)) {
            $raw_cfg = Options::get("pageinfo_config");
            $cfg = json_decode($raw_cfg, true);
            if (empty($cfg)) {
                $cfg = [];
            }
            $host = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
            $logger = Logger::getLogger($host);
            static::$_inst = new static($logger, $cfg);
        }

        return static::$_inst;
    }

    /**
     * Getter.
     * Get all rules
     *
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get rule by key
     *
     * @param string $rule_name rule key
     *
     * @return array
     */
    public function getRule(string $rule_name): array
    {
        $rule = [];
        if (array_key_exists($rule_name, $this->rules)) {
            $rule = $this->rules[$rule_name];
        }
        return $rule;
    }
}