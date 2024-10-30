<?php
/**
 * Extra content wrapper
 */

namespace Clickio\ExtraContent;

use Clickio\ExtraContent\Interfaces\IExtraContentService;
use Clickio\Logger\LoggerAccess;
use Clickio\PageInfo\Rules;
use Clickio\Utils\SafeAccess;
use Exception;

/**
 * Facade. Extra content wrapper
 *
 * @package ExtraContent
 */
final class ExtraContent
{
    /**
     * Simplified access to logger
     */
    use LoggerAccess;

    /**
     * List of extra content service names
     *
     * @var array
     */
    protected $services = [];

    /**
     * Extra content container
     *
     * @var array
     */
    protected $extra = [];

    /**
     * Extra content rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Constructor
     *
     * @param array $services list of services FQCN
     */
    public function __construct(array $services)
    {
        $this->services = $services;
        $this->rules = (Rules::create())->getRule("extra");
    }

    /**
     * Get extra content from selected services
     *
     * @param bool $force ignore settings
     *
     * @return array
     */
    public function getExtraContent(bool $force = false): array
    {

        foreach ($this->services as $service_alias) {
            try {
                $service = ExtraContentServiceFactory::create($service_alias);
            } catch (Exception $e) {
                static::logError($e->getMessage());
            }

            $key = $service::getName();
            $rules = SafeAccess::fromArray($this->rules, $key, 'array', []);
            $service->setRules($rules);
            $extra = $service->getExtraContent($force);

            if (!empty($extra)) {
                $this->extra[$key] = $extra;
            }
        }

        return $this->extra;
    }

    /**
     * Factory method.
     * Creates self with all service
     *
     * @param array $services service name list
     *
     * @return self
     */
    public static function create(array $services = []): self
    {
        if (empty($services)) {
            $services = ExtraContentServiceFactory::getAllServices();
        }
        return new static($services);
    }

    /**
     * Getter
     * Get all services
     *
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }
}
