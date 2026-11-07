# Project Title

Single Sign On client package for Laravel project

### Requirements

- Laravel 9+
- PHP 8.2+
- Composer version 2

### How it works?

User visits Agencies System and unique token is generated. When new token is generated we need to attach User session to his session in Agencies System so he will be redirected to Server and back to Agencies Syste at this moment new session in Server will be created and associated with User session in Agencies System's page. When User visits other System cross Agencies same steps will be done except that when User will be redirected to Server he already use his old session and same session id which associated with Token#1.

# Installation

### Server

Install this package using composer.

```shell
$ composer require mzm/sso
```

Copy config file to Laravel project `config/` folder.

```shell
$ php artisan vendor:publish --tag=sso-config
```

Copy views file to Laravel project `resources/views/` folder.

```shell
$ php artisan vendor:publish --tag=sso-views
```

Options in your `.env` file:

```shell
SSO_BASE_HOME=home
SSO_REGISTRATION_ROUTE=
SSO_ORIGIN=https://your-client-domain
SSO_TOKEN=
SSO_SECRET=
SSO_BASE_URL=https://ilogin.ns.gov.my
VERIFY_SSO=true
```

`SSO_TOKEN` and `SSO_SECRET` are issued by the iLogin administrator (`php artisan sso:rahsia {system}` on iLogin). The system's callback in iLogin must be `https://your-client-domain/sso/verify`.

Button

```shell
<x-sso::ssobutton />
```

### Users that do not exist

This package only logs in users that already exist in your application. It does not create accounts or assign roles.

Set `SSO_REGISTRATION_ROUTE` to the name of a route in your application to handle registration. That route must be accessible to guests. No user data is passed to it. Without this setting, the user receives a 404.

### Upgrading to 3.0.0 (code flow)

Version 3 signs users in with a one-time code instead of reading the `access_token` cookie that iLogin shares across the parent domain:

1. `/sso/verify` sends the user to iLogin with a random `state` kept in your session.
2. iLogin returns to `/sso/verify?code=...&state=...` after login and 2FA.
3. Your server checks the `state` and exchanges the code at `POST /api/sso/token` with `SSO_TOKEN` and `SSO_SECRET`. The response contains the user's data.

To switch:

- Ask the iLogin administrator for `SSO_SECRET` and add it to `.env`. Keep it on the server only.
- Run `php artisan config:clear` (or `config:cache`).

Without `SSO_SECRET`, version 3 keeps using the cookie flow, so you can update the package first and add the secret later. The cookie flow will be switched off on iLogin once every system has moved.

Other changes:

- The session ID is regenerated after an SSO login.
- When iLogin denies access to the system (403), the user sees a 403 page instead of being sent back to iLogin in a loop.

### Upgrading to 2.0.0

- Accounts are no longer created automatically and the `roles` option is removed. Add a registration route if you relied on automatic creation.
- TLS certificates are now verified by default. If you published `config/sso.php` before 2.0.0, it still defaults to `false`: set `VERIFY_SSO=true` in `.env` or publish the config again. If the iLogin certificate is not trusted by your server, requests fail with 503 until its chain is fixed.
- Accounts created automatically by earlier versions have a password equal to the part of the email before `@`. Reset these passwords if your application has its own password login.
- When `status_field` is set, inactive users receive a 404. Remove any duplicate accounts created by earlier versions.
- An SSO response without a required field now returns 422, and an unreachable iLogin returns 503, instead of 500.

### SSO Logged

Permission Folder

```shell
mkdir -p storage/logs/sso
```


permission & owner

```shell
chmod -R 775 storage/logs/sso
chown -R www-data:www-data storage/logs/sso
```
