<?php

// The configurations for production enviornment. The content of this file will be used to replace the content of the
// file named config.php in the same directory of this file on releasing the application.
// see: src/core/config.php
// see: build.sh

config(array(
    // Whether in debug mode.
    'debug' => false,
    // The path of the storage directory. This directory will be created on the first time the application is executed
    // if it is not existed.
    'default_storage_dir' => APP_ROOT_DIR . DIRECTORY_SEPARATOR . 'storage',
    // The auth code for install the application, the default value is `webfs`, you'd better to pick another one
    // instead. This value is only used on the first time you install the application.
    'installation_auth_code' => 'webfs',
));
