<?php

// This is the configuration file of the application.

config(array(
    // Whether in debug mode. Please change the value to `false` when you deploy the application to the production
    // environment.
    'debug' => true,
    // The path of the storage directory. The storage directory will be created if it does not exist.
    'default_storage_dir' => APP_ROOT_DIR . DIRECTORY_SEPARATOR . 'storage',
    // The authorization code for installing the application, the default value is `webfs`, you'd better to pick another
    // one instead.
    'installation_auth_code' => 'webfs',
));
