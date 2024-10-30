<?php
/**
 * Authorization service interface
 */

namespace Clickio\Authorization\Interfaces;

/**
 * Authorization service interface
 *
 * @package Authorization\Interfaces
 */
interface ITokenAuthorizationService
{

    /**
     * Validate message
     *
     * @param string $payload message payload
     * @param string $token token in message
     *
     * @return bool
     */
    public function validate(string $payload, string $token): bool;

    /**
     * Generate payload token
     *
     * @param string $payload payload
     *
     * @return string
     */
    public function generateToken(string $payload): string;
}