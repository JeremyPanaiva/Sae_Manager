<?php

/**
 * Autoloader Class
 *
 * Implements PSR-4 compatible autoloading for the application.
 * Automatically loads class files based on their namespace and class name,
 * eliminating the need for manual require/include statements.
 *
 * How it works:
 * - Registers an autoload function with PHP's SPL autoloader
 * - When a class is referenced, PHP calls this function with the fully qualified class name
 * - The autoloader converts the namespace to a file path and includes the file if it exists
 *
 * Example:
 * When you use:  new Controllers\User\LoginController()
 * It will load: src/Controllers/User/LoginController.php
 *
 * @package Root
 */
class Autoloader
{
    /**
     * Registers the autoload function with PHP's SPL autoloader
     *
     * This method should be called once at application startup (typically in index.php).
     * It registers an anonymous function that will be called automatically whenever
     * PHP encounters a class that hasn't been loaded yet.
     *
     * The autoloader converts namespace separators (\) to directory separators
     * and looks for the corresponding file in the 'src' directory.
     *
     * Conversion logic:
     * - Namespace:  Controllers\User\LoginController
     * - Becomes: src/Controllers/User/LoginController.php
     *
     * @return void
     */
    public static function register()
    {
        spl_autoload_register(function ($class) {
            // Convert namespace to file path
            // Example: "Controllers\User\Login" → "src/Controllers/User/Login.php"
            $file = 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

            // If the file exists, include it
            if (file_exists($file)) {
                require $file;
                return true;
            }

            // File not found, return false to allow other autoloaders to try
            return false;
        });
    }
}

// Register the autoloader immediately when this file is included
Autoloader:: register();