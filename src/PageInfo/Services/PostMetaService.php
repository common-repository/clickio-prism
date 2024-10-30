<?php

/**
 * Apply rules to extra fields
 */

namespace Clickio\PageInfo\Services;

use Clickio\Utils\Container;

/**
 * Extra fields rules
 *
 * @package ExtraContent\Rules
 */
class PostMetaService extends Container
{

    /**
     * Post meta field name
     * Used when $source == "post_meta"
     *
     * @var string
     */
    protected $meta_key = "";

    /**
     * What to do when requested value is empty
     *
     * @var string
     */
    protected $on_empty = "";

    /**
     * Post id
     *
     * @var integer
     */
    protected $post_id = 0;

    /**
     * Apply service rule
     * Get meta_key from post meta
     *
     * @return mixed
     */
    public function apply()
    {
        $value = get_post_meta($this->post_id, $this->meta_key, true);
        if (empty($value)) {
            $value = '';
        }

        if (empty($value) && !empty($this->on_empty)) {
            $value = $this->triggerOnEmpty();
        }
        return $value;
    }

    /**
     * Validate rule
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $result = true;
        if (empty($this->meta_key) || empty($this->post_id)) {
            $result = false;
        }
        return $result;
    }

    /**
     * Fire on_empty event
     *
     * @return mixed
     */
    protected function triggerOnEmpty()
    {
        $value = '';
        switch ($this->on_empty) {
            case "random_post":
                $value = $this->onEmptyRandomPost();
                break;
        }
        return $value;
    }

    /**
     * Get Random posts
     *
     * @return array
     */
    private function _getRandomPost(): array
    {
        $query = [
            'post_type' => '',
            'orderby' => 'rand',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => $this->meta_key,
                    'value'   => [''],
                    'compare' => 'NOT IN'
                ]
            ]
        ];
        $posts = get_posts($query);

        if (empty($posts)) {
            $posts = [];
        }

        return $posts;
    }

    /**
     * Handler random_post for on_empty event
     *
     * @return mixed
     */
    protected function onEmptyRandomPost()
    {
        $value = '';
        $posts = $this->_getRandomPost();
        if (!empty($posts)) {
            $post = array_shift($posts);
            $value = get_post_meta($post->ID, $this->meta_key, true);
        }
        return $value;
    }
}
