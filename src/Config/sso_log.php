<?php
// Cara Guna dalam Kod Pakej
// Log::channel('sso_log')->info('[SSO] Login berjaya oleh user ID: ' . $userId);
// Log::channel('sso_log')->error('[SSO] Ralat ketika login: ' . $exception->getMessage());

return [
    'driver' => 'daily',
    'path' => storage_path('logs/sso/sso.log'),
    'level' => 'debug',
    'days' => 180,
];
