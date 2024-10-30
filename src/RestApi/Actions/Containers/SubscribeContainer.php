<?php

/**
 * Subscribe container
 */

namespace Clickio\RestApi\Actions\Containers;

use Clickio\Utils\Captcha;
use Clickio\Utils\ValidatedContainer;

/**
 * Subscribe container
 *
 * @package RestApi\Actions\Containers
 */
class SubscribeContainer extends ValidatedContainer
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
}