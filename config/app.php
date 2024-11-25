<?php

return [
    'name' => 'Pokemon App',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') ?: false,
    'url' => getenv('APP_URL') ?: 'http://localhost',
];