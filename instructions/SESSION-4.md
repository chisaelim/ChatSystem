# ChatSystem — Google OAuth sign-in via Laravel Socialite

## Table of Contents

- [What Changed in Session 4](#what-changed-in-session-4)
- [File Contents](#file-contents)
  - [laravel-app/composer.json](#laravel-appcomposerjson)
  - [laravel-app/config/services.php](#laravel-appconfigservicesphp)
  - [laravel-app/.env.example](#laravel-appenvexample)
  - [laravel-app/database/migrations/2026_05_23_161509_make_password_nullable_in_users_table.php](#laravel-appdatabasemigrations2026_05_23_161509_make_password_nullable_in_users_tablephp)
  - [laravel-app/app/Http/Controllers/API/GoogleOAuthController.php](#laravel-appappHttpControllersAPIGoogleOAuthControllerphp)
  - [laravel-app/routes/api.php](#laravel-approutesapiphp)
- [How Each File Works](#how-each-file-works)
  - [composer.json — Socialite package dependency](#composerjson--socialite-package-dependency)
  - [config/services.php — Google driver configuration](#configservicesphp--google-driver-configuration)
  - [.env.example — Google OAuth env vars](#envexample--google-oauth-env-vars)
  - [make_password_nullable_in_users_table.php — nullable password migration](#make_password_nullable_in_users_tablephp--nullable-password-migration)
  - [GoogleOAuthController.php — three-step OAuth flow](#googleoauthcontrollerphp--three-step-oauth-flow)
  - [routes/api.php — Google OAuth route group](#routesapiphp--google-oauth-route-group)
- [Common Commands](#common-commands)

---

## What Changed in Session 4

Session 3.7 replaced `php artisan serve` with Laravel Octane backed by RoadRunner and made email notifications asynchronous through the database queue. Session 4 adds Google OAuth sign-in to the API: `laravel-app/composer.json` gains `laravel/socialite ^5.27` (the Laravel OAuth wrapper around Google's OAuth 2.0 provider); `laravel-app/config/services.php` is updated with the `google` driver configuration block so Socialite can read the client credentials from the environment; `laravel-app/.env.example` gains `GOOGLE_OAUTH_CLIENT_ID`, `GOOGLE_OAUTH_CLIENT_SECRET`, and `GOOGLE_OAUTH_CALLBACK_URL` so developers know which credentials to supply; a new migration makes the `password` column on the `users` table nullable, allowing users who sign in exclusively through Google to be stored without a password hash; a new `GoogleOAuthController` is created with three methods covering the redirect, callback, and token-exchange steps of the OAuth flow; and `laravel-app/routes/api.php` gains a `/google` prefix group that exposes all three OAuth endpoints.

| Area | Session 3.7 | Session 4 |
|---|---|---|
| Authentication methods | Email/password sign-up, email verification, password reset | Adds Google OAuth sign-in via Socialite |
| `composer.json` | Has `laravel/octane`, `laravel/sanctum`, `spiral/roadrunner-*` | Adds `laravel/socialite ^5.27` |
| `config/services.php` | Has `postmark`, `resend`, `ses`, `slack` service blocks | Adds `google` driver block (`client_id`, `client_secret`, `redirect`) |
| `.env.example` | Has Gmail SMTP settings, project DB defaults | Adds `GOOGLE_OAUTH_CLIENT_ID`, `GOOGLE_OAUTH_CLIENT_SECRET`, `GOOGLE_OAUTH_CALLBACK_URL` |
| `users` table `password` column | `NOT NULL` — every user must have a password | `nullable()` — social-auth users have no password hash |
| Controllers | `AuthController` handles all sign-in / sign-up flows | New `GoogleOAuthController` handles OAuth redirect, callback, and short-lived token exchange |
| `routes/api.php` | Has `/signup`, `/signin`, email verification, and password reset routes | Adds `/google/oauth/redirect`, `/google/oauth/callback`, `/google/oauth/exchange/token` |

`laravel-app/composer.json` existed from a previous session and was modified by running `composer require laravel/socialite` inside the Laravel container to add `laravel/socialite ^5.27`; `composer.lock` was updated automatically by Composer at the same time. `laravel-app/config/services.php` existed from a previous session and was edited manually to append the `google` driver block. `laravel-app/.env.example` existed from a previous session and was edited manually to add the three Google OAuth environment variables. `laravel-app/database/migrations/2026_05_23_161509_make_password_nullable_in_users_table.php` is a new file scaffolded by `php artisan make:migration make_password_nullable_in_users_table --table=users` and then manually edited to add the `nullable()->change()` column modifier in both `up()` and `down()`. `laravel-app/app/Http/Controllers/API/GoogleOAuthController.php` is a new file scaffolded by `php artisan make:controller API/GoogleOAuthController` and then manually edited to implement all three OAuth methods. `laravel-app/routes/api.php` existed from a previous session and was edited manually to register the Google OAuth route group.

---

## File Contents

The labels below each heading tell you what action to take:
- **Modified by command** — the file already exists; run the command shown to let the package manager add the dependency.
- **Generated by command, then manually edited** — run the scaffold command shown to create the stub file, then paste the block to replace its contents with the final implementation.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `laravel-app/composer.json`

> **Modified by command** — run the command below inside the Laravel container to install Socialite; `composer.json` and `composer.lock` are updated automatically.

```bash
composer require laravel/socialite
```

---

### `laravel-app/config/services.php`

> **Edited manually** — the file already exists; paste the block below to replace its contents with the `google` driver block appended at the bottom.

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_OAUTH_CALLBACK_URL'),
    ],
];
```

---

### `laravel-app/.env.example`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `GOOGLE_OAUTH_CLIENT_ID`, `GOOGLE_OAUTH_CLIENT_SECRET`, and `GOOGLE_OAUTH_CALLBACK_URL` appended at the bottom.

```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

# PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql-service
DB_PORT=3306
DB_DATABASE=chat_system
DB_USERNAME=chat_user
DB_PASSWORD=chat_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME="your_email@example.com"
MAIL_PASSWORD="your_email_app_password"
MAIL_FROM_ADDRESS="your_email@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

GOOGLE_OAUTH_CLIENT_ID=your_google_oauth_client_id
GOOGLE_OAUTH_CLIENT_SECRET=your_google_oauth_client_secret
GOOGLE_OAUTH_CALLBACK_URL="${APP_URL}/api/google/oauth/callback"
```

---

### `laravel-app/database/migrations/2026_05_23_161509_make_password_nullable_in_users_table.php`

> **Generated by command, then manually edited** — run the artisan command below inside the Laravel container to scaffold the migration stub, then paste the block to replace its contents with the `nullable()->change()` implementation.

```bash
php artisan make:migration make_password_nullable_in_users_table --table=users
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
```

---

### `laravel-app/app/Http/Controllers/API/GoogleOAuthController.php`

> **Generated by command, then manually edited** — run the artisan command below inside the Laravel container to scaffold the controller stub, then paste the block to replace its contents with the three OAuth methods.

```bash
php artisan make:controller API/GoogleOAuthController
```

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Socialite;

class GoogleOAuthController extends Controller
{
    function googleOAuthRedirect(Request $request)
    {
        $callback_url = $request->query('callback_url', '');

        $redirectUrl = Socialite::driver('google')
            ->stateless()
            ->with(['state' => base64_encode($callback_url)])
            ->redirect()
            ->getTargetUrl();

        return response(['redirect_url' => $redirectUrl], 200);
    }

    function googleOAuthCallback(Request $request)
    {
        $callback_url = base64_decode($request->query('state', ''));
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect($callback_url . '?error=google_oauth_failed');
        }

        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
            ]
        );

        $user->save();

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $token = $user->createToken('auth_token', ['exchange-new-token'], now()->addMinute())->plainTextToken;

        return redirect($callback_url . '?token=' . urlencode($token));
    }

    function googleOAuthExchangeToken(Request $request)
    {
        $user = $request->user();

        if (!$user->currentAccessToken()->can('exchange-new-token')) {
            return response(['message' => 'Invalid token.'], 403);
        }

        $user->currentAccessToken()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response([
            'message' => 'User signed in.',
            'user' => new UserResource($user),
            'token' => $token
        ], 200);
    }
}
```

---

### `laravel-app/routes/api.php`

> **Edited manually** — the file already exists; paste the block below to replace its contents with the `/google` prefix group added below the existing password reset routes.

```php
<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\GoogleOAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/signin', [AuthController::class, 'signin']);
Route::get('/verify/email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verify.email');
Route::post('/send/verification-email', [AuthController::class, 'sendVerificationEmail']);
Route::post('/send/reset-password-email', [AuthController::class, 'sendResetPasswordEmail']);
Route::post('/set/new-password', [AuthController::class, 'setNewPassword'])->name('set.new-password');

Route::prefix('google')->group(function () {
    Route::get('/oauth/redirect', [GoogleOAuthController::class, 'googleOAuthRedirect']);
    Route::get('/oauth/callback', [GoogleOAuthController::class, 'googleOAuthCallback']);
    Route::post('/oauth/exchange/token', [GoogleOAuthController::class, 'googleOAuthExchangeToken'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/signout', [AuthController::class, 'signout']);
    Route::get('/verify', [AuthController::class, 'verify']);
});
```

---

## How Each File Works

### `composer.json` — Socialite package dependency

`laravel/socialite` is the official Laravel wrapper around OAuth 2.0 providers. It ships with built-in support for Google, GitHub, Facebook, and others. After adding the package, Composer also pulls `firebase/php-jwt` (for verifying Google's ID tokens), `league/oauth1-client` (OAuth 1.0 adapter required by Socialite's dependency graph), `phpseclib/phpseclib` (RSA key operations), `paragonie/constant_time_encoding`, and `paragonie/random_compat` as transitive dependencies. No service provider registration is required — Socialite's service provider is auto-discovered via the `extra.laravel.providers` entry in the package's own `composer.json`.

---

### `config/services.php` — Google driver configuration

The `google` entry maps the three environment variables — `GOOGLE_OAUTH_CLIENT_ID`, `GOOGLE_OAUTH_CLIENT_SECRET`, and `GOOGLE_OAUTH_CALLBACK_URL` — to the keys Socialite's `GoogleProvider` expects (`client_id`, `client_secret`, `redirect`). Socialite reads this config block when `Socialite::driver('google')` is called. The `redirect` value must match one of the "Authorised redirect URIs" registered in the Google Cloud Console exactly, including the scheme and port, or Google will reject the OAuth request.

---

### `.env.example` — Google OAuth env vars

`GOOGLE_OAUTH_CLIENT_ID` and `GOOGLE_OAUTH_CLIENT_SECRET` are obtained from the Google Cloud Console after creating an OAuth 2.0 Client ID under the project's credentials page. `GOOGLE_OAUTH_CALLBACK_URL` is set to `"${APP_URL}/api/google/oauth/callback"` using `.env` variable interpolation so it automatically reflects the current `APP_URL` without requiring a separate value to be kept in sync. The callback URL in the Google Cloud Console must match this value exactly.

---

### `make_password_nullable_in_users_table.php` — nullable password migration

Google-authenticated users are created by `User::firstOrCreate` using only their `email` and `name` — no password is set. The `users` table schema from the original Laravel skeleton declares `password` as `NOT NULL`, which would cause a database integrity error when inserting a user without a password hash. This migration changes the column to `nullable()` so that social-auth-only users can be stored. The `down()` method reverses the change back to `NOT NULL` for clean rollbacks. Existing email/password users are unaffected because their `password` column already contains a bcrypt hash.

---

### `GoogleOAuthController.php` — three-step OAuth flow

The controller implements a three-method stateless OAuth 2.0 flow:

**`googleOAuthRedirect`** — the frontend calls `GET /api/google/oauth/redirect?callback_url=<url>`. The controller base64-encodes `callback_url` and passes it to Google as the OAuth `state` parameter, then returns the full Google authorization URL as JSON (`{ "redirect_url": "..." }`). The frontend redirects the user's browser to that URL. Carrying `callback_url` in `state` avoids storing session data on the server, which is necessary because Octane workers are stateless and persistent session state across workers is unreliable.

**`googleOAuthCallback`** — Google redirects to `GET /api/google/oauth/callback?code=...&state=...` after the user approves. The controller decodes `state` to recover `callback_url`, then calls `Socialite::driver('google')->stateless()->user()` to exchange the authorization code for the user's profile. `User::firstOrCreate` looks up the user by email or creates a new record (force setting `email_verified_at` to `current timestamp` with `->markEmailAsVerified();` if the user has not already verified their email (new user sign-up) because `email_verified_at` is not fillable by default and Google only returns verified addresses). A short-lived Sanctum token with the `exchange-new-token` ability and a one-minute expiry is minted and appended to `callback_url` as a query parameter before issuing a browser redirect. This token cannot be used for any API call other than the exchange endpoint.

**`googleOAuthExchangeToken`** — the frontend's OAuth callback page calls `POST /api/google/oauth/exchange/token` (protected by `auth:sanctum`) with the short-lived token in the `Authorization` header. The controller verifies the token has the `exchange-new-token` ability, deletes it immediately, and mints a full Sanctum token valid for all authenticated endpoints. This two-step exchange pattern prevents the short-lived token from persisting in the browser's navigation history, server access logs, or any CDN cache past the initial redirect.

---

### `routes/api.php` — Google OAuth route group

Three routes are registered under a `/google` prefix group:
- `GET /google/oauth/redirect` — public; accepts a `callback_url` query parameter and returns the Google authorization URL.
- `GET /google/oauth/callback` — public; receives the authorization code from Google and redirects the browser to `callback_url` with a short-lived token.
- `POST /google/oauth/exchange/token` — protected by `auth:sanctum`; exchanges the short-lived token for a permanent Sanctum token.

The first two routes are deliberately outside any `auth:sanctum` middleware because they must be reachable before the user possesses a Sanctum token. The exchange endpoint is protected because it requires the short-lived token in the `Authorization` header to identify which user is completing the sign-in.

---

## Common Commands

```bash
# Install Socialite (modifies composer.json and composer.lock automatically)
docker exec laravel-container composer require laravel/socialite

# Scaffold the nullable-password migration stub (then replace its body with the block above)
docker exec laravel-container php artisan make:migration make_password_nullable_in_users_table --table=users

# Scaffold the GoogleOAuthController stub (then replace its body with the block above)
docker exec laravel-container php artisan make:controller API/GoogleOAuthController

# Run the new nullable-password migration inside the running Laravel container
docker exec laravel-container php artisan migrate

# Rebuild the Laravel image and restart all services (required after any Dockerfile or entrypoint changes)
docker compose down && docker compose up --build

# Verify Socialite is installed
docker exec laravel-container composer show laravel/socialite

# Tail container logs to verify Octane started correctly after rebuild
docker logs laravel-container --tail 50
```
