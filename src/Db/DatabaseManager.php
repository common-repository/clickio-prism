<?php

/**
 * Database manager
 */

namespace Clickio\Db;

use Clickio\Options;
use Clickio\Utils\FileSystem;

/**
 * Database manager
 *
 * @package Db
 */
class DatabaseManager
{

    /**
     * Database version
     *
     * @var int
     */
    const VERSION = 3;

    /**
     * Update db if required
     *
     * @return void
     */
    public static function updateIfRequired()
    {
        $version = (int)Options::get('db_version');
        if ($version != static::VERSION) {
            static::upgradeDb();
            Options::set('db_version', static::VERSION);
            Options::save();
        }
    }

    /**
     * Upgrade all tables in database
     *
     * @return void
     */
    protected static function upgradeDb()
    {
        $models_dir = static::getModelsDir();

        foreach (FileSystem::scandir($models_dir) as $file) {
            $class_name = basename($file, '.php');
            $cls = sprintf("%s\Models\%s", __NAMESPACE__, $class_name);
            if (class_exists($cls)) {
                $model = ModelFactory::create($cls);
                $model->upgrade();
            }
        }
    }

    /**
     * Models dir getter
     *
     * @return string
     */
    public static function getModelsDir(): string
    {
        $pattern = "%s/src/Db/Models";
        return sprintf($pattern, CLICKIO_PLUGIN_DIR);
    }
}
