<?php
return [
    'url' => 'http://localhost:8000',
    'dsn' => 'mysql:host=127.0.0.1;dbname=oxxo;charset=utf8mb4',
    'db_user' => 'oxxo',
    'db_password' => '',
    'oauth' => [
        'google' => ['id' => '', 'secret' => ''],
        'facebook' => ['id' => '', 'secret' => ''],
        'github' => ['id' => '', 'secret' => ''],
    ],
    'smtp' => [
        'host' => '', 'port' => 587, 'encryption' => 'tls',
        'user' => '', 'password' => '', 'from' => '',
    ],
];
