# v-auth-client-php

PHP client for v-auth authentication system.

## Installation

### 1. Add as Git Submodule
First, add this repository as a submodule in your Laravel project:

```bash
git submodule add https://github.com/your-repo/v-auth-client-php.git app/Library/v-auth-client-php
add this project as git-submodule (e.g: under path "app/Library/v-auth-client-php")
```
### 2. Add as Git Submodule
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
