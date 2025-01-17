<?php

namespace Mzm\Sso\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mzm\Sso\Traits\SsoService;

class SsoController extends Controller
{
    use SsoService;

    public function __invoke()
    {
        if (auth()->guard($this->guard)->check()) {

            // check check Redirect ke halaman utama
            if ($this->baseHome) {
                Log::info('auth check Redirect to ' . $this->baseHome);
                return redirect()->route($this->baseHome);
            }

            return response()->json(auth()->user());

        }

        if (!$this->token) {
            return response()->json(['error' => 'Token ' . config('sso.cache_key') . ' not found '], 401);
        }

        // Semak token dengan SSO
        $response = Http::withToken($this->token)
            ->withOptions([
                'verify' => false, // Disable SSL verification
            ])
            ->withHeaders([
                'X-Client-Origin' => "$this->clientOrigin", // Additional headers
                'X-Client-Token' => "$this->clientToken"
            ])->get($this->apiUrl .$this->apiPath);

        if ($response->failed()) {
            Log::error('SSO authentication failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $ssoUserData = $response->json();

        $user = $this->findOrCreateUser($ssoUserData['data']);

        if (!$user) {
            return response()->json(['error' => 'User not found or inactive. Mohon hubungi admin'], 404);
        }

        // Check apakah pengguna telah masuk
        $this->loginUsingUserId($user->id);

        // Redirect ke halaman utama
        if ($this->baseHome) {
            Log::info('Redirect to ' . $this->baseHome);
            return redirect()->route($this->baseHome);
        }

        return response()->json(auth()->user());
    }
}
