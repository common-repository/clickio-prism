<?php

/**
 * Term filter interface
 */

namespace Clickio\Listings\Interfaces;

/**
 * Term filter interface
 *
 * @package Listings\Interfaces
 */
interface ITermFilter extends IFilter
{
    const BUILDER_TYPE = ITermBuilder::class;
}