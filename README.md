# v-auth-client-php

PHP client for v-auth authentication system.

## Installation

To include this package in your Laravel project, add the following configuration to your `composer.json`:

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
