<?php

/**
 * Params to query terms
 */

namespace Clickio\Listings\Containers;

use Clickio\Utils\Container;

/**
 * Term query params
 *
 * @package Listings\Containers
 */
class TermFilterParamsContainer extends Container
{
    /**
     * Listing id
     * Request param: id
     *
     * @var ?int
     */
    protected $id;

    /**
     * Items count
     * Request param: number / posts_per_page / per_page
     *
     * @var int
     */
    protected $number = 5;

    /**
     * Ordering direction
     * Request param: order
     *
     * @var string
     */
    protected $order = 'DESC';

    /**
     * Ordering field e.g. SELECT ... FROM ... ORDER BY $orderby
     * Request param: orderby
     *
     * @var string
     */
    protected $orderby = 'name';

    /**
     * Search in taxonomy
     * Request param: taxonomy / endpoint / post_type
     *
     * @var string
     */
    protected $taxonomy = 'post_tag';

    /**
     * Exclude terms by id
     * Request param: exclude / post__not_in / post_exclude
     *
     * @var array
     */
    protected $exclude = [];

    /**
     * Don not show terms without posts
     *
     * @var bool
     */
    protected $hide_empty = true;

    /**
     * Setter
     * Alias for "number"
     * Request param: per_page
     *
     * @param mixed $val expected number
     *
     * @return void
     */
    public function setPerPage($val)
    {
        if (is_numeric($val)) {
            $this->number = intval($val, 10);
        }
    }

    /**
     * Setter
     * Alias for "number"
     * Request param: posts_per_page
     *
     * @param mixed $val expected number
     *
     * @return void
     */
    public function setPostsPerPage($val)
    {
        $this->setPerPage($val);
    }

    /**
     * Setter
     * Alias for "taxonomy"
     * Request param: endpoint
     *
     * @param mixed $val expected string, taxonomy name
     *
     * @return void
     */
    public function setEndpoint($val)
    {
        $this->taxonomy = (string)$val;
    }

    /**
     * Add exclude ids
     *
     * @param array $val list of terms id
     *
     * @return void
     */
    public function addExclude($val)
    {
        $this->exclude = array_merge($this->exclude, $val);
    }

    /**
     * Setter
     * Alias for "exclude"
     * Request param: post__not_in
     *
     * @param mixed $val expected array, list of terms id
     *
     * @return void
     */
    public function setPostNotIn($val)
    {
        $this->exclude = $val;
    }

    /**
     * Setter
     * Alias for "exclude"
     * Request param: post_exclude
     *
     * @param mixed $val expected array, list of terms id
     *
     * @return void
     */
    public function setPostExclude($val)
    {
        $this->exclude = $val;
    }
}