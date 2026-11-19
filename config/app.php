<?php
// config/app.php - Application Configuration

return [
    'name'      => 'GRM Inventory',
    'full_name' => 'GRM Inventory',
    'version'   => '1.0.0',
    'timezone'  => 'Asia/Kolkata',
    'debug'     => true,
    'session'   => [
        'name'     => 'GRM_INVENTORY_SESSID',
        'lifetime' => 86400, // 24 hours
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];
