<?php

// This API is designed to get the user list. The list can be filtered by the user status.
// @input string $status The status to filter the user list. Can be `active`, `deleted` and `any`. The default status is
// `active`.
// @output array The user list is returned on success.

require_once __DIR__ . '/index.php';

http()->allowedMethod('get');
auth()->admin();

$status = http()->input('status', 'active');

if ( ! ( $status === 'active' || $status === 'deleted' || $status === 'any' ) ) {
    http()->error('ERR_UNKNOWN_STATUS', 'The status is unknown!');
} else {
    http()->send(userManager()->getUserList($status));
}
