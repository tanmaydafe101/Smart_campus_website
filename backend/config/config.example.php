<?php
// Example configuration file for production / staging environments
// Copy this file to config.php or update database.php with production credentials

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'smart_campus',
        'username' => 'campus_user',
        'password' => 'SecureCampusPass2026!',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'base_url' => 'http://localhost/SmartCampus/backend/',
        'upload_dir' => __DIR__ . '/../uploads/tickets/',
        'max_file_size' => 5 * 1024 * 1024, // 5MB
        'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp']
    ]
];
?>
