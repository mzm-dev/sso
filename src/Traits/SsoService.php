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
