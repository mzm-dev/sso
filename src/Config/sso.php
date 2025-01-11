<?php

return [
    'enable' => env('SSO_ENABLE', true),
    'base_home' => env('SSO_BASE_HOME', null), // 'home'
    'client_origin' => env('SSO_ORIGIN', ''),
    'client_token' => env('SSO_TOKEN', ''),
    'base_url' => env('SSO_BASE_URL', 'https://sso.ns.test'),
    'guard' => env('SSO_GUARD', 'web'),
    'cache_key' => env('SSO_CACHE_KEY', 'sso_token'),
    'user_model' => env('SSO_USER_MODEL', \App\Models\User::class),
    'auth_fields' => env('SSO_AUTH_FIELDS', 'email'), // Contoh: "email,no_kad_pengenalan"
    'status_field' => env('SSO_STATUS_FIELD', null),//'is_active'
    'active_value' => env('SSO_ACTIVE_VALUE', null), // Nilai yang dianggap "aktif".
];
