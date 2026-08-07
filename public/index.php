<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.2'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

/*
 *---------------------------------------------------------------
 * MAP APP_ENCRYPTION_KEY → encryption_key
 *---------------------------------------------------------------
 * Replit Secrets (and most CI/CD systems) cannot use dots in
 * environment variable names, so the key is stored as
 * APP_ENCRYPTION_KEY. CI4's BaseConfig reads `encryption_key`
 * (the underscore form of `encryption.key`). We map the value
 * here, before the framework boots, so CI4's native env parsing
 * and hex2bin: decoding work without any custom config code.
 */
$_appEncKey = getenv('APP_ENCRYPTION_KEY');
if ($_appEncKey !== false && $_appEncKey !== '') {
    putenv("encryption_key={$_appEncKey}");
    $_ENV['encryption_key']    = $_appEncKey;
    $_SERVER['encryption_key'] = $_appEncKey;
}
unset($_appEncKey);

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
