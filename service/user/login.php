<?php

// This API is designed to let the user log in. Before login, a logout operation is performed.
// @input string $username The user name.
// @input string $password The password.
// @output The user info is returned on success.

require_once __DIR__ . '/index.php';

http()->allowedMethod('post');

auth()->logout();

$username = http()->input('username');
$password = http()->input('password');

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

$user = auth()->login($username, $password);

if (is_null($user)) {
    http()->error('ERR_LOGIN', 'Failed to login!');
} else {
    http()->send($user->getUserInfo());
}
