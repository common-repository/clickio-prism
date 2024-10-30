<?php
/**
 * Token Authorization
 */

namespace Clickio\Authorization\Services;

use Clickio\Authorization\Interfaces\ITokenAuthorizationService;
use Clickio\Options;

/**
 * Dafault authorization service
 *
 * @package Authorization\Services
 */
class DefaultTokenAuthorizationService implements ITokenAuthorizationService
{

    /**
     * Hash salt
     *
     * @var string
     */
    private $_salt = "|$|";

    /**
     * Generate payload token
     *
     * @param string $payload payload
     *
     * @return string
     */
    public function generateToken(string $payload): string
    {
        $app_key = Options::getApplicationKey();
        $hashable = sprintf("%s%s%s", $payload, $this->_salt, $app_key);
        return md5($hashable);
    }

    /**
     * Validate message
     *
     * @param string $payload message payload
     * @param string $token token in message
     *
     * @return bool
     */
    public function validate(string $payload, string $token): bool
    {
        $new_token = $this->generateToken($payload);
        return $new_token == $token;
    }
}
