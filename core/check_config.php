<?php

if (is_null(config('default_storage_dir'))) {
    http()->error('ERR_BAD_CONFIG', 'The value of default_storage_dir is not set. ' .
        'For more details, please read the config file core/config.php');
} else if (!file_exists(config('default_storage_dir'))) {
    if (false === mkdir(config('default_storage_dir'), 0755, true)) {
        http()->error('ERR_BAD_CONFIG', 'Failed to create the storage directory: ' .
            config('default_storage_dir') . '. For more details, please read the config file core/config.php');
    }
} else if (!is_dir(config('default_storage_dir'))) {
    http()->error('ERR_BAD_CONFIG', config('default_storage_dir') . ' is not a directory. It can not be used as ' .
        'the default_storage_dir. For more details, please read the config file core/config.php');
}

if (is_null(config('installation_auth_code'))) {
    http()->error('ERR_BAD_CONFIG', 'The value of installation_auth_code is not set. ' .
        'For more details, please read the config file core/config.php');
}
