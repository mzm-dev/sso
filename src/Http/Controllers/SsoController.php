<?php

namespace Mzm\Sso\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsoController extends Controller
{
    public function __invoke()
    {

        $token = urldecode($_COOKIE['access_token']);
        $guard = config('sso.guard');
        $clientOrigin = config('sso.client_origin');
        $clientToken = config('sso.client_token');

        if (!$token) {
            return response()->json(['error' => 'Token ' . config('sso.cache_key') . ' not found '], 401);
        }

        // Semak token dengan SSO
        $response = Http::withToken($token)
            ->withOptions([
                'verify' => false, // Disable SSL verification
            ])
            ->withHeaders([
                'X-Client-Origin' => "$clientOrigin", // Additional headers
                'X-Client-Token' => "$clientToken"
            ])->get(config('sso.base_url') . '/api/user');

        if ($response->failed()) {
            Log::error('SSO authentication failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $ssoUserData = $response->json();

        // Model pengguna yang ditentukan
        $userModel = config('sso.user_model');
        $authFields = explode(',', config('sso.auth_fields')); // Contoh: ['email', 'no_kad_pengenalan']
        $statusField = config('sso.status_field');
        $activeValue = config('sso.active_value');

        // Membina query berdasarkan medan
        $query = $userModel::query();
        foreach ($authFields as $field) {
            if (isset($ssoUserData['data'][$field]) == false) {
                return response()->json(['error' => "Field {$field} not found in SSO response"], 422);
            }
            $query->where($field, $ssoUserData['data'][$field]);
        }

        // Tambah semakan status jika diperlukan
        if ($statusField) {
            $query->where($statusField, $activeValue);
        }

        $user = $query->first();

        if (!$user) {
            Log::error('User not found or inactive', [
                'ssoUserData' => $ssoUserData['data'],
                'authFields' => implode(', ', $authFields), // array to string
                'statusField' => $statusField,
            ]);

            return response()->json(['error' => 'User not found or inactive. Mohon hubungi admin'], 404);
        }

        // Check apakah pengguna telah masuk
        if (!auth()->guard($guard)->check()) {
            // Log masuk pengguna
            // Simulate auth()->login() with user data
            auth()->guard($guard)->loginUsingId($user['id']);
        }

        // Redirect ke halaman utama
        $baseHome = config('sso.base_home');

        if ($baseHome) {
            Log::info('Redirect to '.$baseHome);
            return redirect()->route($baseHome);
        }

        Log::info('response json');
        return response()->json(auth()->user());
    }
}
