<?php
/**
 * Taxonomy term meta
 */

namespace Clickio\Meta;

use Clickio\Integration\Services\GenesisFramework;
use Clickio\Integration\Services\WPSEO;
use Clickio\Logger\LoggerAccess;
use WP_Term;

/**
 * Term meta info
 *
 * @package Meta
 */
class TermMeta
{
    use LoggerAccess;
    /**
     * Taxonomy term
     *
     * @var WP_Term
     */
    protected $term = null;

    /**
     * Constructor
     *
     * @param WP_Term $term taxonomy term
     */
    public function __construct(WP_Term $term)
    {
        $this->term = $term;
    }

    /**
     * Factory method.
     * Create new term from term id
     *
     * @param int $term_id term id
     *
     * @return self
     */
    public static function createFromId($term_id): self
    {
        if (empty($term_id)) {
            static::logError("Empty taxonomy id was received");
            throw new \Exception("Empty taxonomy not allowed");
        }

        $term = get_term($term_id);
        if (empty($term) || is_wp_error($term)) {
            throw new \Exception("Taxonomy with term_id $term_id doesn't exists");
        }
        return new static($term);
    }

    /**
     * Term url
     *
     * @return string
     */
    public function getPermalink(): string
    {
        return get_term_link($this->term->term_id, $this->term->taxonomy);
    }

    /**
     * Get term slug
     *
     * @return string
     */
    public function getTermSlug(): string
    {
        return $this->term->slug;
    }

    /**
     * Term description
     *
     * @return string
     */
    public function getDescription(): string
    {
        $desc = '';
        $desc_list = [
            GenesisFramework::getTermMeta('description', $this->term->term_id),
            apply_filters('category_description', $this->term->description, 0),
            WPSEO::getCategoryMeta('description', $this->term->term_id)
        ];

        foreach ($desc_list as $description) {
            if (!empty($description)) {
                $desc = $description;
            }
        }
        return $desc;
    }

    /**
     * Wrapper around get_terms to add term link
     *
     * @param string $taxonomy taxonomy name
     * @param array $opts query condition
     *
     * @return array
     */
    public static function getTerms(string $taxonomy, array $opts): array
    {
        $terms = get_terms($taxonomy, $opts);
        foreach ($terms as $cat) {
            $term = static::createFromId($cat->term_id);
            $cat->link = $term->getPermalink();
            $cat->description = $term->getDescription();
            $meta = $term->getTermMeta();
            $cat->meta = empty($meta)? (object)[] : $meta;
        }
        if (empty($terms)) {
            $terms = [];
        }
        return array_values($terms);
    }

    /**
     * Get term meta
     *
     * @return array
     */
    public function getTermMeta(): array
    {
        $meta = [];
        $_ba_meta = $this->getBainternetTaxMetaClass();
        $meta = array_merge($meta, $_ba_meta);
        return $meta;
    }

    /**
     * Get meta values added by https://github.com/bainternet/Tax-Meta-Class
     *
     * @return array
     */
    protected function getBainternetTaxMetaClass(): array
    {
        $items = get_option("tax_meta_{$this->term->term_id}");
        if (empty($items)) {
            return [];
        }
        return $items;
    }
}
