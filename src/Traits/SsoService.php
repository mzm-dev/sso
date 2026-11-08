<?php

namespace Mzm\Sso\Traits;

use Illuminate\Support\Facades\Log;
use Mzm\Sso\Enums\TokenAbility;

trait SsoService
{

    protected $tokenName;
    protected $token;
    protected $apiUrl;
    protected $apiPath;
    protected $clientOrigin;
    protected $clientToken;
    protected $clientSecret;
    protected $tokenPath;
    protected $baseHome;
    protected $registrationRoute;
    protected $verifyCurl;

    protected $userModel;
    protected $authFields;
    protected $statusField;
    protected $activeValue;
    protected $guard;

    public function __construct()
    {

        if (isset($_COOKIE[TokenAbility::ACCESS_TOKEN->value]))
            $this->tokenName = TokenAbility::ACCESS_TOKEN->value;
        elseif (isset($_COOKIE[TokenAbility::REFRESH_ACCESS_TOKEN->value]))
            $this->tokenName = TokenAbility::REFRESH_ACCESS_TOKEN->value;
        else
            $this->tokenName = null;

        $this->token = $this->tokenName ? urldecode($_COOKIE[$this->tokenName]) : null;

        $this->apiUrl = config('sso.api_url');
        $this->apiPath = config('sso.api_path');
        $this->clientOrigin = config('sso.client_origin');
        $this->clientToken = config('sso.client_token');
        $this->clientSecret = config('sso.client_secret');
        $this->tokenPath = config('sso.token_path') ?: '/api/sso/token';
        $this->baseHome = config('sso.base_home');
        $this->registrationRoute = config('sso.registration_route');
        $this->verifyCurl = config('sso.verify_curl');

        $this->userModel = config('sso.user_model');

        /** Contoh: ['email', 'no_kad_pengenalan'] */
        $this->authFields = explode(',', config('sso.auth_fields'));

        $this->statusField = config('sso.status_field');
        $this->activeValue = config('sso.active_value');
        $this->guard = config('sso.guard') ?? 'web';
    }

    public function findUser($ssoUser)
    {
        $providers = config('sso.user_providers');

        if (is_array($providers) && $providers !== []) {
            return $this->findUserFromProviders($providers, (array) $ssoUser);
        }

        $query = $this->userModel::query();

        foreach ($this->authFields as $field) {
            if (blank($ssoUser[$field] ?? null)) {
                Log::channel('sso_log')->error("Field {$field} not found in SSO response");
                abort(422, "Field {$field} not found in SSO response");
            }
            $query->where($field, $ssoUser[$field]);
        }

        return $query->first();
    }

    /**
     * Semak setiap baris user_providers mengikut turutan dan pulangkan User pertama yang dijumpai.
     */
    protected function findUserFromProviders(array $providers, array $ssoUser)
    {
        foreach (array_values($providers) as $i => $provider) {
            $model = $provider['model'] ?? null;
            $column = $provider['credential'] ?? null;
            $field = $provider['field'] ?? 'nokp';
            $relation = $provider['relation'] ?? null;

            if (! is_string($model) || ! class_exists($model) || blank($column)) {
                Log::channel('sso_log')->error("sso.user_providers[{$i}] tidak sah: 'model' mesti kelas yang wujud dan 'credential' mesti diisi.");
                abort(500, 'Konfigurasi SSO tidak lengkap. Sila hubungi pentadbir sistem.');
            }

            $nilai = $this->nilaiCarian($field, $ssoUser[$field] ?? null);

            // Tanpa nilai, carian menjadi "IS NULL" dan boleh memadankan pengguna lain.
            if ($nilai === []) {
                Log::channel('sso_log')->warning("Field {$field} tiada dalam data iLogin. sso.user_providers[{$i}] dilangkau.");

                continue;
            }

            $rekod = $model::query()->whereIn($column, $nilai)->first();

            if (! $rekod) {
                continue;
            }

            $user = $relation ? $rekod->{$relation} : $rekod;

            if ($user) {
                Log::channel('sso_log')->info("Pengguna dijumpai melalui {$model}.{$column}");

                return $user;
            }

            Log::channel('sso_log')->warning("Rekod {$model} dijumpai tetapi relation '{$relation}' kosong.");
        }

        return null;
    }

    /**
     * Nilai yang dicari dalam pangkalan data. No. KP dicari dalam format digit dan bersempang.
     *
     * @return list<string>
     */
    protected function nilaiCarian(string $field, $nilai): array
    {
        if (! is_scalar($nilai) || trim((string) $nilai) === '') {
            return [];
        }

        $nilai = trim((string) $nilai);

        if ($field !== 'nokp') {
            return [$nilai];
        }

        $digit = preg_replace('/\D/', '', $nilai);
        $calon = [$nilai, $digit];

        if (strlen($digit) === 12) {
            $calon[] = substr($digit, 0, 6) . '-' . substr($digit, 6, 2) . '-' . substr($digit, 8);
        }

        return array_values(array_unique(array_filter($calon, fn ($v) => $v !== '')));
    }

    public function isActive($user)
    {
        return !$this->statusField || $user->{$this->statusField} == $this->activeValue;
    }

    public function loginUsingUserId($userid)
    {
        $guard = $this->guard;

        if (!auth()->guard($guard)->check()) {

            auth()->guard($guard)->loginUsingId($userid);
        }
    }
}
