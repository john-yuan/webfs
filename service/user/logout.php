<?php

// This API is designed to let the user log out.

require_once __DIR__ . '/index.php';

http()->allowedMethod('post');
auth()->logout();
http()->send(true);
