<?php

// This file is the config file for the debug enviornment. The content of this file will be replaced by the content of
// the file named config_production.php in the same directory of this file on releasing the application. Please make
// sure that the configurations in this file is also available in the file named config_production.php in the same
// directory of this file (the values of the configurations maybe different).
// see: src/core/config_production.php
// see: build.sh

// The full documentation of each configuration item is available in the file named config_production.php in the same
// directory of this file. Please read that file for more information and modify that file for your deployment.
config(array(
    'debug' => true,
    'default_storage_dir' => APP_ROOT_DIR . DIRECTORY_SEPARATOR . 'storage',
    'installation_auth_code' => 'webfs',
));
