<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4bea1af113f2443d297d1b2373481389
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'Funclib\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Funclib\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/Funclib',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4bea1af113f2443d297d1b2373481389::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4bea1af113f2443d297d1b2373481389::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
