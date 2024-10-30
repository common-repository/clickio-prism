<?php

/**
 * Post filter interface
 */

namespace Clickio\Listings\Interfaces;

/**
 * Post filter interface
 *
 * @package Listings\Interfaces
 */
interface IPostFilter extends IFilter
{
    const BUILDER_TYPE = IPostBuilder::class;
}