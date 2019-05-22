<?php

// This API is designed for updating the password of the user.
// The user can update the password of herself/himself (the old password is required).
// The administrator can update the password of the normal user (User::USER).
// The root user can update the password of all user types (User::USER and User::ADMIN).
// @input int $user_id The user id of the user to be updated.
// @input string $new_password The new password.
// @input string $old_password The old password is required for updating password of the login user.
// @ouput The result of the updated status is returned on success. There is a chance that we may fail to update the
// password. If that happend, we should tell the user to try again.

require_once __DIR__ . '/index.php';

http()->allowedMethod('post');

$login_user = auth()->user();
$user_id = http()->input('user_id');
$new_password = http()->input('new_password');
$old_password = http()->input('old_password');

if (is_null($user_id)) {
    http()->error('ERR_USER_ID_REQUIRED', 'The user id is required!');
}

if (is_null($new_password)) {
    http()->error('ERR_NEW_PASSWORD_REQUIRED', 'The new password is required!');
}

if (!is_int($user_id)) {
    http()->error('ERR_USER_ID_BAD_TYPE', 'The user id must be an integer!');
}

if (!is_string($new_password)) {
    http()->error('ERR_NEW_PASSWORD_BAD_TYPE', 'The new password must be a string!');
}

$user = userManager()->findUserById($user_id);

if (is_null($user)) {
    http()->error('ERR_USER_NOT_FOUND', 'The user is not found!');
}

if ($login_user->getUserId() === $user->getUserId()) {
    if (is_null($old_password)) {
        http()->error('ERR_OLD_PASSWORD_REQUIRED', 'The old password is required!');
    }
    if (!is_string($old_password)) {
        http()->error('ERR_OLD_PASSWORD_BAD_TYPE', 'The old password must be a string!');
    }
    if ($user->confirmPassword($old_password)) {
        if ($old_password === $new_password) {
            http()->error('ERR_SAME_PASSWORD', 'The new password is same as the old one!');
        } else {
            http()->send(array(
                'updated' => userManager()->updateUserPassword($user_id, $new_password)
            ));
        }
    } else {
        http()->error('ERR_OLD_PASSWORD_NOT_CORRECT', 'The old password is not correct!');
    }
} else {
    $permission_not_denied = false;

    if ($login_user->isRootUser()) {
        $permission_not_denied = true;
    } else if ($login_user->isAdmin() && (!$user->isAdmin())) {
        $permission_not_denied = true;
    }

    if ($permission_not_denied) {
        if ($user->confirmPassword($new_password)) {
            http()->error('ERR_SAME_PASSWORD', 'The new password is same as the old one!');
        } else {
            http()->send(array(
                'updated' => userManager()->updateUserPassword($user_id, $new_password)
            ));
        }
    } else {
        http()->error('ERR_PERMISSION_DENIED', 'Permission denied. Can not update the password!');
    }
}
