<?php

/*
|--------------------------------------------------------------------------
| Vercel serverless entry point for Laravel
|--------------------------------------------------------------------------
| Vercel's filesystem is READ-ONLY except for /tmp. Laravel needs to write
| compiled views, bootstrap caches, logs and sessions. We create the writable
| paths under /tmp on each cold start; the matching env vars (set in the Vercel
| dashboard) point Laravel at them:
|   VIEW_COMPILED_PATH, APP_CONFIG_CACHE, APP_EVENTS_CACHE, APP_PACKAGES_CACHE,
|   APP_ROUTES_CACHE, APP_SERVICES_CACHE, LOG_CHANNEL=stderr
| Sessions & cache use the database driver, and uploaded images use S3 (the
| local disk does not persist on Vercel).
*/

$writable = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($writable as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Hand the request to Laravel's normal front controller.
require __DIR__ . '/../public/index.php';
