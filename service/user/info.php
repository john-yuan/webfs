<?php

// Get the user information of the logged-in user if the user is logged-in.
// @output The user info is returned on success.

require_once __DIR__ . '/index.php';

http()->allowedMethod('get');
http()->send(auth()->user()->getUserInfo());
