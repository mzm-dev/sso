<?php

return [
    'enable' => true,
    'base_home' => env('SSO_BASE_HOME', null), // 'home'
    'registration_route' => env('SSO_REGISTRATION_ROUTE', null), // Nama route klien untuk pengguna yang belum wujud
    'client_origin' => env('SSO_ORIGIN', ''),
    'client_token' => env('SSO_TOKEN', ''),
    'api_url' => env('SSO_BASE_URL', 'https://ilogin.ns.gov.my'),
    'api_path' => '/api/user',
    'verify_curl' => env('VERIFY_SSO', true),
    'guard' => 'web',
    'cache_key' => 'sso_token',
    'user_model' => \App\Models\User::class,
    'auth_fields' => "email", // Contoh: "email,nokp"
    'status_field' => null, //'is_active'
    'active_value' => null, // Nilai yang dianggap "aktif".
];
