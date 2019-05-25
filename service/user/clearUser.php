<?php

// This API is designed to clear the user from the system. Only the root user can clear both the administrator and the
// normal user. The administrator can clear the normal user. The root user can not be cleared. The user must be deleted
// before being cleared.
// @input int $user_id The id of the user to be cleared.

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

$user = userManager()->findUserById($user_id, true);

if (is_null($user)) {
    http()->error('ERR_USER_NOT_FOUND', 'Failed to clear the user. The user is not found!');
}

if (!$user->isDeleted()) {
    http()->error('ERR_USER_NOT_DELETED', 'Failed to clear the user. The user is not deleted!');
}

if ($user->isRootUser()) {
    http()->error('ERR_CAN_NOT_CLEAR_ROOT_USER', 'You can not clear the root user!');
}

if ($user->isAdmin() && (!$admin->isRootUser())) {
    http()->error('ERR_CAN_NOT_CLEAR_ADMINISTRATOR', 'Permission denied. You can not clear the administrator!');
}

try {
    userManager()->clearUser($user_id);
} catch (Exception $exception) {
    if ($exception->getCode() === 1) {
        http()->error('ERR_USER_NOT_FOUND', $exception->getMessage());
    } else if ($exception->getCode() === 2) {
        http()->error('ERR_USER_NOT_DELETED', $exception->getMessage());
    } else {
        http()->error('ERR_CLEAR_USER', $exception->getMessage());
    }
}

http()->send(true);
