<?php

spl_autoload_register(function ($class_name) {
    $directories = array('classes', 'libraries');
    $index = 0;

    while ($index < count($directories)) {
        $relative_directory_name = $directories[$index];
        $abosolute_directory_name = APP_CORE_DIR . '/' . $relative_directory_name;
        $class_filename = $abosolute_directory_name . '/' . $class_name . '.php';

        $index += 1;

        if (is_file($class_filename)) {
            require $class_filename;
            break;
        // If the file is not find in the current directory push the sub directories
        // to the directories array.
        } else if (is_dir($abosolute_directory_name)) {
            if ($handle = opendir($abosolute_directory_name)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry !== '.' && $entry !== '..') {
                        if (is_dir($abosolute_directory_name . '/' . $entry)) {
                            array_push($directories, $relative_directory_name . '/' . $entry);
                        }
                    }
                }
            } else {
                throw new Exception("Failed to open the directory: $abosolute_directory_name");
            }
        }

    }
});
