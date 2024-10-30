<?php

/**
 * Filter interface
 */

namespace Clickio\Listings\Interfaces;

/**
 * Filter interface
 *
 * @package Listings\Interfaces
 */
interface IFilter
{
    /**
     * Factory method.
     * Filter items by params
     *
     * @param array $params query params
     *
     * @return array
     */
    public static function getData(array $params): array;
}