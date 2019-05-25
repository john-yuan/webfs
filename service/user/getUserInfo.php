<?php

// This API is designed to get the information of the user no matter the user is deleted or not. Administrator
// permission is required.
// @input int $user_id The id of the user.
// @output array Returns the user information on success.

require_once __DIR__ . '/index.php';

http()->allowedMethod('post');
auth()->admin();

$user_id = http()->input('user_id');

if (is_null($user_id)) {
    http()->error('ERR_USER_ID_REQUIRED', 'The user id is required!');
}

if (!is_int($user_id)) {
    http()->error('ERR_USER_ID_BAD_TYPE', 'The user id must be an integer!');
}

$user = userManager()->findUserById($user_id, true);

if (is_null($user)) {
    http()->error('ERR_USER_NOT_FOUND', 'The user is not found!');
}

http()->send($user->getUserInfo());
