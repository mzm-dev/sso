<?php

return [
    // false: butang SSO tidak dipaparkan dan laluan sso/* memulangkan 503 (cth. semasa penyelenggaraan iLogin)
    'enable' => (bool) env('SSO_ENABLE', true),
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
    'user_model' => env('SSO_USER_MODEL', \App\Models\User::class),
    'auth_fields' => env('SSO_AUTH_FIELDS', 'email'), // Contoh: "email,nokp"

    /*
     * Carian pengguna mengikut sistem anda menyimpan No. KP atau e-mel.
     * Jika diisi, menggantikan user_model dan auth_fields di atas.
     * Setiap baris disemak mengikut turutan sehingga pengguna pertama dijumpai.
     *
     *  model      Model Eloquent yang menyimpan nilai tersebut
     *  credential Nama kolum dalam model itu (cth. no_kp, no_kad_pengenalan, email)
     *  field      Kunci data iLogin yang dipadankan: 'nokp' (lalai) atau 'email'
     *  relation   Relation belongsTo(User) pada model itu; null jika model itu sendiri ialah User
     *
     * No. KP dipadankan dalam kedua-dua format, 900101011234 dan 900101-01-1234.
     */
    'user_providers' => [
        // [
        //     'model' => \App\Models\User::class,
        //     'credential' => 'no_kp',
        //     'field' => 'nokp',
        //     'relation' => null,
        // ],
        // [
        //     'model' => \App\Models\StaffProfile::class,
        //     'credential' => 'no_kad_pengenalan',
        //     'field' => 'nokp',
        //     'relation' => 'user',
        // ],
    ],

    'status_field' => env('SSO_STATUS_FIELD'), // cth. 'is_active'
    'active_value' => env('SSO_ACTIVE_VALUE'), // Nilai yang dianggap "aktif".
];
