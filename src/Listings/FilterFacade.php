<?php

/**
 * Filter facade
 */

namespace Clickio\Listings;

use Clickio\Logger\Interfaces\ILogger;
use Clickio\Logger\Logger;
use Clickio\Utils\SafeAccess;

/**
 * Facade
 * Get filter result
 *
 * @package Listings
 */
final class FilterFacade
{

    /**
     * Logger instance
     *
     * @var ILogger
     */
    protected $logger = null;

    /**
     * Constructor
     *
     * @param ILogger $logger logger instance
     */
    public function __construct(ILogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get filter result
     *
     * @param array $data list filter params
     *
     * @return array
     */
    public function filter(array $data): array
    {
        $filters_data = $this->repackDataByFilters($data);
        $filter_result = [];
        foreach ($filters_data as $filter => $data) {
            $_result = $filter::getData($data);
            $filter_result = array_merge($filter_result, $_result);
        }

        usort(
            $filter_result,
            function ($current, $next) {
                $fid = $current['id'];
                $sid = $next['id'];
                return $fid < $sid? -1 : ($fid == $sid? 0 : 1);
            }
        );
        return $filter_result;
    }

    /**
     * Factory method
     *
     * @param ?ILogger $logger logger instance
     *
     * @return self
     */
    public static function create($logger = null): self
    {
        if (!$logger) {
            $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
            $logger = Logger::getLogger($domain);
        }

        return new static($logger);
    }

    /**
     * Map request data to filters
     *
     * @param array $data filters query params
     *
     * @return array
     */
    protected function repackDataByFilters(array $data): array
    {
        $_struct = [];
        foreach ($data as $query) {
            $endpoint = SafeAccess::fromArray($query, 'endpoint', 'string', 'post');
            $builder_type = BuilderFactory::getBuilderType($endpoint);
            if (empty($builder_type)) {
                $builder_type = BuilderFactory::getBuilderType('post');
            }
            foreach (FilterFactory::getFilters() as $filter) {
                if (is_a($builder_type, $filter::BUILDER_TYPE, true)) {
                    $_struct[$filter][] = $query;
                    break;
                }
            }
        }
        return $_struct;
    }
}