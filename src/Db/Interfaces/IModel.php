<?php

/**
 * Model interface
 */

namespace Clickio\Db\Interfaces;

/**
 * Model interface
 *
 * @package Db\Interfaces
 */
interface IModel
{
    /**
     * Upgrade table
     *
     * @return void
     */
    public function upgrade();
}