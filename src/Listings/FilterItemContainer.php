<?php
/**
 * Listing item
 */

namespace Clickio\Listings;

use Clickio\Utils\Container;

/**
 * Listing item
 *
 * @package Listings
 */
final class FilterItemContainer extends Container
{
    /**
     * Post id
     *
     * @var int
     */
    protected $id;

    /**
     * Publication date
     *
     * @var string
     */
    protected $date;

    /**
     * Post guid
     *
     * @var array
     */
    protected $guid = [
        "rendered" => null
    ];

    /**
     * Post modification date
     *
     * @var string
     */
    protected $modified;

    /**
     * Post type
     *
     * @var string
     */
    protected $type;

    /**
     * Canonical url
     *
     * @var string
     */
    protected $link;

    /**
     * Post title
     *
     * @var array
     */
    protected $title = [
        "rendered" => ""
    ];

    /**
     * Post alt title
     *
     * @var string
     */
    protected $alt_title = [
        "rendered" => ""
    ];

    /**
     * Post format
     * Will be false when post has default format
     *
     * @var string|boolean
     */
    protected $format;

    /**
     * Reading time
     *
     * @var int
     */
    protected $read_time;

    /**
     * Post rating
     *
     * @var int
     */
    protected $rating = null;

    /**
     * Post image
     *
     * @var array|null
     */
    protected $WpFeaturedImage = null;

    /**
     * Post video
     *
     * @var string | null
     */
    protected $WpFeaturedVideo = null;

    /**
     * Post subtitle
     *
     * @var array
     */
    protected $subtitle = [
        "rendered" => null
    ];

    /**
     * Post excerpt
     *
     * @var array
     */
    protected $excerpt = [
        "rendered" => null
    ];

    /**
     * Post content
     *
     * @var array
     */
    protected $content = [
        "rendered" => null
    ];

    /**
     * List of post categories
     *
     * @var array
     */
    protected $WpCategoriesInfo = [];

    /**
     * Rest api links collection
     *
     * @var array
     */
    // @codingStandardsIgnoreLine
    protected $_links = [];

    /**
     * Author links
     *
     * @var array
     */
    protected $author = [];

    /**
     * Author info
     *
     * @var array
     */
    protected $WpAuthor;

    /**
     * Post comments
     *
     * @var array
     */
    protected $Comments;

    /**
     * Setter
     * Set post guid
     *
     * @param string $value post guid
     *
     * @return void
     */
    protected function setGuid(string $value)
    {
        $this->guid['rendered'] = $value;
    }

    /**
     * Setter
     * Set post title
     *
     * @param string $value post title
     *
     * @return void
     */
    protected function setTitle(string $value)
    {
        $this->title['rendered'] = $value;
    }

    /**
     * Setter
     * Set post alt title
     *
     * @param string $value post alt title
     *
     * @return void
     */
    protected function setAltTitle(string $value)
    {
        $this->alt_title['rendered'] = $value;
    }

    /**
     * Setter
     * Set post excerpt
     *
     * @param string $value post excerpt
     *
     * @return void
     */
    protected function setExcerpt(string $value)
    {
        $this->excerpt['rendered'] = $value;
    }

    /**
     * Getter
     * Get post excerpt
     *
     * @return string|null
     */
    protected function getExcerpt()
    {
        if ($this->excerpt['rendered'] !== null) {
            return $this->excerpt;
        }
        return null;
    }

    /**
     * Setter
     * Set post content
     *
     * @param string $value post content
     *
     * @return void
     */
    protected function setContent(string $value)
    {
        $this->content['rendered'] = $value;
    }

    /**
     * Getter
     * Get post content
     *
     * @return string|null
     */
    protected function getContent()
    {
        if ($this->content['rendered'] !== null) {
            return $this->content;
        }
        return null;
    }

    /**
     * Setter
     * Set post subtitle
     *
     * @param string $value post subtitle
     *
     * @return void
     */
    protected function setSubtitle(string $value)
    {
        $this->subtitle['rendered'] = $value;
    }

    /**
     * Getter
     * Get post subtitle
     *
     * @return string|null
     */
    protected function getSubtitle()
    {
        if ($this->subtitle['rendered'] !== null) {
            return $this->subtitle;
        }
        return null;
    }

    /**
     * Getter
     * Get post categories
     *
     * @return array|null
     */
    protected function getWpCategoriesInfo()
    {
        if (!empty($this->WpCategoriesInfo)) {
            return $this->WpCategoriesInfo;
        }
        return null;
    }

    /**
     * Getter
     * Get post api links
     *
     * @return array|null
     */
    protected function getLinks()
    {
        if (!empty($this->_links)) {
            return $this->_links;
        }
        return null;
    }

    /**
     * Getter
     * Get post author links
     *
     * @return array|null
     */
    protected function getAuthor()
    {
        if (!empty($this->author)) {
            return $this->author;
        }
        return null;
    }

    /**
     * Setter
     * Add post category
     *
     * @param array $value post category
     *
     * @return void
     */
    public function addCategory(array $value)
    {
        $this->WpCategoriesInfo[] = $value;
    }

    /**
     * Setter
     * Add rest api link
     *
     * @param string $name link name
     * @param string $url url
     *
     * @return void
     */
    Public function addApiLink(string $name, string $url)
    {
        $this->_links[$name] = [
            [
                "href" => $url
            ]
        ];
    }

    /**
     * Setter
     * Add author link
     *
     * @param array $link author link struct
     *
     * @return void
     */
    public function addAuthorLink(array $link)
    {
        $this->author[] = $link;
    }

    /**
     * Overrided
     * Do not include field when it's null
     *
     * @return array
     */
    public function toArray()
    {
        $arr = [];
        foreach (array_keys(get_object_vars($this)) as $field) {
            $value = $this->__get($field);
            if ($value !== null) {
                $arr[$field] = $value;
            }
        }
        return $arr;
    }

    /**
     * Setter
     * Set WpFeaturedVideo
     *
     * @param string $var embed url
     *
     * @return void
     */
    protected function setWpFeaturedVideo($var)
    {
        if (!empty($var)) {
            $this->WpFeaturedVideo = $var;
        }
    }

    /**
     * Setter
     * Set post rating
     *
     * @param mixed $var post rating
     *
     * @return void
     */
    protected function setRating($var)
    {
        if (!empty($var)) {
            $this->rating = $var;
        }
    }
}