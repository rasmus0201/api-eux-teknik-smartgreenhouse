<?php

define('ESP32_PROJECT_PATH', dirname(dirname(__DIR__)));
define('ESP32_APP_PATH', dirname(__DIR__));

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once ESP32_PROJECT_PATH . '/vendor/autoload.php';

require_once ESP32_APP_PATH . '/helpers.php';

$dotenv = Dotenv\Dotenv::createImmutable(ESP32_PROJECT_PATH);
$dotenv->load();
