<?php
// Application settings. Database credentials live in database.php, which the setup wizard writes.
return [
    'name' => 'Brio',
    'currency' => 'KSh',
    'timezone' => 'Africa/Nairobi',
    'low_stock_threshold' => 10,
    'uploads' => [
        'dir' => 'uploads',                    // relative to /public; sub-folders: products, hero, pages
        'max_bytes' => 4 * 1024 * 1024,
        'mime' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
    ],
    'admin' => ['per_page' => 10],
];
