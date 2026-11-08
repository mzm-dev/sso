# mzm/sso

iLogin single sign-on client for Laravel applications. For PHP applications without a framework, use [`mzm/ssophp`](https://github.com/mzm-dev/ssophp).

### Requirements

- PHP 8.2+
- Laravel 9+
- Composer 2
- A system registered in iLogin, with its `SSO_TOKEN` and `SSO_SECRET`

### How it works

1. The user clicks the iLogin button. `/sso/verify` sends the user to iLogin with a random `state` kept in your session.
2. The user logs in and passes 2FA on iLogin. iLogin checks that the user may use your system.
3. iLogin returns to `/sso/verify?code=...&state=...`.
4. Your server checks the `state` and exchanges the code at `POST /api/sso/token` using `SSO_TOKEN` and `SSO_SECRET`. The response contains the user's data.
5. The package finds the matching user in your database and logs them in.

The code works once and expires after 60 seconds. `SSO_SECRET` never leaves your server.

## Installation

```shell
composer require mzm/sso:^3.0
```

Publish the config and views:

```shell
php artisan vendor:publish --tag=sso-config
php artisan vendor:publish --tag=sso-views
```

Create the log folder:

```shell
mkdir -p storage/logs/sso
chmod -R 775 storage/logs/sso
chown -R www-data:www-data storage/logs/sso
```

### Registering your system in iLogin

Ask the iLogin administrator to:

- register your system with your domain and the callback `https://your-system.example/sso/verify`;
- generate the client secret, either from the system page in iLogin or with `php artisan sso:rahsia {system}`.

You receive `SSO_TOKEN` and `SSO_SECRET`. The secret is shown once only. If it is lost, ask for a new one; the old one then stops working.

### Configuration

Add to `.env`:

```shell
SSO_BASE_URL=https://your-ilogin-server
SSO_ORIGIN=https://your-system.example
SSO_TOKEN=
SSO_SECRET=
SSO_BASE_HOME=home
SSO_REGISTRATION_ROUTE=
VERIFY_SSO=true
SSO_ENABLE=true
```

| Env | Description |
| --- | --- |
| `SSO_BASE_URL` | iLogin URL, without a trailing slash. Required. |
| `SSO_ORIGIN` | Your system's URL, exactly as registered in iLogin. |
| `SSO_TOKEN` | Your system's client ID in iLogin. Required. |
| `SSO_SECRET` | Your system's client secret. When empty, the legacy cookie flow is used. |
| `SSO_BASE_HOME` | Route name to send the user to after login. |
| `SSO_REGISTRATION_ROUTE` | Route name for users who do not exist in your application (see below). |
| `VERIFY_SSO` | Verify iLogin's TLS certificate. Keep `true` in production. |
| `SSO_USER_MODEL` | Model used to find the user. Default `App\Models\User`. |
| `SSO_AUTH_FIELDS` | Comma-separated fields matched against iLogin's data. Default `email`. |
| `SSO_STATUS_FIELD`, `SSO_ACTIVE_VALUE` | When set, only users whose field equals the value can log in. |
| `SSO_ENABLE` | `true` by default. Set to `false` while iLogin is under maintenance (see below). |

Options in `config/sso.php`:

| Key | Default | Description |
| --- | --- | --- |
| `user_model` | `App\Models\User` | Model used to find the user |
| `auth_fields` | `email` | Comma-separated fields matched against iLogin's data, for example `email,nokp` |
| `user_providers` | `[]` | Where your system stores the IC number or email (see below). Replaces `user_model` and `auth_fields` when set |
| `status_field`, `active_value` | `null` | When set, only users whose field equals the value can log in |
| `guard` | `web` | Auth guard used to log in |

Run `php artisan config:clear` (or `config:cache`) after changing these values.

### Finding users by IC number (`user_providers`)

Use this when the IC number is not in a column the package expects, for example `no_kp` on `users`, or `no_kad_pengenalan` on a profile table that belongs to `User`. You set it in your own `config/sso.php`; the package code is not changed.

```shell
php artisan vendor:publish --tag=sso-config
```

```php
'user_providers' => [
    // 1. IC number on the users table
    ['model' => \App\Models\User::class, 'credential' => 'no_kp', 'field' => 'nokp', 'relation' => null],

    // 2. IC number on a profile table: StaffProfile belongsTo User through user()
    ['model' => \App\Models\StaffProfile::class, 'credential' => 'no_kad_pengenalan', 'field' => 'nokp', 'relation' => 'user'],

    // 3. Fall back to email
    ['model' => \App\Models\User::class, 'credential' => 'email', 'field' => 'email', 'relation' => null],
],
```

| Key | Description |
| --- | --- |
| `model` | Eloquent model that holds the value |
| `credential` | Column in that model |
| `field` | Key in iLogin's data to match: `nokp` (default) or `email` |
| `relation` | `belongsTo(User)` relation on that model, or `null` when the model is `User` itself |

Rows are checked in order and the first user found is logged in. IC numbers match whether your column stores `900101011234` or `900101-01-1234`. A row is skipped when iLogin sends no value for its `field`, so an empty value never matches.

### Login button

```blade
<x-sso::ssobutton />
```

### Turning SSO off during iLogin maintenance

Set `SSO_ENABLE=false` in `.env` and run `php artisan config:clear` (or `config:cache`). While it is off:

- `<x-sso::ssobutton />` is not shown. If SSO is the only way to log in to your application, add your own maintenance notice to the login page.
- `sso/auth` and `sso/verify` return 503, so bookmarked links and callbacks do not reach iLogin.
- Users who are already logged in are not affected.

If your application also has its own password login, users can keep using it. Set `SSO_ENABLE=true` (or remove it) to turn SSO back on.

If you published `config/sso.php` before 3.1.0, change its `enable` line to `'enable' => (bool) env('SSO_ENABLE', true),`. Otherwise `SSO_ENABLE` has no effect.

### User data from iLogin

| Key | Description |
| --- | --- |
| `name` | Full name |
| `email` | Email address |
| `nokp` | Identity card number |
| `no_tel_pejabat`, `no_tel_bimbit` | Phone numbers |
| `jabatan_id`, `jabatan_nama` | Department |
| `jawatan_id`, `jawatan_nama` | Position |
| `gred`, `kod_perkhidmatan` | Grade and service code |

### Users that do not exist

This package only logs in users that already exist in your application. It does not create accounts or assign roles.

Set `SSO_REGISTRATION_ROUTE` to the name of a route in your application that handles registration. That route must be accessible to guests. No user data is passed to it. Without this setting, the user receives a 404.

### Errors

| Status | Cause |
| --- | --- |
| 403 | iLogin says the user has no access to your system |
| 404 | The user does not exist in your application (and no registration route is set), or is inactive |
| 422 | A field in `auth_fields` is missing from iLogin's data |
| 500 | `SSO_BASE_URL`, `SSO_TOKEN` or `SSO_SECRET` is missing or wrong |
| 502 | Login failed three times in a row |
| 503 | iLogin cannot be reached, its TLS certificate is not trusted, or `SSO_ENABLE=false` |

Details are logged in `storage/logs/sso/`.

## Upgrading

### To 3.3.0

When `SSO_ENABLE=false`, `<x-sso::ssobutton />` is no longer shown. It used to be shown disabled with the text "Dalam penyelenggaraan". If you published the package views, run `php artisan vendor:publish --tag=sso-views --force`, or the old button stays.

### To 3.2.0

Adds `user_providers` and the `SSO_USER_MODEL`, `SSO_AUTH_FIELDS`, `SSO_STATUS_FIELD` and `SSO_ACTIVE_VALUE` environment variables. No action is needed: without `user_providers`, users are found with `user_model` and `auth_fields` as before. A published `config/sso.php` keeps its own values; copy the new keys from the package config if you want to use them.

### To 3.1.0

Adds `SSO_ENABLE`. No action is needed: SSO stays on by default. If you published `config/sso.php`, update its `enable` line as described in [Turning SSO off during iLogin maintenance](#turning-sso-off-during-ilogin-maintenance).

### To 3.0.0 (code flow)

Version 3 signs users in with a one-time code instead of reading the `access_token` cookie that iLogin shared across the parent domain.

1. Make sure your server runs PHP 8.2 or later.
2. Run `composer require mzm/sso:^3.0`.
3. Add `SSO_SECRET` to `.env` and set `SSO_BASE_URL` explicitly. Version 3 has no default iLogin URL.
4. Run `php artisan config:clear`.

Without `SSO_SECRET`, version 3 keeps using the cookie flow, so you can update the package first and add the secret later. iLogin will switch the cookie flow off once every system has moved.

Other changes:

- The session ID is regenerated after an SSO login.
- When iLogin denies access (403), the user sees a 403 page instead of being sent back to iLogin in a loop.

### To 2.0.0

- Accounts are no longer created automatically and the `roles` option is removed. Add a registration route if you relied on automatic creation.
- TLS certificates are verified by default. If you published `config/sso.php` before 2.0.0, it still defaults to `false`: set `VERIFY_SSO=true` in `.env` or publish the config again.
- Accounts created automatically by earlier versions have a password equal to the part of the email before `@`. Reset these passwords if your application has its own password login.
- When `status_field` is set, inactive users receive a 404. Remove any duplicate accounts created by earlier versions.
