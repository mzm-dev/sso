<?php

namespace Mzm\Sso\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SsoService
{
    public function checkAuth()
    {
        $token = Cache::get(config('sso.cache_key'));

        $clientOrigin = config('sso.client_origin');
        $clientToken = config('sso.client_token');

        if (!$token) {
            throw new \Exception('Token not found');
        }

        $response = Http::withToken($token)
            ->withHeaders([
                'X-Client-Origin' => "$clientOrigin", // Additional headers
                'X-Client-Token' => "$clientToken"
            ])->get(config('sso.base_url') . '/api/user');

        if ($response->failed()) {
            throw new \Exception('Unauthorized');
        }

        return $response->json();
    }
}
