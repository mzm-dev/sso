<?php

namespace Mzm\Sso\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
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
                Log::channel('sso_log')->info('auth check Redirect to ' . $this->baseHome);
                return redirect()->route($this->baseHome);
            }

            return response()->json(auth()->user());
        }

        if (!$this->token) {
            Log::channel('sso_log')->error('Token ' . config('sso.cache_key') . ' not found ');
            return redirect()->away($this->apiUrl.'/login?token='.urlencode($this->clientToken));
            //return response()->json(['error' => 'Token ' . config('sso.cache_key') . ' not found '], 401);
        }

        // Semak token dengan SSO
        try {
            $response = Http::withToken($this->token)
                ->withOptions([
                    'verify' => $this->verifyCurl,
                ])
                ->withHeaders([
                    'X-Client-Origin' => "$this->clientOrigin", // Additional headers
                    'X-Client-Token' => "$this->clientToken"
                ])->get($this->apiUrl . $this->apiPath);
        } catch (ConnectionException $e) {
            // Termasuk kegagalan pengesahan sijil TLS
            Log::channel('sso_log')->error('SSO connection failed: ' . $e->getMessage());
            return abort(503, 'Tidak dapat menghubungi Sistem iLogin.');
        }

        if ($response->failed() || !$response->json()) {
            Log::channel('sso_log')->error('SSO authentication failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            // return abort(401, 'Unauthorized');
            Log::channel('sso_log')->error('401 Unauthorized');
            return redirect()->away($this->apiUrl.'/login?token='.urlencode($this->clientToken));
        }


        $ssoUserData = $response->json();

        $user = $this->findUser($ssoUserData['data']);

        // Pendaftaran pengguna diuruskan oleh sistem klien
        if (!$user && $this->registrationRoute) {
            Log::channel('sso_log')->info('User not found. Redirect to ' . $this->registrationRoute);
            return redirect()->route($this->registrationRoute);
        }

        if (!$user || !$this->isActive($user)) {
            Log::channel('sso_log')->error('User not found or inactive. Mohon hubungi admin');
            return abort(404, 'User not found or inactive. Please contact admin');
        }

        // Check apakah pengguna telah masuk
        $this->loginUsingUserId($user->id);

        // Redirect ke halaman utama
        if ($this->baseHome) {
            Log::channel('sso_log')->info('Redirect to ' . $this->baseHome);
            return redirect()->route($this->baseHome);
        }
        Log::channel('sso_log')->error($this->baseHome . ' not found');
        return abort(404, 'Base Home not found. Please contact admin');
        // return response()->json(auth()->user());
    }
}
