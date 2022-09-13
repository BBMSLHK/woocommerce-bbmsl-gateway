<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit420c4dacebde77c3d619ca07c66ed26b
{
    public static $files = array (
        'decc78cc4436b1292c6c0d151b19445c' => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'phpseclib3\\' => 11,
        ),
        'P' => 
        array (
            'ParagonIE\\ConstantTime\\' => 23,
        ),
        'B' => 
        array (
            'BBMSL_Gateway\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'phpseclib3\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'ParagonIE\\ConstantTime\\' => 
        array (
            0 => __DIR__ . '/..' . '/paragonie/constant_time_encoding/src',
        ),
        'BBMSL_Gateway\\' => 
        array (
            0 => '/',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit420c4dacebde77c3d619ca07c66ed26b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit420c4dacebde77c3d619ca07c66ed26b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit420c4dacebde77c3d619ca07c66ed26b::$classMap;

        }, null, ClassLoader::class);
    }
}