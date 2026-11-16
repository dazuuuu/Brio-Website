<?php
// Application settings. Database credentials live in database.php, which the setup wizard writes.
return [
    'name' => 'Asiva Organics',
    'currency' => 'KSh',
    'currency_code' => 'KES',
    // Where Google ID tokens are checked. Only change this for testing.
    'google_tokeninfo_url' => 'https://oauth2.googleapis.com/tokeninfo',
    'timezone' => 'Africa/Nairobi',
    'low_stock_threshold' => 10,
    'uploads' => [
        'dir' => 'uploads',                    // relative to /public; sub-folders: products, hero, pages
        'max_bytes' => 4 * 1024 * 1024,
        'mime' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
        'video_mime' => ['video/mp4' => 'mp4', 'video/webm' => 'webm'],
        'video_max_bytes' => 25 * 1024 * 1024,
    ],
    'admin' => ['per_page' => 10],
];
