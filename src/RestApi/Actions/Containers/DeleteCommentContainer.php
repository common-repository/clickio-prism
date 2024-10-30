<?php

/**
 * Delete comment container
 */

namespace Clickio\RestApi\Actions\Containers;

use Clickio\Utils\UserUtils;
use Clickio\Utils\ValidatedContainer;

/**
 * External data to delete comment
 *
 * @package RestApi\Actions\Containers
 */
class DeleteCommentContainer extends ValidatedContainer
{
    /**
     * Comment id
     *
     * @var int
     */
    protected $id = 0;

    /**
     * User id
     *
     * @var int
     */
    protected $user_id = 0;

    /**
     * Validate comment id
     *
     * @param mixed $var expects comment id
     *
     * @return bool
     */
    public function validateId($var): bool
    {
        if (empty($var) || !is_numeric($var) || $var < 0) {
            $this->pushError("id", "field_required");
            return false;
        }

        $comment = get_comment($var);
        if (empty($comment)) {
            $this->pushError("id", "not_found");
            return false;
        }
        return true;
    }

    /**
     * Validate user id
     *
     * @param mixed $var expected user id
     *
     * @return bool
     */
    public function validateUid($var): bool
    {
        if (empty($var) || !is_numeric($var) || $var < 0) {
            $this->pushError("user_id", "field_required");
            return false;
        }

        $decoded = UserUtils::decryptUserId($var);
        $user = get_user_by("ID", $decoded);
        if (empty($user)) {
            $this->pushError("user_id", "not_found");
            return false;
        }
        return true;
    }
}