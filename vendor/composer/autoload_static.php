<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5315d9b4408df7bd0971e87f2407efe0
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Clickio\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Clickio\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5315d9b4408df7bd0971e87f2407efe0::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5315d9b4408df7bd0971e87f2407efe0::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
