<?php

// This API is designed to get the public details of the RSA key. The details is used to encrypt the password in the
// browser.

require_once __DIR__ . '/index.php';

http()->send(RSAPassword::getPublicDetails());
