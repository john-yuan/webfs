<?php

// This API is designed to add user into the system. The root user can create administrator and normal user. The
// administrator can create normal user. The normal user can not create user. Root user can not be created with this
// API.
// @input string $username The user name.
// @input string $password The password.
// @input string $type The user type. ADMIN or USER.
// @output The user info is returned on success.

require_once __DIR__ . '/index.php';

http()->allowedMethod('post');

$admin = auth()->admin();
$username = http()->input('username');
$password = http()->input('password');
$nickname = http()->input('nickname');
$type = http()->input('type');

if (is_null($username)) {
    http()->error('ERR_USERNAME_REQUIRED', 'The username is required!');
}

if (is_null($password)) {
    http()->error('ERR_PASSWORD_REQUIRED', 'The password is required!');
}

if (is_null($type)) {
    http()->error('ERR_USER_TYPE_REQUIRED', 'The user type is required!');
}

if (!is_null($nickname) && !is_string($nickname)) {
    http()->error('ERR_NICKNAME_BAD_TYPE', 'The nickname must be a string!');
}

if (!is_string($username)) {
    http()->error('ERR_USERNAME_BAD_TYPE', 'The username must be a string!');
}

if (!is_string($password)) {
    http()->error('ERR_PASSWORD_BAD_TYPE', 'The password must be a string!');
}

if (!is_string($type)) {
    http()->error('ERR_USER_TYPE_BAD_TYPE', 'The user type must be a string!');
}

if ($type !== User::USER && $type !== User::ADMIN) {
    http()->error('ERR_BAD_USER_TYPE', "User type must be USER or ADMIN, but $type is given!");
}

if ($type === User::ADMIN && (!$admin->isRootUser())) {
    http()->error('ERR_CAN_NOT_CREATE_ADMINISTRATOR', 'Permission denied. You can not create the administrator!');
}

try {
    $user = userManager()->createUser($username, $password, $type, false, $nickname);
} catch (Exception $exception) {
    if ($exception->getCode() === 1) {
        http()->error('ERR_BAD_USER_TYPE', $exception->getMessage());
    } else if ($exception->getCode() === 2) {
        http()->error('ERR_PASSWORD_TOO_LONG', $exception->getMessage());
    } else {
        http()->error('ERR_CREATE_USER', $exception->getMessage(), array(
            'exception_code' => $exception->getCode()
        ));
    }
}

if (is_null($user)) {
    http()->error('ERR_USERNAME_TAKEN', 'The username is already taken!');
}

http()->send($user->getUserInfo());
