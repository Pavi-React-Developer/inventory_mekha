<?php
// config/app.php - Application Configuration

return [
    'name'      => 'INVENTO',
    'full_name' => 'INVENTO Multi-Concern Inventory Platform',
    'version'   => '1.0.0',
    'timezone'  => 'Asia/Kolkata',
    'debug'     => true,
    'session'   => [
        'name'     => 'INVENTO_SESSID',
        'lifetime' => 86400, // 24 hours
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];
