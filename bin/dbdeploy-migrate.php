<?php
/**
 * DBDeploy PHP
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

use DBDeployPHP\DBDeploy;
use Doctrine\DBAL\DriverManager;

$files  = array(__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php');
$loader = null;

foreach ($files as $file) {
    if (file_exists($file)) {
        $loader = require $file;
        break;
    }
}

if (!$loader) {
    throw new RuntimeException('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
}

if (!isset($_SERVER['DATABASE_URL'])) {
    throw new RuntimeException("Missing environment variable DATABASE_URL in format mysql://user:password@host/datbase");
}

if (!isset($argv[1])) {
    throw new RuntimeException("Missing schema directory.");
}

$directory = $argv[1];
if (strpos($directory, '/') !== 0) {
    $directory = getcwd() . '/' . $directory;
}

$connection = DriverManager::getConnection(array('url' => $_SERVER['DATABASE_URL']));
$migrator = new DBDeploy($connection, $directory);
$migrator->migrate();
