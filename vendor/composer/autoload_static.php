<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc9b168e8400f8789b0e099f4aa1d40aa
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Pmaxs\\Crawler\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Pmaxs\\Crawler\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc9b168e8400f8789b0e099f4aa1d40aa::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc9b168e8400f8789b0e099f4aa1d40aa::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
