<?php

namespace OptivacConsent\Core;

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    private static function autoload(string $class): void
    {
        if (strpos($class, 'OptivacConsent\\') !== 0) {
            return;
        }

        $path = OPTIVAC_CONSENT_PATH . 'src/' . str_replace(
            ['OptivacConsent\\', '\\'],
            ['', DIRECTORY_SEPARATOR],
            $class
        ) . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }
}