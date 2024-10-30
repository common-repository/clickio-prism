<?php
/**
 * Listings
 */

namespace Clickio\Listings;

use Clickio\Utils\Container;

/**
 * Listings container
 *
 * @package Listings
 */
final class FilterListingContainer extends Container
{

    /**
     * Listing ID
     *
     * @var mixed
     */
    protected $id;

    /**
     * Total count posts for condition
     *
     * @var int
     */
    protected $total_count = 0;

    /**
     * Taxonomy term name
     *
     * @var string
     */
    protected $taxonomy_term = '';

    /**
     * Taxonomy name
     *
     * @var string
     */
    protected $taxonomy = "";

    /**
     * Listing items
     *
     * @var array
     */
    protected $Listings = [];

    /**
     * Add listing item
     *
     * @param FilterItemContainer $item listing item
     *
     * @return void
     */
    public function addItem(FilterItemContainer $item)
    {
        $this->Listings[] = $item;
    }

    /**
     * Get raw listing item by index
     *
     * @param int $idx item index
     *
     * @return FilterItemContainer
     */
    public function getItemByIndex(int $idx): FilterItemContainer
    {
        if (array_key_exists($idx, $this->Listings)) {
            return $this->Listings[$idx];
        }

        throw new \Exception("Listing with index $idx not found");
    }

    /**
     * Iterate over raw items
     *
     * @return Generator
     */
    public function itemsGenerator()
    {
        foreach ($this->Listings as $idx => $list) {
            yield $idx => $list;
        }
    }

    /**
     * Getter
     * Convert all listing items into array
     *
     * @return array
     */
    public function getListings(): array
    {
        $items = [];
        foreach ($this->Listings as $list) {
            $items[] = $list->toArray();
        }
        return $items;
    }
}