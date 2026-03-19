<?php

return [
    'require_invite_code' => (bool) env('ADMIN_REQUIRE_INVITE_CODE', true),
    'invite_code' => env('ADMIN_REGISTER_INVITE_CODE'),
    'master_key' => env('ADMIN_MASTER_KEY'),
    'enable_demo_account' => (bool) env('ADMIN_ENABLE_DEMO_ACCOUNT', true),
    'demo' => [
        'name' => env('ADMIN_DEMO_NAME', 'Demo Admin Hackathon'),
        'email' => env('ADMIN_DEMO_EMAIL', 'demo.admin@hackathon.local'),
        'password' => env('ADMIN_DEMO_PASSWORD', 'Demo12345!'),
    ],
];
