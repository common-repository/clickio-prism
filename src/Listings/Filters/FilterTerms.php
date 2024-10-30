<?php

/**
 * Terms filter
 */

namespace Clickio\Listings\Filters;

use Clickio\Listings\BuilderFactory;
use Clickio\Listings\Containers\TermFilterParamsContainer;
use Clickio\Listings\Interfaces\ITermFilter;

/**
 * Filter terms
 *
 * @package Listings
 */
class FilterTerms extends AbstractFilter implements ITermFilter
{

    /**
     * Factory method.
     * Filter items by params
     *
     * @param array $params query params
     *
     * @return array
     */
    public static function getData(array $params): array
    {
        $query_result = [];
        $exclude = [];
        foreach ($params as $param) {
            $query_params = TermFilterParamsContainer::create($param);
            $query_params->addExclude($exclude);

            $builder = BuilderFactory::createTermBuilder($query_params->taxonomy, []);

            $terms = $builder->build($query_params);

            foreach ($terms->itemsGenerator() as $item) {
                $exclude[] = $item->id;
            }

            $query_result[] = $terms->toArray();
        }
        return $query_result;
    }
}