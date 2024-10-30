<?php
/**
 * Post listings params
 */

namespace Clickio\Listings;

use Clickio\Utils\Container;

/**
 * Post listings container
 *
 * @package Listings
 */
class FilterParamsContainer extends Container
{
    /**
     * Params id
     *
     * @var ?int
     */
    protected $id = null;

    /**
     * Count posts per page
     * Request param: posts_per_page / per_page
     *
     * @var int
     */
    protected $posts_per_page = 5;

    /**
     * Ordering direcrion
     * Request param: order
     *
     * @var string
     */
    protected $order = 'DESC';

    /**
     * Size for all images
     * Request param: image_size
     *
     * @var string
     */
    protected $image_size = 'thumbnail';

    /**
     * Size for image in first post
     * Request param: first_image_size
     *
     * @var string
     */
    protected $first_image_size = '';

    /**
     * Offset
     * Request param: offset / page
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * Ordering field e.g. SELECT ... FROM ... ORDER BY $orderby
     * Request param: orderby
     *
     * @var string
     */
    protected $orderby = 'date';

    /**
     * Record type
     * Request param: post_type / endpoint
     *
     * @var string
     */
    protected $post_type = 'post';

    /**
     * Author id
     * Request param: author
     *
     * @param int
     */
    protected $author = 0;

    /**
     * Post status
     * Request param: post_status
     *
     * @var string
     */
    protected $post_status = 'publish';

    /**
     * Category id list
     * Request param: category__in / categories
     *
     * @var array
     */
    protected $category__in = [];

    /**
     * Exclude categories
     * Request param: category__not_in / categories_exclude
     *
     * @var array
     */
    protected $category__not_in = [];

    /**
     * Tags id list
     * Request param: tags__in / tags
     *
     * @var array
     */
    protected $tag__in = [];

    /**
     * Exclude tags
     * Request param: tags__not_in / tags_exclude
     *
     * @var array
     */
    protected $tag__not_in = [];

    /**
     * Meta query
     * Request param: meta_query
     *
     * @var array
     */
    protected $meta_query = [];

    /**
     * Tax query
     * Request param: tax_query
     *
     * @var array
     */
    protected $tax_query = [];

    /**
     * Date query
     * Request param: date_query
     *
     * @var array
     */
    protected $date_query = [];

    /**
     * Include post content
     * Request param: include_post_content
     *
     * @var bool
     */
    protected $include_post_content = false;

    /**
     * Exclude posts by post id
     * Request param: post__not_in , post_exclude

     * @var array
     */
    protected $post__not_in = [];

    /**
     * Filter posts by post name
     * Request param: name / slideshow
     *
     * @var string
     */
    protected $name = '';

    /**
     * Taxonomy term name
     * Internal usage only
     * Request param:
     *
     * @var string
     */
    protected $taxonomy_term = '';

    /**
     * Taxonomy name
     * Internal usage only
     * Request param:
     *
     * @var string
     */
    protected $taxonomy = '';

    /**
     * Filter posts by parent post id
     * Request param: post_parent
     *
     * @var int
     */
    protected $post_parent;

    /**
     * Requested image size
     * Size for image must grater or equal
     * Request param: image_size_width
     *
     * @var int
     */
    protected $image_size_width = 0;

    /**
     * Bottom border for image size
     * Size for image must grater or equal
     * if no images with size image_size_width, then using this value
     * Request param: image_size_min_width
     *
     * @var int
     */
    protected $image_size_min_width = 0;

    /**
     * Same as image_size_width, but for first image
     * Request param: first_image_size_width
     *
     * @var int
     */
    protected $first_image_size_width = 0;

    /**
     * Same as image_size_min_width, but for first image
     * Request param: first_image_size_min_width
     *
     * @var int
     */
    protected $first_image_size_min_width = 0;

    /* Post featured image caption
     * Request param: category_source
     *
     * @var int
     */
    protected $category_source;

    /**
     * Use cropped images
     * Request param: use_cropped
     *
     * @var bool
     */
    protected $use_cropped = false;

    /**
     * Search by post id
     * Request param: post__in / posts
     *
     * @var array
     */
    protected $post__in;

    /**
     * Ignore sticky posts
     * Request param: ignore_sticky_posts / no_sticky
     *
     * @var bool
     */
    protected $ignore_sticky_posts = false;

    /**
     * Autogenrated excerpt
     * Request param: smart_excerpt
     *
     * @var bool
     */
    protected $smart_excerpt = false;

    /**
     * Disable duplicates filter
     * Request param: ignore_duplicates | autonomous
     *
     * @var bool
     */
    protected $ignore_duplicates = false;

    /**
     * Setter
     * Alias for posts_per_page
     * Request param: per_page
     *
     * @param int $value count posts per page
     *
     * @return void
     */
    protected function setPerPage(int $value)
    {
        $this->posts_per_page = $value;
    }

    /**
     * Getter
     * Behavior rules
     *
     * @return string
     */
    protected function getFirstImageSize(): string
    {
        $f_img_size = $this->first_image_size;
        $img_size = $this->image_size;

        if (empty($img_size)) {
            $img_size = '';
        }

        if (empty($f_img_size)) {
            return (string)$img_size;
        }

        return (string)$f_img_size;
    }

    /**
     * Setter
     * Alias for category__in
     * Request param: categories
     *
     * @param array $value category id list
     *
     * @return void
     */
    protected function setCategories(array $value)
    {
        $this->category__in = $value;
    }

    /**
     * Setter
     * Include posts from child categories
     * Request param: child_categories
     *
     * @param bool $value include or not
     *
     * @return void
     */
    protected function setChildCategories(bool $value)
    {
        if (empty($value)) {
            return ;
        }

        $categories = [];
        foreach ($this->category__in as $cat) {
            $childs = get_categories(['child_of' => $cat]);
            if (!is_array($childs)) {
                continue ;
            }

            foreach ($childs as $child_categ) {
                if ($child_categ instanceof \WP_Term) {
                    $categories[] = $child_categ->term_id;
                }
            }
        }

        $this->category__in = array_merge($this->category__in, $categories);
    }

    /**
     * Setter
     * Alias for category_not_in
     * Request param: categories_exclude
     *
     * @param array $value categories id list
     *
     * @return void
     */
    protected function setCategoriesExclude(array $value)
    {
        $this->category__not_in = $value;
    }

    /**
     * Setter
     * Alias for tags__in
     * Request param: tags
     *
     * @param array $value tags id list
     *
     * @return void
     */
    protected function setTags(array $value)
    {
        $this->tag__in = $value;
    }

    /**
     * Setter
     * Alias for tags__not_in
     * Request param: tags_exclude
     *
     * @param array $value tags id list
     *
     * @return void
     */
    protected function setTagsExclude(array $value)
    {
        $this->tag__not_in = $value;
    }

    /**
     * Setter
     * Multiplier alias for offset
     * Request param: page
     *
     * @param int $value page number, starting from 1
     *
     * @return void
     */
    protected function setPage(int $value)
    {
        if ($value < 1) {
            $value = 1;
        }

        $this->offset += $this->posts_per_page * ($value - 1);
    }

    /**
     * Setter
     * Add offset to existed
     *
     * @param mixed $value offset
     *
     * @return void
     */
    protected function setOffset($value)
    {
        if (!is_numeric($value)) {
            return ;
        }

        $this->offset += $value;
    }

    /**
     * Setter
     * Alias for post_type
     * Request param: endpoint
     *
     * @param string $value post type
     *
     * @return void
     */
    protected function setEndpoint(string $value)
    {
        if (in_array($value, ['posts', 'category'])) {
            $this->post_type = 'post';
        } else {
            $this->post_type = $value;
        }
    }

    /**
     * Setter
     * Alias for post_status
     * Request param: status
     *
     * @param string $value post status
     *
     * @return void
     */
    protected function setStatus(string $value)
    {
        $this->post_status = $value;
    }

    /**
     * Getter
     * Type safe include_post_content
     *
     * @return bool
     */
    protected function getIncludePostContent(): bool
    {
        return (bool)$this->include_post_content;
    }

    /**
     * Setter
     * alias for post__not_in
     * Request param: post_exclude
     *
     * @param array $value posts id list
     *
     * @return void
     */
    protected function setPostExclude(array $value)
    {
        $this->post__not_in = $value;
    }

    /**
     * Setter
     * Alias for name
     * Request param: slideshow
     *
     * @param string $value slideshow id
     *
     * @return void
     */
    protected function setSlideshow(string $value)
    {
        $this->name = $value;
    }

    /**
     * Getter
     * Get first image size width
     *
     * @return int
     */
    protected function getFirstImageSizeWidth(): int
    {
        $img_width = $this->image_size_width;
        $f_img_width = $this->first_image_size_width;

        if (!is_numeric($img_width) || !is_numeric($f_img_width)) {
            return 0;
        }

        if (empty($f_img_width)) {
            return (int)$img_width;
        }
        return (int)$f_img_width;
    }

    /**
     * Getter
     * Get first image size width
     *
     * @return int
     */
    protected function getFirstImageSizeMinWidth(): int
    {
        $img_width = $this->image_size_min_width;
        $f_img_width = $this->first_image_size_min_width;

        if (!is_numeric($img_width) || !is_numeric($f_img_width)) {
            return 0;
        }

        if (empty($f_img_width)) {
            return (int)$img_width;
        }
        return (int)$f_img_width;
    }

    /**
     * Getter
     * Get boolean for use_cropped param
     *
     * @return bool
     */
    protected function getUseCropped(): bool
    {
        return !empty($this->use_cropped);
    }

    /**
     * Setter
     * Alias for post__in param
     * Request param: posts
     *
     * @param array $value posts ids
     *
     * @return void
     */
    protected function setPosts($value)
    {
        $this->post__in = $value;
    }

    /**
     * Setter
     * Alias for ignore_sticky_posts
     * Request param: no_sticky
     *
     * @param bool $value ignore sticky
     *
     * @return void
     */
    protected function setNoSticky($value)
    {
        $this->ignore_sticky_posts = !empty($value);
    }

    /**
     * Setter
     * Set smart_excerpt as boolean value
     * Request param: smart_excerpt
     *
     * @param mixed $value value for smart_excerpt
     *
     * @return void
     */
    protected function setSmartExcerpt($value)
    {
        $this->smart_excerpt = !empty($value);
    }

    /**
     * Setter
     * Set ignore_duplicates as boolean
     *
     * @param mixed $value value for ignore_duplicates
     *
     * @return void
     */
    protected function setIgnoreDuplicates($value)
    {
        $this->ignore_duplicates = !empty($value);
    }

    /**
     * Setter
     * Alias for ignore_duplicates
     *
     * @param mixed $value value for ignore_duplicates
     *
     * @return void
     */
    protected function setAutonomous($value)
    {
        $this->setIgnoreDuplicates($value);
    }
}