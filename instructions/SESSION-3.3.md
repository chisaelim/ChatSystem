# ChatSystem — Email verification backend

## Table of Contents

- [What Changed in Session 3.3](#what-changed-in-session-33)
- [File Contents](#file-contents)
  - [laravel-app/.env.example](#laravel-appenvexample)
  - [laravel-app/app/Notifications/EmailVerificationNotification.php](#laravel-appappnotificationsemailverificationnotificationphp)
  - [laravel-app/app/Models/User.php](#laravel-appappmodelsuserphp)
  - [laravel-app/app/Http/Requests/User/SendVerificationEmailRequest.php](#laravel-appapphttprequestsusersendverificationemailrequestphp)
  - [laravel-app/app/Http/Requests/User/SignupRequest.php](#laravel-appapphttprequestsuserSignupRequestphp)
  - [laravel-app/app/Http/Controllers/API/AuthController.php](#laravel-appapphttpcontrollersapiauthcontrollerphp)
  - [laravel-app/routes/api.php](#laravel-approutesapiphp)
- [How Each File Works](#how-each-file-works)
  - [.env.example — SMTP mail configuration](#envexample--smtp-mail-configuration)
  - [EmailVerificationNotification — custom mail notification](#emailverificationnotification--custom-mail-notification)
  - [User model — MustVerifyEmail trait and notification override](#user-model--mustverifyemail-trait-and-notification-override)
  - [SendVerificationEmailRequest — resend validation](#sendverificationemailrequest--resend-validation)
  - [SignupRequest — callback_url addition](#signuprequest--callback_url-addition)
  - [AuthController — verification flow](#authcontroller--verification-flow)
  - [routes/api.php — verification endpoints](#routesapiphp--verification-endpoints)
- [Common Commands](#common-commands)

---

## What Changed in Session 3.3

Session 3.2-frontend connected the Vue.js frontend to the Laravel sign-up and sign-in API with Pinia state, Sanctum token management, and a navigation guard, leaving the backend sign-up with no email dispatch and sign-in accessible to any registered user regardless of email verification status. Session 3.3 adds a complete email verification layer to the Laravel backend: a custom `EmailVerificationNotification` dispatches a five-minute signed URL to the user's inbox on registration and on demand; `User` adopts the `MustVerifyEmail` trait and overrides `sendEmailVerificationNotification` to accept a frontend callback URL; `AuthController` blocks unverified users at sign-in and exposes two new methods — `verifyEmail` and `sendVerificationEmail`; `routes/api.php` registers those methods behind a `signed` middleware and as a public POST endpoint respectively; `SignupRequest` gains a required `callback_url` field so the frontend can tell the API where to redirect after verification; and `.env.example` switches the mail driver from `log` to SMTP so real emails can be sent in development.

| Area | Session 3.2-frontend | Session 3.3 |
|---|---|---|
| Mail driver | `MAIL_MAILER=log` (writes to log file, no real delivery) | `MAIL_MAILER=smtp` with Gmail SMTP placeholders in `.env.example` |
| Email verification notification | None — no notification class; `MustVerifyEmail` interface commented out | Custom `EmailVerificationNotification` builds a 5-minute signed URL and embeds a frontend `callback_url` in the mail action |
| `User` model traits | `HasFactory, Notifiable, HasApiTokens` | Adds `MustVerifyEmail` trait; overrides `sendEmailVerificationNotification($callback_url)` |
| Sign-up flow | Creates user, returns 201 immediately | Creates user, dispatches verification email synchronously, then returns 201 |
| Sign-in flow | Any registered user can sign in | Throws 422 `email` error if `email_verified_at` is null |
| `SignupRequest` validation | `name`, `email`, `password`, `password_confirmation` | Adds `callback_url` as a required URL field |
| `SendVerificationEmailRequest` | Does not exist | New FormRequest validating `email` (must exist in `users`) and `callback_url` (required URL) |
| API routes | `POST /signup`, `POST /signin`, `POST /signout`, `GET /verify` | Adds `GET /verify/email/{id}/{hash}` (signed middleware) and `POST /send/verification-email` |
| `AuthController` methods | Four: `signup`, `signin`, `signout`, `verify` | Six: adds `verifyEmail` and `sendVerificationEmail` |

`laravel-app/.env.example` existed from a previous session and was edited manually to replace the log mail driver with Gmail SMTP configuration placeholders. `laravel-app/app/Notifications/EmailVerificationNotification.php` is a new file that did not exist before this session and was created manually. `laravel-app/app/Models/User.php` existed from a previous session and was edited manually to add the `MustVerifyEmail` trait import, the trait to the `use` line, and the custom `sendEmailVerificationNotification` method. `laravel-app/app/Http/Requests/User/SendVerificationEmailRequest.php` is a new file created manually. `laravel-app/app/Http/Requests/User/SignupRequest.php` existed from a previous session and was edited manually to add `callback_url` to the rules array. `laravel-app/app/Http/Controllers/API/AuthController.php` existed from a previous session and was edited manually to add the `SendVerificationEmailRequest` import, the email-not-verified check in `signin`, the `sendEmailVerificationNotification` call in `signup`, and the two new methods. `laravel-app/routes/api.php` existed from a previous session and was edited manually to register the two new routes.

---

## File Contents

The labels below each heading tell you what action to take:
- **Created manually** — the file does not exist yet; create it and paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `laravel-app/.env.example`

> **Edited manually** — the file already exists; paste the block below to replace its contents with the updated mail section pointing at Gmail SMTP.

```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

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
```

---

### `laravel-app/app/Notifications/EmailVerificationNotification.php`

> **Generated by command, then manually edited** — create this file at `app/Notifications/EmailVerificationNotification.php` and paste the block below to define the custom email verification notification that embeds a frontend callback URL in the signed verification link.

```bash
# Inside the laravel container
php artisan make:notification EmailVerificationNotification
```

```php
<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use URL;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    private ?string $callback_url;

    /**
     * Create a new notification instance.
     */
    public function __construct($callback_url = null)
    {
        $this->callback_url = $callback_url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationURL = $this->verificationURL($notifiable);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->line('Click the button below to verify your email address.')
            ->action('Verify Email', $this->callback_url . '?forwarded-url=' . urlencode($verificationURL))
            ->line('If you did not create an account, no further action is required.')
            ->line('This verification link will expire in 5 minutes.');
    }

    protected function verificationURL($notifiable)
    {
        return URL::temporarySignedRoute(
            'verify.email',
            Carbon::now()->addMinutes(5),
            [
                'id' => $notifiable->getKey(), // user id
                'hash' => sha1($notifiable->getEmailForVerification()), // hash using user email
            ]
        );
    }
}
```

---

### `laravel-app/app/Models/User.php`

> **Edited manually** — the file already exists from a previous session; paste the block below to add the `MustVerifyEmail` trait and the custom `sendEmailVerificationNotification` override.

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\EmailVerificationNotification;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, MustVerifyEmail;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendEmailVerificationNotification($callback_url = null)
    {
        $this->notify(new EmailVerificationNotification($callback_url));
    }
}
```

---

### `laravel-app/app/Http/Requests/User/SendVerificationEmailRequest.php`

> **Generated by command, then manually edited** — create this file at `app/Http/Requests/User/SendVerificationEmailRequest.php` and paste the block below to validate the resend-verification-email request body.

```bash
# Inside the laravel container
php artisan make:request User/SendVerificationEmailRequest
```

```php
<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendVerificationEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'callback_url' => 'required|url',
        ];
    }
}
```

---

### `laravel-app/app/Http/Requests/User/SignupRequest.php`

> **Edited manually** — the file already exists from a previous session; paste the block below to add `callback_url` as a required URL field in the validation rules.

```php
<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SignupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|max:10|confirmed',
            'callback_url' => 'required|url',
        ];
    }
}
```

---

### `laravel-app/app/Http/Controllers/API/AuthController.php`

> **Edited manually** — the file already exists from a previous session; paste the block below to add the email-not-verified check in `signin`, the `sendEmailVerificationNotification` call in `signup`, and the two new `verifyEmail` and `sendVerificationEmail` methods.

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SendVerificationEmailRequest;
use App\Http\Requests\User\SigninRequest;
use App\Http\Requests\User\SignupRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    function signup(SignupRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $user->sendEmailVerificationNotification($request->callback_url);

        return response([
            'message' => 'User signed up.',
            'user' => new UserResource($user)
        ], 201);
    }

    function signin(SigninRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is not verified.',
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password does not match.',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response([
            'message' => 'User signed in.',
            'user' => new UserResource($user),
            'token' => $token
        ], 200);
    }

    function signout(Request $request)
    {
        $user = $request->user();

        // option 1
        $user->currentAccessToken()->delete();

        // option 2
        $currentToken = $user->currentAccessToken();
        $user->tokens()->where('id', $currentToken->id)->delete();

        return response([
            'message' => 'User signed out.'
        ], 200);
    }

    function verify(Request $request)
    {
        return response([
            'message' => 'Token is valid.',
            'user' => new UserResource($request->user())
        ], 200);
    }

    function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is already verified.',
            ]);
        }

        $user->markEmailAsVerified();

        return response([
            'message' => 'Email verified successfully.'
        ], 200);
    }

    function sendVerificationEmail(SendVerificationEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification($request->callback_url);

        return response([
            'message' => 'Verification email resent.'
        ], 200);
    }
}
```

---

### `laravel-app/routes/api.php`

> **Edited manually** — the file already exists from a previous session; paste the block below to register the `verifyEmail` route behind the `signed` middleware and `sendVerificationEmail` as a public endpoint.

```php
<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/signin', [AuthController::class, 'signin']);
Route::get('/verify/email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verify.email');
Route::post('/send/verification-email', [AuthController::class, 'sendVerificationEmail']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/signout', [AuthController::class, 'signout']);
    Route::get('/verify', [AuthController::class, 'verify']);
});
```

---

## How Each File Works

### `.env.example` — SMTP mail configuration

The mail section switches from `MAIL_MAILER=log` (which writes the full email content to `storage/logs/laravel.log` without ever connecting to an external server) to `MAIL_MAILER=smtp` pointing at Gmail's SMTP server (`smtp.gmail.com:587` with TLS encryption). The `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS` fields carry placeholder strings that a developer replaces with a real Gmail address and an App Password generated from Google Account security settings. The `.env.example` file is a committed template — the actual `.env` file (not committed to version control) must be populated with real credentials before verification emails can be delivered in development.

---

### `EmailVerificationNotification` — custom mail notification

The notification constructor accepts an optional `$callback_url` string and stores it as a private nullable property. When dispatched, `via()` routes it exclusively through the `mail` channel.

`toMail()` calls the private `verificationURL()` helper, which uses `URL::temporarySignedRoute()` to generate a Laravel signed URL for the named route `verify.email`. The URL encodes the user's primary key as `id` and the SHA-1 hash of the user's email address as `hash`, and it expires five minutes from the moment of generation. The signed URL is then appended to the frontend `callback_url` as a query parameter (`?forwarded-url=`), percent-encoded with `urlencode()` so it survives any URL parsing the frontend performs. The resulting mail action button points at the frontend page, which is responsible for reading the `forwarded-url` parameter from the query string and forwarding it to the backend `GET /verify/email/{id}/{hash}` endpoint.

The class extends `Notification` without implementing `ShouldQueue`, so the email is dispatched synchronously within the same HTTP request that triggers it.

---

### `User` model — `MustVerifyEmail` trait and notification override

`MustVerifyEmail` is imported from `Illuminate\Auth\MustVerifyEmail` — the trait, not the interface at `Illuminate\Contracts\Auth\MustVerifyEmail` (which remains commented out). Adding the trait to the `use` line equips the model with three methods: `hasVerifiedEmail()` (returns `true` when `email_verified_at` is non-null), `markEmailAsVerified()` (stamps `email_verified_at` with the current timestamp and saves), and the default `sendEmailVerificationNotification()` which is immediately overridden.

The custom `sendEmailVerificationNotification($callback_url = null)` replaces Laravel's built-in implementation — which sends a plain signed URL pointing at the Laravel app's own URL — with a dispatch of `EmailVerificationNotification`, passing `$callback_url` through so the notification can embed the frontend page address in the mail action link.

---

### `SendVerificationEmailRequest` — resend validation

Two rules are enforced: `email` must be a valid email address that already exists in the `users` table (ensuring the API only attempts to resend to registered accounts and surfaces a meaningful 422 error for unknown addresses), and `callback_url` must be a valid URL (forwarded unchanged to `sendEmailVerificationNotification` so the new email's action button points at the correct frontend page). `authorize()` returns `true` unconditionally — no Sanctum token is required to request a resend, since the user may not yet be able to sign in.

---

### `SignupRequest` — `callback_url` addition

A single rule is added: `callback_url` as `required|url`. This value is forwarded by `AuthController::signup` to `sendEmailVerificationNotification` so the verification email links back to the frontend page that will call the `verifyEmail` API endpoint. Every sign-up request from the frontend must now include this field alongside `name`, `email`, `password`, and `password_confirmation`.

---

### `AuthController` — verification flow

The controller gains changes to two existing methods and two entirely new methods:

**`signup`** — after creating the user record, calls `$user->sendEmailVerificationNotification($request->callback_url)`. The notification is dispatched synchronously before the 201 response is returned, so the user receives the verification email as part of the same request cycle.

**`signin`** — inserts an early `hasVerifiedEmail()` check before the password comparison. If the user's `email_verified_at` column is `null`, a `ValidationException` is thrown with an `email` key and a 422 status. The frontend's existing 422-handling code in `Signin.vue` surfaces this as an inline field error without any additional changes.

**`verifyEmail`** — finds the user by the `id` route parameter using `findOrFail`, then guards against double-verification with `hasVerifiedEmail()`. If not yet verified, calls `markEmailAsVerified()`, which stamps `email_verified_at` with the current timestamp. The route for this method is protected by the `signed` middleware, so Laravel validates the `signature` query parameter automatically before the method body runs — no manual signature check is needed inside the method.

**`sendVerificationEmail`** — looks up the user by `email` (already guaranteed to exist in `users` by `SendVerificationEmailRequest`), checks whether the email is already verified, and re-dispatches `sendEmailVerificationNotification` with the supplied `callback_url`. Returns a 200 response on success.

---

### `routes/api.php` — verification endpoints

Two routes are added outside the Sanctum-protected group because neither requires an authenticated user:

| Method | Path | Controller method | Notes |
|---|---|---|---|
| `GET` | `/verify/email/{id}/{hash}` | `verifyEmail` | `->middleware('signed')` rejects requests with a missing or invalid `signature`; `->name('verify.email')` matches the name used in `URL::temporarySignedRoute()` inside the notification |
| `POST` | `/send/verification-email` | `sendVerificationEmail` | Publicly accessible — no middleware — so a user whose link expired can request a new one without a Sanctum token |

The existing four routes (`/signup`, `/signin`, and the Sanctum group containing `/signout` and `/verify`) are unchanged.

---

## Common Commands

```bash
# Shell into the Laravel container
docker compose exec laravel-container bash

# Clear application config and route caches after updating .env
php artisan config:clear
php artisan route:clear

# Start all services
docker compose up --build
```
