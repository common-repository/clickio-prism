<?php
/**
 * Extra content interface
 */

namespace Clickio\ExtraContent\Interfaces;

/**
 * Extra content service
 *
 * @package Extra
 */
interface IExtraContentService
{

    /**
     * Entry point
     * Get extra content
     *
     * @param bool $force ignore settings
     *
     * @return array
     */
    public function getExtraContent(bool $force = false): array;

    /**
     * Parse source id
     *
     * @param string $source_id source id
     *
     * @return array
     */
    public static function extractSourceId(string $source_id): array;

    /**
     * Get service name
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * Get service label
     *
     * @return string
     */
    public static function getLabel(): string;

    /**
     * Get options key
     *
     * @return string
     */
    public function getOptionsContainer(): string;

    /**
     * Get extra content source
     *
     * @return array
     */
    public function getExtraContentSource(): array;

    /**
     * Setter
     * Set service rules
     *
     * @param array $rules service rules
     *
     * @return void
     */
    public function setRules(array $rules);
}
