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
            Log::error('Token ' . config('sso.cache_key') . ' not found ');
            return redirect()->away($this->apiUrl.'?token='.urlencode($this->token));
            //return response()->json(['error' => 'Token ' . config('sso.cache_key') . ' not found '], 401);
        }

        // Semak token dengan SSO
        $response = Http::withToken($this->token)
            ->withOptions([
                'verify' => $this->verifyCurl, // Disable SSL verification
            ])
            ->withHeaders([
                'X-Client-Origin' => "$this->clientOrigin", // Additional headers
                'X-Client-Token' => "$this->clientToken"
            ])->get($this->apiUrl . $this->apiPath);

        if ($response->failed() || !$response->json()) {
            Log::error('SSO authentication failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return abort(401, 'Unauthorized');
        }


        $ssoUserData = $response->json();

        $user = $this->findOrCreateUser($ssoUserData['data']);

        if (!$user) {
            Log::error('User not found or inactive. Mohon hubungi admin');
            return abort(404, 'User not found or inactive. Please contact admin');
        }

        // Check apakah pengguna telah masuk
        $this->loginUsingUserId($user->id);

        // Redirect ke halaman utama
        if ($this->baseHome) {
            Log::info('Redirect to ' . $this->baseHome);
            return redirect()->route($this->baseHome);
        }
        Log::error($this->baseHome . ' not found');
        return abort(404, 'Base Home not found. Please contact admin');
        // return response()->json(auth()->user());
    }
}
