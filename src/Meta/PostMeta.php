<?php
/**
 * Post meta info
 */

namespace Clickio\Meta;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Integration\Services\GenesisFramework;
use Clickio\Logger\LoggerAccess;
use Clickio\Options;

/**
 * Post meta class
 *
 * @package Meta
 */
final class PostMeta
{
    use LoggerAccess;

    /**
     * Post
     *
     * @var WP_Post
     */
    protected $post = null;

    /**
     * Taxonomy black list
     *
     * @var array
     */
    protected $exclude_taxnomies = [
        'post_tag',
        'post_format',
        "wp_template",
        "wp_template_part",
        "wp_global_styles",
        "wp_navigation",
    ];

    /**
     * Post rating meta keys
     *
     * @var array
     */
    protected $rating_meta_keys = [
        '_rev_review_rating'
    ];

    /**
     * Constructor
     *
     * @param WP_Post $post post
     */
    public function __construct(\WP_Post $post)
    {
        $this->post = $post;
    }

    /**
     * Create from post id
     *
     * @param int $postid post id
     *
     * @return self
     */
    public static function createFromId(int $postid): self
    {
        if (empty($postid)) {
            static::logError("Empty post id was received");
            throw new \Exception("Empty post not allowed");
        }

        $post = get_post($postid);
        if (!empty($post)) {
            return new static($post);
        }

        throw new \Exception("Post doesn't exists");
    }

    /**
     * Get post categories
     *
     * @return array
     */
    public function getCategories(): array
    {
        $taxes = $this->_getTaxonomies();
        $categories =  wp_get_post_terms($this->post->ID, $taxes);
        $primary_term = $this->getPrimaryCategoryId();

        foreach ($categories as $cat) {
            $term = TermMeta::createFromId($cat->term_id);
            $cat->link = $term->getPermalink();
            $cat->description = $term->getDescription();
            $cat->primary = $cat->term_id == $primary_term;
            $meta = $term->getTermMeta();
            $cat->meta = empty($meta)? (object)[] : $meta;
        }
        return $categories;
    }

    /**
     * Get post taxonomies
     *
     * @return array
     */
    private function _getTaxonomies(): array
    {
        $taxes = get_post_taxonomies($this->post->ID);
        $taxes = array_reduce(
            $taxes,
            function ($new_tax_list, $next) {
                $taxonomy = get_taxonomy($next);
                if (!in_array($next, $this->exclude_taxnomies) && ($taxonomy->public || $taxonomy->show_ui)) {
                    $new_tax_list[] = $next;
                }
                return $new_tax_list;
            },
            []
        );
        return $taxes;
    }

    /**
     * Post url
     *
     * @return string
     */
    public function getPermalink(): string
    {
        return get_permalink($this->post->ID);
    }

    /**
     * Get post comments
     *
     * @return array
     */
    public function getComments(): array
    {
        $comments_data = [];
        $comments = get_comments(
            [
                'post_id' => $this->post->ID,
                'orderby' => 'comment_date_gmt',
                'order'   => 'DESC',
                'status'  => 'approve',
            ]
        );
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $author_avatar_urls=[];
                foreach ( [ 24, 48, 96 ] as $size ) {
                    $author_avatar_urls[$size] = get_avatar_url($comment->comment_author_email, ['size' => $size]);
                }
                $wpdiscuz = IntegrationServiceFactory::getService('wpdiscuz');
                $comment_content = $wpdiscuz::wrapComments($comment->comment_content);
                $comment_content = apply_filters('comment_text', $comment_content, $comment, []);
                $comments_data[] = [
                    'id' => (int)$comment->comment_ID,
                    'author_name' => $comment->comment_author,
                    'author_email' => $comment->comment_author_email,
                    'author_url' => $comment->comment_author_url,
                    'date' => $comment->comment_date,
                    'content' => [ "rendered" => $comment_content ],
                    'link' =>  get_comment_link($comment),
                    'author_avatar_urls' => $author_avatar_urls,
                    'parent' => (int) $comment->comment_parent,
                    'status' => $comment->comment_approved,
                    'karma' => $comment->comment_karma,
                    'vote' => $wpdiscuz::getCommentVotes((int)$comment->comment_ID),
                    'rank' => $wpdiscuz::getUserRank((int)$comment->user_id),
                    'label' => $wpdiscuz::getUserLabel((int)$comment->user_id, $this->post->ID)
                ];
            }
        }
        // die();
        return $comments_data;
    }

    /**
     * Getter
     * Get current post
     *
     * @return WP_Post
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Get post content
     *
     * @return string
     */
    protected function getPostContent(): string
    {
        $field_content = get_post_field('post_content', $this->post->ID);
        $content = apply_filters('the_content', $field_content);
        if (empty($content)) {
            $content = '';
        }
        return $content;
    }

    /**
     * Get post reading time
     *
     * @return int|null
     */
    public function getReadingTime()
    {
        $plugin = IntegrationServiceFactory::getService('reading_time');
        $reading_time = 0;

        if ($plugin::integration()) {
            $reading_time = $plugin::getReadingTime($this->post->ID);
        } else {
            $reading_time = $this->_calcReadingTime();
        }
        wp_reset_query();
        return $reading_time;
    }

    /**
     * Calc post reading time
     *
     * @return int|null
     */
    private function _calcReadingTime()
    {
        $content = $this->getPostContent();
        $content = wp_strip_all_tags($content);
        $word_count = count(preg_split('/\s+/', $content));
        $wpm = Options::get("words_per_minute");

        if ($word_count <= 0 || $wpm <= 0) {
            return null;
        }

        $time = floor(($word_count / $wpm));
        return $time;
    }

    /**
     * Get id of the primary category
     *
     * @return int
     */
    public function getPrimaryCategoryId(): int
    {
        $wpseo = IntegrationServiceFactory::getService("wpseo");
        $primary_term = $wpseo::getPrimaryTerm($this->post->ID);

        if (empty($primary_term)) {
            $td_composer = IntegrationServiceFactory::getService('td_composer');
            $primary_term = $td_composer::getPrimaryTerm($this->post->ID);
        }

        if (empty($primary_term)) {
            $cat_permalink = IntegrationServiceFactory::getService('category_permalink');
            $primary_term = $cat_permalink::getPostMainCategoryID($this->post->ID);
        }
        return $primary_term;
    }

    /**
     * Get post rating
     *
     * @return string
     */
    public function getRating(): string
    {
        $rating = "0";
        foreach ($this->rating_meta_keys as $key) {
            $post_meta = get_post_meta($this->post->ID, $key, true);
            if (!empty($post_meta)) {
                $rating = $post_meta;
            }
        }
        return $rating;
    }

    /**
     * Get total number of views
     *
     * @return int
     */
    public function getViewsCounter(): int
    {
        $meta = [
            "views",
            "post_views_count",
            "total_number_of_views"
        ];
        $post_meta = get_post_meta($this->post->ID);
        foreach ($meta as $key) {
            $views_count = -1;
            if (array_key_exists($key, $post_meta)) {
                $views_count = array_pop($post_meta[$key]);
                break ;
            }
        }
        return $views_count;
    }
}
