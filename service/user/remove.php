<?php

// This API is designed to remove the user from the system. The administator permission is required. You can not use
// this API to delete yourself.
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

if ($user_id === $admin->getUserId()) {
    http()->error('ERR_CAN_NOT_DELETE_YOURSELF', 'You can not delete yourself!');
}

http()->send(array(
    'user_deleted' => userManager()->deleteUser($user_id)
));
