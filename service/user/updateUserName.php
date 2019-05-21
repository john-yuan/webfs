<?php

// This API is designed for updating the name of the user.
// The user can update the name of herself/himself.
// The administrator can update the name of the normal user (User::USER).
// The root user can update the name of all user types (User::USER and User::ADMIN).
// @input int $user_id The user id of the user to be updated.
// @input string $new_username The new user name.
// @ouput The updated user info is returned on success.

require_once __DIR__ . '/index.php';

http()->allowedMethod('post');

$login_user = auth()->user();
$user_id = http()->input('user_id');
$new_username = http()->input('new_username');

if (is_null($user_id)) {
    http()->error('ERR_USER_ID_REQUIRED', 'The user id is required!');
}

if (is_null($new_username)) {
    http()->error('ERR_NEW_USERNAME_REQUIRED', 'The new username is required!');
}

if (!is_int($user_id)) {
    http()->error('ERR_USER_ID_BAD_TYPE', 'The user id must be an integer!');
}

if (!is_string($new_username)) {
    http()->error('ERR_NEW_USERNAME_BAD_TYPE', 'The new username must be a string!');
}

$user = userManager()->findUserById($user_id);

if (is_null($user)) {
    http()->error('ERR_USER_NOT_FOUND', 'The user is not found!');
}

$permission_not_denied = false;

if ($login_user->getUserId() === $user->getUserId()) {
    $permission_not_denied = true;
} else if ($login_user->isRootUser()) {
    $permission_not_denied = true;
} else if ($login_user->isAdmin() && (!$user->isAdmin())) {
    $permission_not_denied = true;
}

if ($permission_not_denied) {
    if ($user->getUserName() === $new_username) {
        http()->send($user->getUserInfo());
    } else {
        $updated_user = userManager()->updateUserName($user_id, $new_username);

        if (is_null($updated_user)) {
            http()->error('ERR_USERNAME_TAKEN', 'Failed to update the username. The username is taken!');
        } else {
            http()->send($updated_user->getUserInfo());
        }
    }
} else {
    http()->error('ERR_PERMISSION_DENIED', 'Permission denied. Can not update the username!');
}
