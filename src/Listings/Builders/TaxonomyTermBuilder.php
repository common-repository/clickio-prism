<?php
/**
 * Attachment builder
 */

namespace Clickio\Listings\Builders;

use Clickio\Listings\Containers\FilterItemContainer;
use Clickio\Listings\Containers\FilterListingContainer;
use Clickio\Listings\Interfaces\ITermBuilder;
use Clickio\Meta\TermMeta;
use Clickio\Utils\Container;

/**
 * Build listing as attachment
 *
 * @package Listings\Builders
 */
final class TaxonomyTermBuilder extends AbstractBuilder implements ITermBuilder
{

    /**
     * Builder alias
     *
     * @var string
     */
    const ALIAS = 'post_tag';

    /**
     * Get builder alias
     *
     * @return string
     */
    public static function getAlias(): string
    {
        return static::ALIAS;
    }

    /**
     * Builder main function
     *
     * @param Container $params query result
     *
     * @return FilterListingContainer
     */
    public function build(Container $params): FilterListingContainer
    {


        $listing = new FilterListingContainer();
        $query = new \WP_Term_Query($params->toArray());
        $terms_array = $query->get_terms();

        $listing->id = $params->id;
        $listing->total_count = 0;
        $listing->taxonomy_term = "";
        $listing->taxonomy = $params->taxonomy;

        foreach ($terms_array as $term) {
            $meta = new TermMeta($term);
            $item = new FilterItemContainer();

            $item->id = $term->term_id;

            $item->type = $term->taxonomy;
            $item->link = $meta->getPermalink();
            $item->title = $term->name;
            $item->content = $term->description;

            $listing->addItem($item);
        }

        return $listing;
    }
}