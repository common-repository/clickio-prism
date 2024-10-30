<?php

/**
 * Get embed content
 */

namespace Clickio\Utils;

/**
 * Embed content
 *
 * @package Utils
 */
class OEmbed
{
    /**
     * Embed meta fields
     *
     * @var array
     */
    protected $embed_fields = [
        "video_url",
        "_video_url"
    ];

    /**
     * Embed Video regexp
     *
     * @var array
     * @codingStandardsIgnoreStart
     */
    protected $embed_video_regexp = [
        "(?:https?:\/\/)?(?:www\.)?youtu\.?be(?:\.com)?\/?.*(?:watch|embed)?(?:.*v=|v\/|\/)([\w\-_]+)\&?",
        "(http|https)?:\/\/(www\.|player\.)?vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|video\/|)(\d+)(?:|\/\?)",
        "(?:dailymotion\.com(?:\/video|\/hub)|dai\.ly)\/([0-9a-z]+)(?:[\-_0-9a-zA-Z]+#video=([a-z0-9]+))?"
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Embed video categories
     *
     * @var array
     */
    protected $video_categories = [
        "video"
    ];

    /**
     * Singletone instance
     *
     * @var ?OEmbed
     */
    private static $_inst = null;

    /**
     * Get vidoe html
     *
     * @param int $post_id post ID
     *
     * @return string
     */
    public function getEmbedHtml(int $post_id = 0): string
    {
        $oembed_url = $this->getOembedUrl($post_id);
        $embed_html = wp_oembed_get($oembed_url);
        return $embed_html;
    }

    /**
     * Get vidoe url
     *
     * @param int $post_id post ID
     *
     * @return string
     */
    public function getOembedUrl(int $post_id = 0): string
    {
        foreach ($this->embed_fields as $meta_key) {
            $meta = get_post_meta($post_id, $meta_key, true);
            if (!empty($meta)) {
                return $meta;
            }
        }
        return '';
    }

    /**
     * Factory method
     * Create singletone
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (empty(static::$_inst)) {
            static::$_inst = new static();
        }
        return static::$_inst;
    }

    /**
     * Static alias for getEmbedHtml
     *
     * @param int $post_id post ID
     *
     * @return string
     */
    public static function getEmbed(int $post_id = 0): string
    {
        $inst = static::getInstance();
        return $inst->getEmbedHtml($post_id);
    }

    /**
     * Test if post contain an embed video
     *
     * @param int $post_id post ID
     *
     * @return bool
     */
    public function hasEmbedVideo(int $post_id = 0): bool
    {
        $post = get_post($post_id);
        if (empty($post)) {
            return false;
        }

        $video_marks = [];
        $video_marks[] = $this->_hasVideoBlock($post->post_content);
        $video_marks[] = $this->_testVideoRegexp($post->post_content);
        $video_marks[] = $this->_hasVideoField($post->ID);
        $video_marks[] = $this->_hasVideoCategory($post->ID);

        $status = false;
        foreach ($video_marks as $video) {
            if ($video) {
                $status = $video;
                break;
            }
        }
        return $status;
    }

    /**
     * Search embed block with type video
     *
     * @param string $content post raw content
     *
     * @return bool
     */
    private function _hasVideoBlock(string $content): bool
    {
        $blocks = parse_blocks($content);
        if (empty($blocks)) {
            return false;
        }
        foreach ($blocks as $block) {
            $name = SafeAccess::fromArray($block, 'blockName', 'string', 'undefined');
            if ($name != 'core/embed') {
                continue ;
            }

            $attrs = SafeAccess::fromArray($block, 'attrs', 'array', []);
            $type = SafeAccess::fromArray($attrs, 'type', 'string', 'undefined');
            if ($type == 'video') {
                return true;
            }
        }
        return false;
    }

    /**
     * Search video providers by regexp
     *
     * @param string $content post content
     *
     * @return bool
     */
    private function _testVideoRegexp(string $content): bool
    {
        $regexp_list = apply_filters("_clickio_embed_video_regexp", $this->embed_video_regexp);
        foreach ($regexp_list as $regexp) {
            if (preg_match("/$regexp/", $content)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Search video in meta fields
     *
     * @param int $post_id post ID
     *
     * @return bool
     */
    private function _hasVideoField(int $post_id): bool
    {
        $embed_url = $this->getOembedUrl($post_id);
        return !empty($embed_url);
    }

    /**
     * Belonging of the article to the video category
     *
     * @param int $post_id post ID
     *
     * @return bool
     */
    private function _hasVideoCategory(int $post_id): bool
    {
        $categories = apply_filters("_clickio_embed_video_categories", $this->video_categories);
        return has_category($categories, $post_id);
    }
}
