<?php

return [
    'enable' => true,
    'base_home' => env('SSO_BASE_HOME', null), // 'home'
    'registration_route' => env('SSO_REGISTRATION_ROUTE', null), // Nama route klien untuk pengguna yang belum wujud
    'client_origin' => env('SSO_ORIGIN', ''),
    'client_token' => env('SSO_TOKEN', ''),
    // Rahsia sistem (php artisan sso:rahsia di iLogin). Jika diisi, aliran kod digunakan; jika kosong, aliran cookie lama.
    'client_secret' => env('SSO_SECRET', ''),
    'api_url' => rtrim((string) env('SSO_BASE_URL', ''), '/'), // URL iLogin, wajib,
    'api_path' => '/api/user',
    'token_path' => '/api/sso/token',
    'verify_curl' => env('VERIFY_SSO', true),
    'guard' => 'web',
    'cache_key' => 'sso_token',
    'user_model' => \App\Models\User::class,
    'auth_fields' => "email", // Contoh: "email,nokp"
    'status_field' => null, //'is_active'
    'active_value' => null, // Nilai yang dianggap "aktif".
];
