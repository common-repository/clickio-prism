<?php

/**
 * Abstract extra content service
 */

namespace Clickio\ExtraContent\Services;

use Clickio\Logger\LoggerAccess;

/**
 * Abstract content service
 *
 * @package ExtraContent\Services
 */
abstract class ContentServiceBase
{
    /**
     * Simplified access to logs
     */
    use LoggerAccess;

    /**
     * Service alias
     *
     * @var string
     */
    const NAME = 'undefined';

    /**
     * Servicce label
     *
     * @var string
     */
    const LABEL = 'No Name';

    /**
     * Get service name
     *
     * @return string
     */
    public static function getName(): string
    {
        return static::NAME;
    }

    /**
     * Get service label
     *
     * @return string
     */
    public static function getLabel(): string
    {
        return static::LABEL;
    }
}
