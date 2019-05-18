<?php

if (is_null(config('default_storage_dir'))) {
    echo '<h1>Config Error</h1>';
    echo '<p>The value of <b>default_storage_dir</b> is not set.<p>';
    echo '<p>For more details, please read the config file ' . __DIR__ . DIRECTORY_SEPARATOR . 'config.php</p>';
    exit;
} else if (!file_exists(config('default_storage_dir'))) {
    if (false === mkdir(config('default_storage_dir'), 0755, true)) {
        echo '<h1>Config Error</h1>';
        echo '<p>Failed to create the storage directory: ' . config('default_storage_dir') . '</p>';
        echo '<p>For more details, please read the config file ' . __DIR__ . DIRECTORY_SEPARATOR . 'config.php</p>';
        exit;
    }
} else if (!is_dir(config('default_storage_dir'))) {
    echo '<h1>Config Error</h1>';
    echo '<p>' . config('default_storage_dir') .' is not a directory. It can not be using as <b>default_storage_dir</b>.</p>';
    echo '<p>For more details, please read the config file ' . __DIR__ . DIRECTORY_SEPARATOR . 'config.php</p>';
    exit;
}
