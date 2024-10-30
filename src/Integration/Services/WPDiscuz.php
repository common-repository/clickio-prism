<?php

/**
 * WP external links
 */

namespace Clickio\Integration\Services;

use Clickio\ClickioPlugin;
use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Meta\PostMeta;
use Clickio\Utils\LocationType;
use Clickio\Utils\SafeAccess;

/**
 * Integration with WP External Links
 *
 * @package Integration\Services
 */
final class WPDiscuz extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wpdiscuz/class.WpdiscuzCore.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wpdiscuz';

    /**
     * Disable plugin for a single request
     *
     * @param string $comment_content comment text
     *
     * @return void
     */
    public static function wrapComments(string $comment_content): string
    {
        if (!static::integration()) {
            return $comment_content;
        }

        try {
            global $wpdiscuz;
            if (empty($wpdiscuz)) {
                return $comment_content;
            }

            if (!property_exists($wpdiscuz, 'helper')) {
                return $comment_content;
            }

            if (!method_exists($wpdiscuz->helper, "makeClickable")) {
                return $comment_content;
            }

            return $wpdiscuz->helper->makeClickable($comment_content);
        } catch (\Exception $e) {
            return $comment_content;
        }
    }

    /**
     * Get the number of votes in the comment
     *
     * @param int $comment_id comment id
     *
     * @return int
     */
    public static function getCommentVotes(int $comment_id): int
    {
        if (!static::integration()) {
            return 0;
        }

        $votes = get_comment_meta($comment_id, 'wpdiscuz_votes', true);
        if (empty($votes)) {
            $votes = 0;
        }

        return $votes;
    }

    /**
     * Get user rank
     *
     * @param int $uid user id
     *
     * @return string
     */
    public static function getUserRank(int $uid): string
    {
        if (!static::integration() || empty($uid)) {
            return '';
        }

        $rating = intval(get_user_meta($uid, "wv_rating", true));
        $wv_option = get_option("wv_settings");
        $level = SafeAccess::fromArray($wv_option, 'wv_level', 'array', []);
        if (@isset($level[1]['vote']['value']) && $rating < $level[1]['vote']['value']) {
            return '';
        }

        for ($i = count($level); $i > 0; $i--) {
            if (@isset($level[$i]["vote"]["value"]) && $rating >= intval($level[$i]["vote"]["value"])) {
                return $level[$i]["label"]["value"];
            }
        }
        return "";
    }

    /**
     * Get user label
     *
     * @param int $uid comment author id
     * @param int $post_id post author id
     *
     * @return string
     */
    public static function getUserLabel(int $uid, int $post_id): string
    {

        if (!static::integration()
            || empty($uid)
            || empty($post_id)
            || !class_exists("\WpdiscuzCore", false)
        ) {
            return '';
        }

        $dis_core = \WpdiscuzCore::getInstance();
        if (!property_exists(\WpdiscuzCore::class, 'options') || empty($dis_core->options)) {
            return '';
        }

        $opt = $dis_core->options;
        $post = get_post($post_id);
        $user = get_user_by("ID", $uid);
        if (empty($user)) {
            return '';
        }

        if ($uid == $post->post_author) {
            $author_label = esc_html__("Author", "wpdiscuz");
            if (!empty($opt->labels["blogRoleLabels"]["post_author"])) {
                $author_label = esc_html($opt->phrases["wc_blog_role_post_author"]);
            }
            return $author_label;
        } else {
            if ($opt->labels["blogRoles"]) {
                if ($user->roles && is_array($user->roles)) {
                    foreach ($user->roles as $k => $role) {
                        if (isset($opt->labels["blogRoles"][$role])) {
                            $rolePhrase = isset($opt->phrases["wc_blog_role_" . $role]) ? esc_html($opt->phrases["wc_blog_role_" . $role]) : "";
                            if (!empty($opt->labels["blogRoleLabels"][$role])) {
                                return apply_filters("wpdiscuz_user_label", $rolePhrase, $user);
                            }
                            break;
                        }
                    }
                } else {
                    if (!empty($opt->labels["blogRoleLabels"]["guest"])) {
                        return esc_html($opt->phrases["wc_blog_role_guest"]);
                    }
                }
            }
        }

        return '';
    }

    /**
     * Setup cache cleaners
     *
     * @return void
     */
    public static function setupCacheCleaners()
    {
        if (!static::integration()) {
            return '';
        }

        add_filter('wpdiscuz_clean_post_cache', [static::class, "addPurgeUrl"]);
    }

    /**
     * Push post url into purge queue
     *
     * @param int $post_id post id
     *
     * @return void
     */
    public static function addPurgeUrl($post_id)
    {
        if (empty($post_id) || !is_numeric($post_id)) {
            return ;
        }
        $post = PostMeta::createFromId($post_id);
        $cache = (ClickioPlugin::getInstance())->getCache();
        $cache->addPurgeUrl($post->getPermalink());
    }

    /**
     * Get the id of the comment that the user has voted on
     *
     * @param int $post_id article id
     * @param int $user_id user id
     *
     * @return array
     */
    public static function getPostUserVotes(int $post_id, int $user_id): array
    {
        if (!static::integration()) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix."wc_users_voted";
        $sql = "SELECT comment_id FROM $table WHERE user_id = %s AND post_id = %s AND vote_type = 1";
        $sanitaized = $wpdb->prepare($sql, [$user_id,$post_id]);
        $res = $wpdb->get_col($sanitaized);
        if (empty($res)) {
            $res = [];
        }
        return $res;
    }

    /**
     * Get comments form
     *
     * @return void
     */
    public static function  addCommentsTemplateToExtra()
    {
        if (!static::integration() || !LocationType::isPost()) {
            return [];
        }

        add_action(
            "_clickio_after_plugins_extra_generated",
            function ($extra) {
                $discuz = \wpDiscuz();
                $discuz->initCurrentPostType();

                ob_start();
                comments_template();
                $tpl = ob_get_clean();
                @ob_end_clean();
                $extra->pushContent('content', "wpdiscuz/class_WpdiscuzCore_php", "comments_template", $tpl, "wp_footer");
            }
        );
    }

    /**
     * Get form comments status
     *
     * @return ?int
     */
    public static function getFormCommentStatus()
    {
        if (!static::integration() || !LocationType::isPost() || !function_exists('wpDiscuz')) {
            return null;
        }

        try {
            $discuz = \wpDiscuz();
            if (!is_object($discuz)
                || !method_exists($discuz, "initCurrentPostType")
                || !property_exists($discuz, 'wpdiscuzForm')
            ) {
                return null;
            }
            $discuz->initCurrentPostType();
            $form_obj = $discuz->wpdiscuzForm;
            if (empty(get_the_ID())
                || !is_object($form_obj)
                || !method_exists($form_obj, 'getForm')
            ) {
                return null;
            }

            $form = $form_obj->getForm(get_the_ID());
            if (!is_object($form)
                || !method_exists($form, "getFormId")
                || empty($form->getFormId())
            ) {
                return null;
            }
            $opt = get_post_meta($form->getFormId(), "wpdiscuz_form_general_options", true);

            return isset($opt['guest_can_comment'])? $opt['guest_can_comment'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
}
