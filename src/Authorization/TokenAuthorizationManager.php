<?php
/**
 * Token Authorization
 */

namespace Clickio\Authorization;

use Clickio\Authorization\Interfaces\ITokenAuthorizationService;
use Clickio\Authorization\Services\DefaultTokenAuthorizationService;

/**
 * Token Authorization
 *
 * @package Authorization
 */
class TokenAuthorizationManager
{
    /**
     * Service instance
     *
     * @var ITokenAuthorizationService
     */
    private $_service = null;

    /**
     * Constructor
     *
     * @param ITokenAuthorizationService $srv authorization service
     */
    public function __construct(ITokenAuthorizationService $srv)
    {
        $this->_service = $srv;
    }

    /**
     * Create new authorization token
     *
     * @return string
     */
    public function generateAplicationKey(): string
    {
        $salt_start = '$'.\random_int(0, 65534).'$';
        $salt_end = '$'.\random_int(0, 65534).'$';
        $hashable_str = sprintf('%s|%s|%s', $salt_start, time(), $salt_end);
        return md5($hashable_str);
    }

    /**
     * Generate payload token
     *
     * @param string $payload payload
     *
     * @return string
     */
    public function generateToken(string $payload): string
    {
        return $this->_service->generateToken($payload);
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
        return $this->_service->validate($payload, $token);
    }

    /**
     * Factory method
     *
     * @param ?ITokenAuthorizationService $srv service
     *
     * @return self
     */
    public static function create($srv = null)
    {
        if (empty($srv)) {
            $srv = new DefaultTokenAuthorizationService();
        }
        return new static($srv);
    }
}