<?php

/**
 * Edit commment container
 */

namespace Clickio\RestApi\Actions\Containers;

use Clickio\Utils\UserUtils;
use Clickio\Utils\ValidatedContainer;

/**
 * Edit comment form container
 *
 * @package RestApi\Actions\Containers
 */
class EditCommentContainer extends ValidatedContainer
{

    /**
     * User id
     *
     * @var int
     */
    protected $user_id = 0;

    /**
     * Comment content
     *
     * @var string
     */
    protected $comment = "";

    /**
     * Comment id
     *
     * @var int
     */
    protected $id = 0;

    /**
     * Validate user id
     *
     * @param mixed $val user id
     *
     * @return bool
     */
    public function validateUserId($val): bool
    {
        if (empty($val) || !is_numeric($val) || $val < 0) {
            $this->pushError("user_id", "field_required");
            return false;
        }

        $decoded = UserUtils::decryptUserId($val);
        $user = get_user_by("ID", $decoded);
        if (empty($user)) {
            $this->pushError("user_id", "not_found");
            return false;
        }
        return true;
    }

    /**
     * Validate comment text
     *
     * @param mixed $val comment text
     *
     * @return bool
     */
    public function validateComment($val): bool
    {
        if (empty($val)) {
            $this->pushError("comment", "field_required");
            return false;
        }
        return true;
    }

    /**
     * Validate comment id
     *
     * @param mixed $val commment id
     *
     * @return bool
     */
    public function validateId($val): bool
    {
        if (empty($val) || !is_numeric($val) || $val < 0) {
            $this->pushError("id", "field_required");
            return false;
        }

        $comment = get_comment($val);
        if (empty($comment)) {
            $this->pushError("id", "not_found");
            return false;
        }
        return true;
    }
}