<?php

declare(strict_types=1);

namespace Bono\Config;

use Dotenv\Dotenv;

/**
 * Env class to handle environment variable initialization.
 * This class loads environment variables from a .env file located in the
 * parent directory. It ensures that the environment is only initialized once
 * to avoid redundant loading.
 */
class Env
{
    private static bool $initialized = false;

    public static function initialize(): void
    {
        if (Env::$initialized) {
            return;
        }
        $dotenv = Dotenv::createImmutable('./');
        try {
            $dotenv->load();
        } catch (\Exception $e) {
            // Handle the case where the .env file is not found or cannot be loaded
            // You might want to log this or throw an exception
            error_log(
                'Could not load .env file, using default settings as fallback!'
            );
        }
        Env::$initialized = true;
    }
}