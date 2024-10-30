<?php

/**
 * Comment container
 */

namespace Clickio\RestApi\Actions\Containers;

use Clickio\Utils\Captcha;
use Clickio\Utils\UserUtils;
use Clickio\Utils\ValidatedContainer;

/**
 * Comment container
 *
 * @package RestApi\Actions\Containers
 */
class CommentContainer extends ValidatedContainer
{
    /**
     * Solved captcha
     * Validator: required, captcha
     *
     * @var string
     */
    protected $captcha = "";

    /**
     * Captcha hash
     * Validator: required
     *
     * @param string
     */
    protected $sign = "";

    /**
     * Comment text
     * Validator: required
     *
     * @var string
     */
    protected $comment = "";

    /**
     * Post id
     * Validator: required
     *
     * @var int
     */
    protected $post_id = 0;

    /**
     * Commment id
     * If comment is answer to another comment
     *
     * @var int
     */
    protected $parent_comment = 0;

    /**
     * Author email
     *
     * @var string
     */
    protected $author_email = "";

    /**
     * Author name
     *
     * @var string
     */
    protected $author = "";

    /**
     * Comment author id
     *
     * @var int
     */
    protected $user_id = 0;

    /**
     * Validate $captcha field
     *
     * @param mixed $val field value
     *
     * @return bool
     */
    public function validateCaptcha($val): bool
    {
        if (empty($val)) {
            $this->pushError('captcha', 'field_required');
            return false;
        }

        $res = Captcha::verify($this->sign, $val);
        if (!$res) {
            $this->pushError('captcha', 'invalid_captcha');
            return false;
        }
        return true;
    }

    /**
     * Validate $sign field
     *
     * @param mixed $val field value
     *
     * @return bool
     */
    public function validateSign($val): bool
    {
        if (empty($val)) {
            $this->pushError('sign', 'field_required');
            return false;
        }
        return true;
    }

    /**
     * Validate $comment field
     *
     * @param mixed $val field value
     *
     * @return bool
     */
    public function validateComment($val): bool
    {
        if (empty($val)) {
            $this->pushError('comment', 'field_required');
            return false;
        }
        return true;
    }

    /**
     * Validate $post_id field
     *
     * @param mixed $val field value
     *
     * @return bool
     */
    public function validatePostId($val): bool
    {
        if (empty($val)) {
            $this->pushError('post_id', 'field_required');
            return false;
        }
        return true;
    }

    /**
     * Setter
     * Set user id
     * Property: user_id
     *
     * @param mixed $raw raw user id
     *
     * @return void
     */
    public function validateUserId($raw): bool
    {
        if (empty($raw)) {
            return true; // allow anonymus
        }

        if (!is_numeric($raw)) {
            $this->pushError("user_id", "not_numeric");
            return false;
        }

        $decoded = UserUtils::decryptUserId($raw);
        $user = get_user_by("ID", $decoded);
        if (empty($user)) {
            $this->pushError("user_id", "user_not_found");
            return false;
        }
        return true;
    }
}