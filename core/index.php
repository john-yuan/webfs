<?php

require_once __DIR__ . '/php_settings.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/error_handlers.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_config.php';

if (config('debug')) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}
