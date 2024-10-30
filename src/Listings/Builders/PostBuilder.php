<?php
/**
 * Post builder
 */

namespace Clickio\Listings\Builders;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Integration\Services\WPSubtitle;
use Clickio\Listings\Containers\FilterItemContainer;
use Clickio\Listings\Containers\FilterListingContainer;
use Clickio\Listings\Interfaces\IPostBuilder;
use Clickio\Meta\PostMeta;
use Clickio\Utils\Container;
use Clickio\Utils\OEmbed;
use Clickio\Utils\SafeAccess;
use DateTimeImmutable;

/**
 * Build listing as post listing
 *
 * @package Listings\Builders
 */
class PostBuilder extends AbstractBuilder implements IPostBuilder
{

    /**
     * Builder alias
     *
     * @var string
     */
    const ALIAS = 'post';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
        $this->rest_url = sprintf("https://%s/wp-json/wp/v2/", $domain);
    }

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
        $query = new \WP_Query($params->toArray());
        $posts_array = $query->get_posts();
        $listing->id = $params->id;
        $listing->total_count = $query->found_posts;
        $listing->taxonomy_term = $params->taxonomy_term;
        $listing->taxonomy = $params->taxonomy;

        foreach ($posts_array as $idx => $post) {
            $item = new FilterItemContainer();
            $post_meta = new PostMeta($post);

            $item->id = $post->ID;

            $create_dt = get_post_datetime($post->ID, 'date');
            $item->date = $create_dt? $create_dt : new DateTimeImmutable($post->post_date);

            $item->guid = $post->guid;

            $modif_dt = get_post_datetime($post->ID, 'modified');
            $item->modified = $modif_dt? $modif_dt : new DateTimeImmutable($post->post_modified);

            $item->type = $post->post_type;
            $item->link = get_permalink($post->ID);
            $item->title = $post->post_title;
            $polylang = IntegrationServiceFactory::getService('polylang');
            $item->language = str_replace("_", "-", $polylang::getPostLocale($post->ID));
            $item->format = $this->getPostFormat($post->ID);
            $item->read_time = $post_meta->getReadingTime();
            $item->rating = $post_meta->getRating();
            $item->views = $post_meta->getViewsCounter();
            if ($featured_media = get_post_thumbnail_id($post->ID)) {
                $size = $params->image_size;
                $desired = $params->image_size_width;
                $min_width = $params->image_size_min_width;
                if (!$idx) {
                    // if first item
                    $size = $params->first_image_size;
                    $desired = $params->first_image_size_width;
                    $min_width = $params->first_image_size_min_width;
                }
                $images = $this->getFeatureImage($featured_media, $size, $desired, $min_width, $params->use_cropped);
                $caption_fld = $params->category_source;
                if (!empty($caption_fld)) {
                    $flds = get_post_meta($post->ID, $caption_fld);
                    if (!empty($flds)) {
                        $caption = array_shift($flds);
                        $images['Caption'] = $caption;
                    }
                }
                $item->WpFeaturedImage = $images;
            }

            $item->WpFeaturedVideo = $this->getWpFeaturedVideo($post->ID);

            if (WPSubtitle::integration()) {
                $item->subtitle = WPSubtitle::getTheSubtitle($post->ID);
            }

            $item->excerpt = get_the_excerpt($post->ID);

            if ($params->include_post_content) {
                $item->content = $this->getPostContent($post->ID);
            }

            $cat_list = $post_meta->getCategories();
            $item->WpCategoriesInfo = $cat_list;

            if (empty($cat_list)) {
                $cat_list = [];
            }
            $main_category = $this->findMainCategory($cat_list, $post->ID);

            if (!empty($main_category)) {
                $item->alt_title = $main_category->name;
            }

            $item->addApiLink('self', $this->rest_url.$post->post_type .'/'. $post->ID);
            $item->addApiLink('collection', $this->rest_url.$post->post_type .'/');
            $item->addApiLink('about', $this->rest_url.'/types/' . $post->post_type);
            $item->addAuthorLink(
                [
                    'href'       => $this->rest_url. 'users/' . $post->post_author ,
                    'embeddable' => true,
                ]
            );
            $item->WpAuthor=$this->getPostAuthor($post->post_author);
            $comments = $post_meta->getComments();
            if (!empty($comments)) {
                $item->Comments = $comments;
            }
            $listing->addItem($item);
        }

        return $listing;
    }

    /**
     * Build author struct
     *
     * @param int $id author id
     *
     * @return array
     */
    protected function getPostAuthor(int $id): array
    {
        $user = get_userdata($id);
        $author_avatar_urls=[];
        foreach ( [ 24, 48, 96 ] as $size ) {
            $author_avatar_urls[$size] = get_avatar_url($user->user_email, ['size' => $size]);
        }
        $data = [
            'id' => $user->ID,
            'name' => $user->display_name,
            'link' => get_author_posts_url($user->ID, $user->user_nicename),
            'slug' => $user->user_nicename,
            'avatar_urls' => $author_avatar_urls,
        ];
        return $data;
    }

    /**
     * Get post content
     *
     * @param int $id post id
     *
     * @return string
     */
    protected function getPostContent(int $id): string
    {
        $field_content = get_post_field('post_content', $id);
        $content = apply_filters('the_content', $field_content);
        if (empty($content)) {
            $content = '';
        }
        return $content;
    }

    /**
     * Find main category in post categories
     *
     * @param array $cat_list post categories
     * @param int $post_id post id
     *
     * @return WP_Term|null
     */
    protected function findMainCategory(array $cat_list, int $post_id)
    {

        $cat_permalink = IntegrationServiceFactory::getService("category_permalink");
        $main_category = $cat_permalink::getPostMainCategoryID($post_id);

        if (empty($main_category)) {
            return null;
        }
        foreach ($cat_list as $cat) {
            if ($cat->term_id == $main_category) {
                return $cat;
            }
        }

        return null;
    }

    /**
     * Get fetaured video struct
     *
     * @param int $post_id $post->ID
     *
     * @return array
     */
    protected function getWpFeaturedVideo(int $post_id): array
    {
        $oembed = OEmbed::getInstance();
        $embed_url = $oembed->getOembedUrl($post_id);
        $embed_html = $oembed->getEmbedHtml($post_id);
        if (empty($embed_url) || empty($embed_html)) {
            return [];
        }
        return ["html" => $embed_html, "url" => $embed_url];
    }

    /**
     * Get post format
     *
     * @param int $post_id post ID
     *
     * @return string
     */
    protected function getPostFormat(int $post_id): string
    {
        $format = 'post';

        $oembed = OEmbed::getInstance();
        $has_video = $oembed->hasEmbedVideo($post_id);
        if ($has_video) {
            return "video";
        }

        $post_format = get_post_format($post_id);
        if (!empty($post_format)) {
            $format = $post_format;
        }
        return $format;
    }
}
