# Project Title

Single Sign On client package for Laravel project

### Requirements

- Laravel 8+
- PHP 9.x
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

options in your `.env` file:

```shell
SSO_ENABLE=true
SSO_BASE_HOME=home
SSO_ORIGIN=
SSO_TOKEN=
SSO_BASE_URL=
SSO_GUARD=web
SSO_CACHE_KEY=
SSO_USER_MODEL=\App\Models\User
SSO_AUTH_FIELDS=email
```

Button

```shell
<x-sso::button />
```

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
