<?php

declare(strict_types=1);

return [
    'database_path' => sprintf('sqlite:%s/database/database.db', __DIR__),
    'centralbank_api' => ('https://www.yrgopelag.se/centralbank/'),
    'user' => 'Hanna',
    'calendar_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
];
