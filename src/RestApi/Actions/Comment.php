<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Db\ModelFactory;
use Clickio\Db\Models\Captcha;
use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Integration\Services\SecureImageWp;
use Clickio\Integration\Services\SGR;
use Clickio\RestApi as rest;
use Clickio\RestApi\Actions\Containers\CommentContainer;
use Clickio\RestApi\Actions\Containers\DeleteCommentContainer;
use Clickio\RestApi\Actions\Containers\EditCommentContainer;
use Clickio\Utils\SafeAccess;
use Clickio\Utils\UserUtils;
use WP_REST_Response;

/**
 * Comments endpoint
 *
 * Example:
 *      GET http://domain.name/wp-json/clickio/captcha/
 *
 * @package RestApi\Actions
 */
class Comment extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    private $_allowed_tags = [
        "span" => [
            "id" => true,
            "class" => true,
            "title" => true,
            "contenteditable" => true,
            "data-id" => true,
            "data-name" => true,
            "data-title" => true,
            "data-content" => true,
            "style" => true
        ],
        "div" => [
            "id" => true,
            "class" => true,
            "title" => true,
            "contenteditable" => true,
            "data-id" => true,
            "data-name" => true,
            "data-title" => true,
            "data-content" => true,
            "style" => true
        ],
        "p" => [
            "id" => true,
            "class" => true,
            "title" => true,
            "contenteditable" => true,
            "data-name" => true,
            "data-title" => true,
            "data-content" => true,
            "data-id" => true,
            "style" => true
        ],
        "a" => [
            "class" => true,
            "href" => true,
            "title" => true,
            "target" => true,
            "rel" => true,
            "download" => true,
            "hreflang" => true,
            "media" => true,
            "type" => true
        ],
        "img" => [
            "class" => true,
            "src" => true,
            "alt" => true,
            "title" => true,
            "width" => true,
            "height" => true,
            "sizes" => true,
            "srcset" => true
        ],
        "ul" => [
            "class" => true
        ],
        "ol" => [
            "class" => true
        ],
        "li" => [
            "class" => true
        ],
        "blockquote" => [
            "class" => true,
            "cite" => true
        ],
        "pre" => [
            "class" => true,
            "spellcheck" => true
        ],
        "code" => [
            "class" => true
        ]
    ];

    /**
     * Handle http POST method
     * Create new comment.
     *
     * @return mixed
     */
    public function post()
    {

        SGR::disable();
        SecureImageWp::disable();
        $invisible_recaptcha = IntegrationServiceFactory::getService('invre');
        $invisible_recaptcha::disable();

        $content_type_arr = $this->request->get_content_type();
        $content_type = SafeAccess::fromArray($content_type_arr, 'value', 'string', 'application/json');

        if ($content_type == 'multipart/form-data') {
            $body = $this->request->get_body_params();
        } else {
            $body = json_decode($this->request->get_body(), true);
        }

        $resp = ['code' => 0, 'msg' => ''];
        if (empty($body)) {
            $resp['code'] = 400;
            $resp['msg'] = 'invalid_request';
            return $resp;
        }

        $this->extendAllowedTags();
        $container = CommentContainer::create($body);
        if (!$container->isValid()) {
            $resp['code'] = 400;
            $resp['msg'] = $container->getErrors();
            return $resp;
        }

        $uid = 0;
        if ($container->user_id) {
            $uid = UserUtils::decryptUserId($container->user_id);
        }

        $comment_data = [
            "comment_post_ID" => $container->post_id,
            "comment_content" => $container->comment,
            "comment_author_email" => $container->author_email,
            "comment_parent" => $container->parent_comment,
            "comment_author" => $container->author,
            "user_id" => $uid
        ];

        $status = wp_new_comment($comment_data, true);

        $moderation = get_option('comment_moderation');
        if (empty($moderation) && !empty($status) && !is_wp_error($status)) {
            wp_set_comment_status($status, 'approve');
        }

        if (is_wp_error($status)) {
            $resp['code'] = 400;
            $resp['msg'] = $status->get_error_message();
            return $resp;
        }

        $model = ModelFactory::create(Captcha::class);
        $model->deleteRow('captcha_hash', $container->sign);
        $resp['code'] = 200;
        $resp['msg'] = $status;

        return $resp;
    }

    /**
     * Handle DELETE request
     * Remove comment
     *
     * @return mixed
     */
    public function delete()
    {
        $body = $this->request->get_body();
        $parsed = json_decode($body, true);
        if (empty($body) || empty($parsed)) {
            return new WP_REST_Response(["code" => 400, "msg" => 'invalid_request'], 400);
        }

        $cont = DeleteCommentContainer::create($parsed);
        if (!$cont->isValid()) {
            $resp['code'] = 400;
            $resp['msg'] = $cont->getErrors();
            return $resp;
        }

        $comment = get_comment($cont->id);
        $comment_status = wp_get_comment_status($cont->id);
        $uid = UserUtils::decryptUserId($cont->user_id);

        if ($uid != $comment->user_id || $comment_status != 'approved') {
            return new WP_REST_Response(["code" => 400, "msg" => 'not_allowed'], 400);
        }

        wp_delete_comment($cont->id);
    }

    /**
     * Handle http PATCH request
     * Edit comment
     *
     * @return mixed
     */
    public function patch()
    {
        $body = $this->request->get_body();
        $parsed = json_decode($body, true);
        if (empty($body) || empty($parsed)) {
            return new WP_REST_Response(["code" => 400, "msg" => 'invalid_request'], 400);
        }

        $this->extendAllowedTags();
        $cont = EditCommentContainer::create($parsed);
        if (!$cont->isValid()) {
            $resp['code'] = 400;
            $resp['msg'] = $cont->getErrors();
            return $resp;
        }

        $comment = get_comment($cont->id);
        $comment_status = wp_get_comment_status($cont->id);
        $uid = UserUtils::decryptUserId($cont->user_id);

        if ($uid != $comment->user_id || !in_array($comment_status, ['approved', 'unapproved'])) {
            return new WP_REST_Response(["code" => 400, "msg" => 'not_allowed'], 400);
        }

        $upd_comment = [
            'comment_ID' => $cont->id,
            'comment_content' => $cont->comment
        ];

        wp_update_comment($upd_comment);
    }

    /**
     * Extend allowed tags
     *
     * @return void
     */
    protected function extendAllowedTags()
    {
        global $allowedtags;
        $allowedtags = array_merge($allowedtags, $this->_allowed_tags);
    }
}