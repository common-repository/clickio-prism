<?php

/**
 * Task base class
 */

namespace Clickio\Tasks\Tasks;

/**
 * Base class for any task
 *
 * @package Tasks\Tasks
 */
abstract class AbstractTask
{
    /**
     * Default task name
     *
     * @var string
     */
    const NAME = "undefined";

    /**
     * Getter.
     * Get task name
     *
     * @return string
     */
    public static function getName()
    {
        return static::NAME;
    }
}
