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
    protected $baseHome;
    protected $verifyCurl;

    protected $userModel;
    protected $authFields;
    protected $statusField;
    protected $activeValue;
    protected $roles;
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
        $this->baseHome = config('sso.base_home');
        $this->verifyCurl = config('sso.verify_curl');

        $this->userModel = config('sso.user_model');

        /** Contoh: ['email', 'no_kad_pengenalan'] */
        $this->authFields = explode(',', config('sso.auth_fields'));

        $this->statusField = config('sso.status_field');
        $this->activeValue = config('sso.active_value');
        $this->roles = config('sso.roles');
        $this->guard = config('sso.guard') ?? 'web';
    }

    public function findOrCreateUser($ssoUser)
    {

        $query = $this->userModel::query();

        foreach ($this->authFields as $field) {
            if (isset($ssoUser[$field]) == false) {
                return response()->json(['error' => "Field {$field} not found in SSO response"], 422);
            }
            $query->where($field, $ssoUser[$field]);
        }

        // Tambah semakan status jika diperlukan
        if ($this->statusField) {
            $query->where($this->statusField, $this->activeValue);
        }

        $user = $query->firstOrCreate(function () use ($ssoUser) {
            return $this->userModel::create([   // Create user with these attributes if not found
                'name' => $ssoUser['name'], // Default name
                'email' => $ssoUser['email'], // Default email
                'password' => bcrypt(explode('@', $ssoUser['email'])[0]), // Default password
            ]);
        });


        if ($user)
            Log::info('SSO user success');

        if (!$user)
            Log::error('User not found or inactive');
        if (
            $user
            && $user->cretated_at == now()->day
            && isset($this->roles)
        ) {
            $user->syncRoles($this->roles);
        }

        return $user;
    }

    public function loginUsingUserId($userid)
    {
        $guard = $this->guard;

        if (!auth()->guard($guard)->check()) {

            auth()->guard($guard)->loginUsingId($userid);
        }
    }
}
