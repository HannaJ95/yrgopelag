<?php

declare(strict_types=1);

return [
    'database_path' => sprintf('sqlite:%s/database/database.db', __DIR__),
    'centralbank_api' => ('https://www.yrgopelag.se/centralbank/'),
    'user' => 'Hanna',
    'calendar_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    'base_url' => '/yrgopelag',
    'hotel_name' => "LOST ISLAND HOTEL",

    'paths' => [
        'index' => '/index.php',
        'receipt' => '/receipt.php',
        'admin' => [
            'index' => '/admin/index.php',
            'login' => '/admin/login.php'
        ],
        'posts' => [
            'create_booking' => '/app/posts/create-booking.php',
            'admin' => [
                'update_price' => '/app/posts/admin/update-price.php',
                'toggle_active' => '/app/posts/admin/toggle-active.php',
                'insert_features' => '/app/posts/admin/insert-features.php',
                'insert_package' => '/app/posts/admin/insert-package.php',
                'login' => '/app/admin-users/login.php'
            ]
        ]
    ],

    'assets' => [
        'css' => '/assets/styles/app.css'
    ]
];
