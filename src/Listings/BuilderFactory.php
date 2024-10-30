<?php
/**
 * Listing builder factory
 */

namespace Clickio\Listings;

use Clickio\Listings\Interfaces\IListingBuilder;
use Clickio\Listings\Interfaces\IPostBuilder;
use Clickio\Listings\Interfaces\ITermBuilder;
use Clickio\Utils\FileSystem;

/**
 * Builder Factory
 *
 * @package Listings
 */
class BuilderFactory
{
    /**
     * Only one discover
     *
     * @var bool
     */
    private static $_discovered = false;

    /**
     * Post builders map
     *
     * @var array
     */
    protected static $post_builders_map = [];

    /**
     * Term builders map
     *
     * @var array
     */
    protected static $term_builders_map = [];

    /**
     * Auto discover all builder
     *
     * @return void
     */
    public static function discover()
    {
        if (static::$_discovered) {
            return ;
        }

        $dir_path = sprintf("%s/Builders", dirname(__FILE__));
        foreach (FileSystem::scandir($dir_path) as $target) {
            $cls = sprintf("%s\%s\%s", __NAMESPACE__, "Builders", basename($target, '.php'));
            $implements = class_implements($cls, true);
            if (!empty($implements) && in_array(IListingBuilder::class, $implements)) {
                $alias = $cls::getAlias();
                if (in_array(IPostBuilder::class, $implements)) {
                    static::$post_builders_map[$alias] = $cls;
                } else if (in_array(ITermBuilder::class, $implements)) {
                    static::$term_builders_map[$alias] = $cls;
                }
            }
        }

        static::$_discovered = true;
    }

    /**
     * Get all available builders
     *
     * @param string $builder_type builder interface
     *
     * @return array
     */
    public static function getBuilders(string $builder_type): array
    {
        if (!static::$_discovered) {
            static::discover();
        }

        $map = [];

        switch($builder_type) {
            case IPostBuilder::class:
                $map = static::$post_builders_map;
                break;
            case ITermBuilder::class:
                $map = static::$term_builders_map;
                break;
            default:
                $map = static::$post_builders_map;
                break;
        }
        return $map;
    }

    /**
     * Get all available builders
     *
     * @return array
     */
    public static function getAllBuilders(): array
    {
        if (!static::$_discovered) {
            static::discover();
        }

        return array_merge(static::$post_builders_map, static::$term_builders_map);
    }

    /**
     * Get builder class
     *
     * @param string $alias builder alias
     *
     * @return string
     */
    public static function getBuilderType(string $alias): string
    {
        $builders = static::getAllBuilders();
        if (array_key_exists($alias, $builders)) {
            return $builders[$alias];
        }
        return '';
    }

    /**
     * Create new Post builder
     *
     * @param string $builder builder alias
     * @param array $args builder args
     *
     * @return IPostBuilder
     */
    public static function createPostBuilder(string $builder, array $args): IPostBuilder
    {
        return static::_create(IPostBuilder::class, $builder, $args);
    }

    /**
     * Create new Term builder
     *
     * @param string $builder builder alias
     * @param array $args builder args
     *
     * @return IPostBuilder
     */
    public static function createTermBuilder(string $builder, array $args): ITermBuilder
    {
        return static::_create(ITermBuilder::class, $builder, $args);
    }

    /**
     * Create builder by its type
     *
     * @param string $type builder interface
     * @param string $builder builder alias
     * @param array $args builder args
     *
     * @return IListingBuilder
     */
    private static function _create(string $type, string $builder, array $args): IListingBuilder
    {
        if (static::builderExists($builder, $type)) {
            $builders = static::getBuilders($type);
            $cls = $builders[$builder];
            return new $cls(...$args);
        }
        throw new \Exception("Builder $builder doesn't exists");
    }

    /**
     * Check were builder exists
     *
     * @param string $builder builder alias
     * @param string $type builder interface
     *
     * @return bool
     */
    public static function builderExists(string $builder, string $type): bool
    {
        $builders = static::getBuilders($type);
        return array_key_exists($builder, $builders);
    }
}
