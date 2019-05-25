<?php

// This API is designed to restore the deleted user. If the user is not deleted an error (ERR_USER_NOT_DELETED) will be
// thrown. The root user can restore both the administrator and the normal user. The administrator can restore the
// normal user only.
// @input int $user_id The user id of the deleted user.
// @ipput string $new_username The username to use when restore the user. Can be the old username.

require_once __DIR__ . '/index.php';

http()->allowedMethod('post');

$admin = auth()->admin();
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

$user = userManager()->findUserById($user_id, true);

if (is_null($user)) {
    http()->error('ERR_USER_NOT_FOUND', 'Failed to restore the user. The user is not found!');
}

if (!$user->isDeleted()) {
    http()->error('ERR_USER_NOT_DELETED', 'Failed to restore the user. The user is not deleted!');
}

if ($user->isRootUser()) {
    http()->error('ERR_CAN_NOT_RESTORE_ROOT_USER', 'You can not restore the root user!');
}

if ($user->isAdmin() && (!$admin->isRootUser())) {
    http()->error('ERR_CAN_NOT_RESTORE_ADMINISTRATOR', 'Permission denied. You can not restore the administrator!');
}

try {
    userManager()->restoreUser($user_id, $new_username);
} catch (Exception $exception) {
    if ($exception->getCode() === 1) {
        http()->error('ERR_USER_NOT_DELETED', $exception->getMessage());
    } else if ($exception->getCode() === 2) {
        http()->error('ERR_USERNAME_TAKEN', $exception->getMessage());
    } else if ($exception->getCode() === 3) {
        http()->error('ERR_USER_NOT_FOUND', $exception->getMessage());
    } else {
        http()->error('ERR_RESTORE_USRE', $exception->getMessage());
    }
}

http()->send(true);
