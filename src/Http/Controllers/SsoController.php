<?php

namespace Mzm\Sso\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mzm\Sso\Traits\SsoService;

class SsoController extends Controller
{
    use SsoService;

    /** Kunci session untuk state aliran kod. */
    public const SESSION_STATE = 'sso_state';

    /** Hentikan permintaan dengan 503 jika SSO dimatikan (SSO_ENABLE=false). */
    public static function pastikanAktif(): void
    {
        if (config('sso.enable', true)) {
            return;
        }

        Log::channel('sso_log')->warning('Log masuk SSO disekat kerana SSO_ENABLE=false.');

        abort(503, 'Log masuk melalui Sistem iLogin ditutup sementara untuk penyelenggaraan. Sila cuba lagi kemudian.');
    }

    public function __invoke(Request $request)
    {
        if (auth()->guard($this->guard)->check()) {
            return $this->keHalamanUtama('auth check');
        }

        static::pastikanAktif();

        if (blank($this->apiUrl) || blank($this->clientToken)) {
            Log::channel('sso_log')->error('SSO_BASE_URL atau SSO_TOKEN tidak ditetapkan');

            return abort(500, 'Konfigurasi SSO tidak lengkap. Sila hubungi pentadbir sistem.');
        }

        // Aliran kod (disyorkan): aktif apabila SSO_SECRET ditetapkan.
        if ($this->clientSecret) {
            return $request->filled('code') ? $this->terimaKod($request) : $this->keIlogin($request);
        }

        return $this->aliranCookie();
    }

    /** Hantar pengguna ke iLogin dengan state rawak yang disimpan dalam session. */
    protected function keIlogin(Request $request)
    {
        // Elak gelung tanpa henti jika penukaran kod sentiasa gagal (cth. cache iLogin bermasalah).
        $cubaan = (int) $request->session()->get('sso_cubaan', 0) + 1;
        if ($cubaan > 3) {
            $request->session()->forget('sso_cubaan');
            Log::channel('sso_log')->error('Log masuk SSO gagal 3 kali berturut-turut.');

            return abort(502, 'Log masuk melalui iLogin gagal. Sila cuba sebentar lagi atau hubungi pentadbir.');
        }
        $request->session()->put('sso_cubaan', $cubaan);

        $state = Str::random(40);
        $request->session()->put(self::SESSION_STATE, $state);

        return redirect()->away($this->apiUrl . '/login?' . http_build_query([
            'token' => $this->clientToken,
            'state' => $state,
        ]));
    }

    /** Callback: sahkan state, tukar kod kepada data pengguna (server ke server), log masuk. */
    protected function terimaKod(Request $request)
    {
        $state = $request->session()->pull(self::SESSION_STATE);
        $stateDiterima = $request->query('state');

        if (! is_string($state) || ! is_string($stateDiterima) || ! hash_equals($state, $stateDiterima)) {
            Log::channel('sso_log')->warning('State tidak sepadan. Mulakan semula log masuk.');

            // State hilang (cth. session tamat) atau bukan dimulakan oleh pelayar ini: mula semula.
            return $this->keIlogin($request);
        }

        try {
            $response = Http::acceptJson()
                ->asForm()
                ->withOptions(['verify' => $this->verifyCurl])
                ->timeout(15)
                ->post($this->apiUrl . $this->tokenPath, [
                    'client_id' => $this->clientToken,
                    'client_secret' => $this->clientSecret,
                    'code' => (string) $request->query('code'),
                ]);
        } catch (ConnectionException $e) {
            Log::channel('sso_log')->error('SSO connection failed: ' . $e->getMessage());

            return abort(503, 'Tidak dapat menghubungi Sistem iLogin.');
        }

        if ($response->status() === 403) {
            Log::channel('sso_log')->warning('Akses ditolak oleh iLogin', ['error' => $response->json('error_description')]);

            return abort(403, $response->json('error_description') ?? 'Anda tidak mempunyai akses ke sistem ini.');
        }

        if ($response->json('error') === 'invalid_client') {
            Log::channel('sso_log')->error('SSO_TOKEN atau SSO_SECRET ditolak oleh iLogin.');

            return abort(500, 'Konfigurasi SSO tidak sah. Sila hubungi pentadbir sistem.');
        }

        if ($response->failed() || ! is_array($response->json('user'))) {
            Log::channel('sso_log')->error('SSO code exchange failed', [
                'status' => $response->status(),
                'error' => $response->json('error'),
            ]);

            // Kod tamat tempoh atau sudah digunakan (cth. butang Back): mula semula.
            return $this->keIlogin($request);
        }

        return $this->logMasuk($request, $response->json('user'));
    }

    /** Aliran lama: access_token dalam cookie domain induk, disahkan melalui /api/user. */
    protected function aliranCookie()
    {
        $request = request();

        if (! $this->token) {
            Log::channel('sso_log')->error('Token ' . config('sso.cache_key') . ' not found ');

            return redirect()->away($this->apiUrl . '/login?token=' . urlencode($this->clientToken));
        }

        try {
            $response = Http::acceptJson()
                ->withToken($this->token)
                ->withOptions([
                    'verify' => $this->verifyCurl,
                ])
                ->withHeaders([
                    'X-Client-Origin' => "$this->clientOrigin", // Additional headers
                    'X-Client-Token' => "$this->clientToken",
                ])->get($this->apiUrl . $this->apiPath);
        } catch (ConnectionException $e) {
            // Termasuk kegagalan pengesahan sijil TLS
            Log::channel('sso_log')->error('SSO connection failed: ' . $e->getMessage());

            return abort(503, 'Tidak dapat menghubungi Sistem iLogin.');
        }

        // iLogin menolak akses pengguna ke sistem ini: jangan hantar semula ke /login (gelung).
        if ($response->status() === 403) {
            Log::channel('sso_log')->warning('Akses ditolak oleh iLogin', ['error' => $response->json('message')]);

            return abort(403, $response->json('message') ?? 'Anda tidak mempunyai akses ke sistem ini.');
        }

        if ($response->failed() || ! is_array($response->json('data'))) {
            Log::channel('sso_log')->error('SSO authentication failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return redirect()->away($this->apiUrl . '/login?token=' . urlencode($this->clientToken));
        }

        return $this->logMasuk($request, $response->json('data'));
    }

    protected function logMasuk(Request $request, array $ssoUserData)
    {
        $user = $this->findUser($ssoUserData);

        // Pendaftaran pengguna diuruskan oleh sistem klien
        if (! $user && $this->registrationRoute) {
            Log::channel('sso_log')->info('User not found. Redirect to ' . $this->registrationRoute);

            return redirect()->route($this->registrationRoute);
        }

        if (! $user || ! $this->isActive($user)) {
            Log::channel('sso_log')->error('User not found or inactive. Mohon hubungi admin');

            return abort(404, 'User not found or inactive. Please contact admin');
        }

        $this->loginUsingUserId($user->id);

        // Elak session fixation selepas log masuk.
        $request->session()->forget('sso_cubaan');
        $request->session()->regenerate();

        return $this->keHalamanUtama('login');
    }

    protected function keHalamanUtama(string $sebab)
    {
        if ($this->baseHome) {
            Log::channel('sso_log')->info("{$sebab}: redirect to {$this->baseHome}");

            return redirect()->route($this->baseHome);
        }

        Log::channel('sso_log')->error('SSO_BASE_HOME not set');

        return abort(404, 'Base Home not found. Please contact admin');
    }
}
