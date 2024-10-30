<?php
/**
 * Listing builder interface
 */

namespace Clickio\Listings\Interfaces;

use Clickio\Listings\Containers\FilterListingContainer;
use Clickio\Utils\Container;

/**
 * Listing builder interface
 *
 * @package Listings\Interfaces
 */
interface IListingBuilder
{
    /**
     * Builder main function
     *
     * @param Container $container query result
     *
     * @return FilterListingContainer
     */
    public function build(Container $container): FilterListingContainer;

    /**
     * Get builder alias
     *
     * @return string
     */
    public static function getAlias(): string;
}