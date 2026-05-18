# ChatSystem — Password reset backend

## Table of Contents

- [What Changed in Session 3.5](#what-changed-in-session-35)
- [File Contents](#file-contents)
  - [laravel-app/app/Http/Requests/User/SendResetPasswordEmailRequest.php](#laravel-appapphttprequestsuserSendResetPasswordEmailRequestphp)
  - [laravel-app/app/Http/Requests/User/SetNewPasswordRequest.php](#laravel-appapphttprequestsuserSetNewPasswordRequestphp)
  - [laravel-app/app/Notifications/ResetPasswordNotification.php](#laravel-appappnotificationsResetPasswordNotificationphp)
  - [laravel-app/app/Models/User.php](#laravel-appappmodelsuserphp)
  - [laravel-app/config/auth.php](#laravel-appconfigauthphp)
  - [laravel-app/app/Http/Controllers/API/AuthController.php](#laravel-appapphttpcontrollersapiauthcontrollerphp)
  - [laravel-app/routes/api.php](#laravel-approutesapiphp)
- [How Each File Works](#how-each-file-works)
  - [SendResetPasswordEmailRequest.php — validate send-reset-password-email request body](#sendresetpasswordemailrequestphp--validate-send-reset-password-email-request-body)
  - [SetNewPasswordRequest.php — validate set-new-password request body](#setnewpasswordrequestphp--validate-set-new-password-request-body)
  - [ResetPasswordNotification.php — build and dispatch the reset email](#resetpasswordnotificationphp--build-and-dispatch-the-reset-email)
  - [User.php — sendPasswordResetNotification override](#userphp--sendpasswordresetnotification-override)
  - [config/auth.php — password broker tuning](#configauthphp--password-broker-tuning)
  - [AuthController.php — sendResetPasswordEmail and setNewPassword actions](#authcontrollerphp--sendresetpasswordemail-and-setnewpassword-actions)
  - [routes/api.php — reset password route registration](#routesapiphp--reset-password-route-registration)
- [Common Commands](#common-commands)

---

## What Changed in Session 3.5

Session 3.4-frontend completed the email verification frontend loop — the Vue.js app can now receive the verification callback, call the signed Laravel URL, and let a user resend the verification email — but the application had no mechanism for a user to recover a forgotten password. Session 3.5 adds the full password reset flow entirely on the Laravel backend: two new `FormRequest` classes enforce input validation for each step; a new `ResetPasswordNotification` composes the reset email and embeds the frontend callback URL using the same `forwarded-url` pattern established in session 3.3; `User.php` gains a `sendPasswordResetNotification` override to inject that notification into Laravel's broker pipeline; `config/auth.php` is annotated with commented token-expiry and throttle alternatives for rapid local testing; `AuthController.php` gains `sendResetPasswordEmail` (which resolves the user, triggers the Password broker with a custom notification closure) and `setNewPassword` (which calls `Password::reset`, persists the new hash, and revokes all existing tokens); and `routes/api.php` registers the two new public routes.

| Area | Session 3.4-frontend | Session 3.5 |
|---|---|---|
| Request validation | SignupRequest, SigninRequest, SendVerificationEmailRequest | Adds `SendResetPasswordEmailRequest` (`email`, `callback_url`) and `SetNewPasswordRequest` (`token`, `email`, `password`) |
| Password reset notification | Does not exist | New `ResetPasswordNotification` builds reset URL via `route('set.new-password')` and appends it as `forwarded-url` on the `callback_url` |
| `User.php` | Has `sendEmailVerificationNotification` override | Adds `sendPasswordResetNotification($token, $callback_url)` override; imports `ResetPasswordNotification` |
| `config/auth.php` | Default `expire` (60 min) and `throttle` (60 s) | Retains defaults; adds commented `// 'expire' => 5` and `// 'throttle' => 120` for local development reference |
| `AuthController.php` | Has signup, signin, signout, verify, verifyEmail, sendVerificationEmail | Adds `sendResetPasswordEmail` and `setNewPassword`; imports `Password` facade, `SendResetPasswordEmailRequest`, `SetNewPasswordRequest` |
| `routes/api.php` | Has signup, signin, verify email, send verification email routes | Adds `POST /send/reset-password-email` and `POST /set/new-password` (named `set.new-password`) |

`laravel-app/app/Http/Requests/User/SendResetPasswordEmailRequest.php` is a new file scaffolded by `php artisan make:request` and then manually edited to fill in the validation rules. `laravel-app/app/Http/Requests/User/SetNewPasswordRequest.php` is a new file scaffolded by `php artisan make:request` and then manually edited to fill in the validation rules. `laravel-app/app/Notifications/ResetPasswordNotification.php` is a new file scaffolded by `php artisan make:notification` and then manually edited to define the constructor, the `toMail` method, and the callback-URL forwarding logic. `laravel-app/app/Models/User.php` existed from a previous session and was edited manually to import `ResetPasswordNotification` and add the `sendPasswordResetNotification` override. `laravel-app/config/auth.php` existed from a previous session and was edited manually to add the commented expire and throttle alternatives. `laravel-app/app/Http/Controllers/API/AuthController.php` existed from a previous session and was edited manually to add the two new action methods and their required imports. `laravel-app/routes/api.php` existed from a previous session and was edited manually to register the two new password reset routes.

---

## File Contents

The labels below each heading tell you what action to take:
- **Generated by command, then manually edited** — run the artisan command first, then paste the block to replace the generated body.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `laravel-app/app/Http/Requests/User/SendResetPasswordEmailRequest.php`

> **Generated by command, then manually edited** — run the command below inside the Laravel container to scaffold the class, then paste the block to replace its contents with the `email` and `callback_url` validation rules.

```bash
php artisan make:request User/SendResetPasswordEmailRequest
```

```php
<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendResetPasswordEmailRequest extends FormRequest
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
            'callback_url' => 'required|url'
        ];
    }
}
```

---

### `laravel-app/app/Http/Requests/User/SetNewPasswordRequest.php`

> **Generated by command, then manually edited** — run the command below inside the Laravel container to scaffold the class, then paste the block to replace its contents with the `token`, `email`, and `password` validation rules.

```bash
php artisan make:request User/SetNewPasswordRequest
```

```php
<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SetNewPasswordRequest extends FormRequest
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
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|max:10|confirmed'
        ];
    }
}
```

---

### `laravel-app/app/Notifications/ResetPasswordNotification.php`

> **Generated by command, then manually edited** — run the command below inside the Laravel container to scaffold the class, then paste the block to replace its contents with the constructor, properties, and `toMail` implementation.

```bash
php artisan make:notification ResetPasswordNotification
```

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;
    private $token;
    private string $callback_url;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token, string $callback_url)
    {
        $this->token = $token;
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
    public function toMail($notifiable)
    {
        $resetUrl = route('set.new-password', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('Click the button below to set your new password.')
            ->action('Set New Password', $this->callback_url . '?forwarded-url=' . urlencode($resetUrl))
            ->line('If you did not request a password reset, no further action is required.');
    }
}
```

---

### `laravel-app/app/Models/User.php`

> **Edited manually** — the file already exists from a previous session; paste the block below to add the `ResetPasswordNotification` import and the `sendPasswordResetNotification` override method.

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\ResetPasswordNotification;
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

    public function sendPasswordResetNotification($token, $callback_url = null)
    {
        $this->notify(new ResetPasswordNotification($token, $callback_url));
    }
}
```

---

### `laravel-app/config/auth.php`

> **Edited manually** — the file already exists from a previous session; paste the block below to add the commented `expire` and `throttle` alternatives inside the `users` password broker entry.

```php
<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
            // 'expire' => 5, // 5 minutes
            // 'throttle' => 120, // 2 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
```

---

### `laravel-app/app/Http/Controllers/API/AuthController.php`

> **Edited manually** — the file already exists from a previous session; paste the block below to add the `Password` facade import, the two new request class imports, and the `sendResetPasswordEmail` and `setNewPassword` action methods.

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SendResetPasswordEmailRequest;
use App\Http\Requests\User\SendVerificationEmailRequest;
use App\Http\Requests\User\SetNewPasswordRequest;
use App\Http\Requests\User\SigninRequest;
use App\Http\Requests\User\SignupRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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

    function sendResetPasswordEmail(SendResetPasswordEmailRequest $request)
    {
        $status = Password::sendResetLink(
            ['email' => $request->email],
            function ($user, $token) use ($request) {
                $user->sendPasswordResetNotification($token, $request->callback_url);
            }
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response([
                'message' => 'Password reset link sent to your email'
            ], 200);
        }

        return response([
            'message' => 'Password reset link sent to your email'
        ], 200);
    }

    function setNewPassword(SetNewPasswordRequest $request)
    {
        $status = Password::reset(
            [
                'token' => $request->token,
                'email' => $request->email,
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation
            ],
            function ($user, $password) {
                $user->password = $password;
                $user->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'password' => [__($status)],
            ]);
        }

        return response([
            'message' => 'Password has been reset successfully.'
        ], 200);
    }
}
```

---

### `laravel-app/routes/api.php`

> **Edited manually** — the file already exists from a previous session; paste the block below to add the two new password reset routes.

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
Route::post('/send/reset-password-email', [AuthController::class, 'sendResetPasswordEmail']);
Route::post('/set/new-password', [AuthController::class, 'setNewPassword'])->name('set.new-password');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/signout', [AuthController::class, 'signout']);
    Route::get('/verify', [AuthController::class, 'verify']);
});
```

---

## How Each File Works

### `SendResetPasswordEmailRequest.php` — validate send-reset-password-email request body

The class extends `FormRequest` with `authorize()` returning `true` (open to all callers). The `rules()` array requires `email` to be a valid email address that already exists in the `users` table, ensuring the controller never attempts to look up or send to a non-existent user. `callback_url` must be a well-formed URL; this is the frontend path that Laravel will embed in the reset email's action link via the same `forwarded-url` pattern used for email verification.

---

### `SetNewPasswordRequest.php` — validate set-new-password request body

`token` is a required string — it is the opaque reset token Laravel stores in `password_reset_tokens`. `email` must be a valid address that exists in `users`. `password` is required, bounded to 6–10 characters, and must pass Laravel's `confirmed` rule, meaning a `password_confirmation` field with the same value must accompany the request.

---

### `ResetPasswordNotification.php` — build and dispatch the reset email

The constructor accepts the raw reset `$token` and the frontend `$callback_url`, storing both as private properties. `via()` returns `['mail']` so only the mail channel is used.

`toMail` first calls `route('set.new-password', ['token' => ..., 'email' => ...])` to build the backend URL that corresponds to the `POST /set/new-password` route. It then constructs the email action link as `$this->callback_url . '?forwarded-url=' . urlencode($resetUrl)`, appending the backend URL as a percent-encoded query parameter — the same pattern `EmailVerificationNotification` uses for the verify-email callback. When the user clicks the button, their browser lands on the configured frontend reset-password page with the full backend URL available as `forwarded-url` for extraction.

---

### `User.php` — `sendPasswordResetNotification` override

`sendPasswordResetNotification($token, $callback_url = null)` overrides the method Laravel's `CanResetPassword` trait defines on `Authenticatable`. By overriding it here, the Password broker's `sendResetLink` closure can call `$user->sendPasswordResetNotification(...)` and receive `ResetPasswordNotification` instead of Laravel's default `ResetPassword` notification. The `$callback_url` default of `null` is not used in practice (the controller always supplies it), but keeps the signature flexible.

---

### `config/auth.php` — password broker tuning

The only change is inside the `passwords.users` array. The active `expire` (60 minutes) and `throttle` (60 seconds) values remain at their Laravel defaults. Two commented alternatives are added directly below them: `// 'expire' => 5` (5-minute tokens) and `// 'throttle' => 120` (2-minute re-request delay). These allow a developer to swap in tighter constraints during local testing by uncommenting one line each, without needing to look up the config key names or remember the units.

---

### `AuthController.php` — `sendResetPasswordEmail` and `setNewPassword` actions

**`sendResetPasswordEmail`** — accepts a `SendResetPasswordEmailRequest`, looks up the user by email, and delegates entirely to `Password::sendResetLink`. The second argument is a closure that receives the resolved `$user` and the generated `$token`; the closure calls `$user->sendPasswordResetNotification($token, $request->callback_url)` to swap Laravel's default notification for the custom one. Because `use ($request)` binds the outer request object into the closure, `$request->callback_url` is available inside it without needing to be passed as an argument. The method returns the same success message regardless of whether `Password::RESET_LINK_SENT` is returned — this avoids leaking information about whether the broker throttled the request.

**`setNewPassword`** — accepts a `SetNewPasswordRequest` and passes a credentials array (`token`, `email`, `password`, `password_confirmation`) to `Password::reset`. The closure receives the resolved `$user` and the new `$password` string (already hashed by the broker via the `User` casts); it assigns the new hash, saves the model, and calls `$user->tokens()->delete()` to revoke all existing Sanctum tokens — forcing the user to sign in again after the reset. If `Password::reset` returns anything other than `Password::PASSWORD_RESET`, a `ValidationException` is thrown with the translated broker status string so the frontend receives a 422 with a human-readable message.

---

### `routes/api.php` — reset password route registration

Two new public `Route::post` entries are added above the `auth:sanctum` group.

`POST /send/reset-password-email` maps to `AuthController@sendResetPasswordEmail`. It is intentionally unauthenticated — the user has no token yet.

`POST /set/new-password` maps to `AuthController@setNewPassword` and is given the named route `set.new-password`. This name is used by `ResetPasswordNotification::toMail` to generate the backend reset URL via `route('set.new-password', [...])`, so the route name and the `route()` call must match exactly.

---

## Common Commands

```bash
# Shell into the Laravel container
docker compose exec laravel-container bash

# Scaffold the SendResetPasswordEmailRequest class
php artisan make:request User/SendResetPasswordEmailRequest

# Scaffold the SetNewPasswordRequest class
php artisan make:request User/SetNewPasswordRequest

# Scaffold the ResetPasswordNotification class
php artisan make:notification ResetPasswordNotification

# Start all services
docker compose up --build
```
