<?php

// This API is designed to remove the user from the system. Only the root user can delete both the administrator and the
// normal user. The administrator can delete the normal user. The root user can not be deleted.
// @input int $user_id The id of the user to be removed.

require_once __DIR__ . '/index.php';

http()->allowedMethod('post');

$admin = auth()->admin();
$user_id = http()->input('user_id');

if (is_null($user_id)) {
    http()->error('ERR_USER_ID_REQUIRED', 'The user id is required!');
}

if (!is_int($user_id)) {
    http()->error('ERR_USER_ID_BAD_TYPE', 'The user id must be an integer!');
}

$user = userManager()->findUserById($user_id);

if (is_null($user)) {
    http()->error('ERR_USER_NOT_FOUND', 'The user is not found!');
}

if ($user->isRootUser()) {
    http()->error('ERR_CAN_NOT_DELETE_ROOT_USER', 'You can not delete the root user!');
}

if ($user->isAdmin()) {
    if (!$admin->isRootUser()) {
        http()->error('ERR_CAN_NOT_DELETE_ADMINISTRATOR', 'Permission denied. You can not delete the administrator!');
    }
}

http()->send(userManager()->deleteUser($user_id));
