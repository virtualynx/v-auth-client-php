# v-auth-client-php

PHP (8.2^) client for v-auth authentication system.

## Installation

### 1. Add as Git Submodule
First, add this repository as a submodule in your Laravel project (e.g: under path "app/Library/v-auth-client-php"):

```bash
git submodule add https://github.com/virtualynx/v-auth-client-php.git app/Library/v-auth-client-php
```
or
```bash
git submodule update --init --recursive
```
to re-init submodule in a repo that already include it

### 2. Autoload
Add the following configuration to your `composer.json`:

```json
{
    "files": [
        "app/Library/v-auth-client-php/helpers.php"
    ],
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/",
        "VLynx\\Sso\\": "app/Library/v-auth-client-php/"
    }
}
```

### 3. ENV Variables
Add the following configuration to your `.env`:

```json
SSO_SERVER_URL=https://yourdomain.com/v-auth/sso
SSO_CLIENT_ID=efabcd01-2db8-4a4c-babc-f67172caec83
SSO_CLIENT_SECRET=9f6b92b5-6a63-4349-8b1e-b57a7916d228
SSO_SERVER_URL_LOCAL=http://10.254.42.58:8081/v-auth/sso #this is needed for curl-ing to the sso server during auth-check
```
