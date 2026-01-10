<?php

declare(strict_types=1);

return [
    'database_path' => sprintf('sqlite:%s/database/database.db', __DIR__),
    'centralbank_api' => ('https://www.yrgopelag.se/centralbank/'),
    'user' => 'Hanna',
    'calendar_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    'base_url' => $_ENV['BASE_URL'] ?? '/',
    'hotel_name' => "LOST ISLAND HOTEL",

    'paths' => [
        'index' => $_ENV['BASE_URL'] . '/index.php',
        'receipt' => $_ENV['BASE_URL'] . '/receipt.php',
        'admin' => [
            'index' => $_ENV['BASE_URL'] . '/admin/index.php'
        ],
        'posts' => [
            'create_booking' => $_ENV['BASE_URL'] . '/app/posts/create-booking.php',
            'admin' => [
                'update_price' => $_ENV['BASE_URL'] . '/app/posts/admin/update-price.php',
                'toggle_active' => $_ENV['BASE_URL'] . '/app/posts/admin/toggle-active.php',
                'insert_features' => $_ENV['BASE_URL'] . '/app/posts/admin/insert-features.php',
                'insert_package' => $_ENV['BASE_URL'] . '/app/posts/admin/insert-package.php',
                'update_discount' => $_ENV['BASE_URL'] . '/app/posts/admin/update-discount.php'
            ]
        ],
        'app' => [
            'admin-users' => [
                'login' => $_ENV['BASE_URL'] . '/app/admin-users/login.php',
                'logout' => $_ENV['BASE_URL'] . '/app/admin-users/logout.php',
            ]
        ]
    ],

    'assets' => [
        'css' => $_ENV['BASE_URL'] . '/assets/styles/app.css?ver=23',
        'images' => [
            'rooms' => $_ENV['BASE_URL'] . '/assets/images/rooms/',
            'header_logo' => $_ENV['BASE_URL'] . '/assets/images/LOST_ISLAND_HOTEL1.png',
            'star' => $_ENV['BASE_URL'] . '/assets/images/star1.png'
        ]
    ]
];