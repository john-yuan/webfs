<?php

// This API is designed to register the first admin user in the system if the system is not installed.
// If the system is already installed before, no user will be registered and an error will be thrown.
// @input string $installation_auth_code The installation auth code.
// @input string $username The user name.
// @input string $password The password.
// @output The user info is returned on success.

require_once __DIR__ . '/../index.php';

http()->allowedMethod('post');

$installed_file_path = config('default_storage_dir') . '/installed.php';

if (file_exists($installed_file_path)) {
    http()->error('ERR_SYSTEM_INSTALLED', 'The system is already installed!');
} else {
    $installation_auth_code = http()->input('installation_auth_code');
    $username = http()->input('username');
    $password = http()->input('password');

    if (is_null($installation_auth_code)) {
        http()->error('ERR_INSTALLATION_AUTH_CODE_REQUIRED', 'The installation auth code is required!');
    }

    if ($installation_auth_code !== config('installation_auth_code')) {
        http()->error('ERR_BAD_INSTALLATION_AUTH_CODE', 'The installation auth code is not correct!');
    }

    if (is_null($username)) {
        http()->error('ERR_USERNAME_REQUIRED', 'The username is required!');
    }

    if (is_null($password)) {
        http()->error('ERR_PASSWORD_REQUIRED', 'The password is required!');
    }

    if (!is_string($username)) {
        http()->error('ERR_USERNAME_BAD_TYPE', 'The username must be a string!');
    }

    if (!is_string($password)) {
        http()->error('ERR_PASSWORD_BAD_TYPE', 'The password must be a string!');
    }

    if (strlen($password) > UserManager::MAX_PASSWORD_LENGTH) {
        http()->error('ERR_PASSWORD_TOO_LONG', 'The length of the password must be less than equal to ' .
            UserManager::MAX_PASSWORD_LENGTH . '!');
    }

    $user = null;

    try {
        $user = userManager()->createUser($username, $password, User::ADMIN);
    } catch (Exception $exception) {
        http()->error('ERR_CREATE_USER', $exception->getMessage());
    }

    if (is_null($user)) {
        http()->error('ERR_USER_EXISTS', 'The user already exists!');
    } else {
        file_put_contents($installed_file_path, "<?php\n\n// The system is installed.\n");
        http()->send($user->getUserInfo());
    }
}
