<?php

// This API is designed to check whether the system is installed.

require_once __DIR__ . '/index.php';

$installed_file_path = config('default_storage_dir') . '/installed.php';

http()->send(array(
    'installed' => file_exists($installed_file_path)
));
