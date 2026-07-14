# ChatSystem — Backend chat API endpoints with controllers, form requests, and resource transformers

## Table of Contents

- [What Changed in Session 6.3](#what-changed-in-session-63)
- [File Contents](#file-contents)
  - [laravel-app/app/Http/Requests/Chat/GetChatsRequest.php](#laravel-appapphttprequestschatchetchatsrequestphp)
  - [laravel-app/app/Http/Requests/Chat/GetChatUsersRequest.php](#laravel-appapphttprequestschatchetchatusersrequestphp)
  - [laravel-app/app/Http/Resources/Chat/ChatUserResource.php](#laravel-appapphttpresourcescatchatuserresourcephp)
  - [laravel-app/app/Http/Resources/Chat/ChatMessageResource.php](#laravel-appapphttpresourcescatchatmessageresourcephp)
  - [laravel-app/app/Http/Resources/Chat/ChatResource.php](#laravel-appapphttpresourcescatchatresourcephp)
  - [laravel-app/app/Http/Controllers/API/ChatController.php](#laravel-appapphttpcontrollersapichatcontrollerphp)
  - [laravel-app/routes/api.php](#laravel-approutesapiphp)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 6.3

Session 6.2 implemented the backend foundation for the chat system with three core database tables, three Eloquent models, and realistic database seeders. Session 6.3 implements the complete REST API layer for chat operations, introducing two form request classes for validating incoming requests (`GetChatsRequest`, `GetChatUsersRequest`) with pagination and search parameters, three resource classes for transforming Eloquent models into JSON responses (`ChatUserResource`, `ChatMessageResource`, `ChatResource`) with intelligent avatar handling for personal chats, a `ChatController` that orchestrates two endpoints (`getChats()` and `getChatUsers()`) with advanced query optimization using eager loading and raw SQL for latest message timestamps, and route definitions for both endpoints under the `/api/chats` prefix with `auth:sanctum` middleware protection. The controller implements sophisticated filtering logic: `getChatUsers()` retrieves all users not already in a chat with the requesting user and supports keyword search by name or email with pagination; `getChats()` retrieves all chats the user is a member of, supports keyword search by chat name or member name/email, orders results by latest message timestamp, and eager-loads up to 25 recent messages per chat with creators and chat member details.

| Area | Session 6.2 | Session 6.3 |
|---|---|---|
| Chat API endpoints | No endpoints | Two endpoints: `/chats` (list user chats) and `/chats/users` (list available users) |
| Chat Controller | No controller | ChatController with getChatUsers() and getChats() methods |
| Form requests | No form requests | GetChatsRequest and GetChatUsersRequest with validation rules |
| JSON resource transformers | No resources | Three resources: ChatUserResource, ChatMessageResource, ChatResource |
| API route definitions | No chat routes | Chat routes with auth:sanctum middleware |
| User filtering logic | N/A | Excludes users already in chats, excludes requesting user, keyword search by name/email |
| Chat filtering logic | N/A | Includes only chats user is member of, keyword search by chat/member name, ordered by latest message |
| Message inclusion | N/A | Latest 25 messages per chat eager-loaded with creators |
| Personal chat display | N/A | Avatar and name automatically populated from other member's profile |
| Pagination | N/A | All list endpoints support per_page (10, 25, 50, 100, 250) and page parameters |

`laravel-app/app/Http/Requests/Chat/GetChatsRequest.php` and `laravel-app/app/Http/Requests/Chat/GetChatUsersRequest.php` were created manually as new Laravel form request classes with validation rules. `laravel-app/app/Http/Resources/Chat/ChatUserResource.php`, `laravel-app/app/Http/Resources/Chat/ChatMessageResource.php`, and `laravel-app/app/Http/Resources/Chat/ChatResource.php` were created manually as new Laravel resource classes for JSON transformation. `laravel-app/app/Http/Controllers/API/ChatController.php` was created manually as the main API controller for chat operations. `laravel-app/routes/api.php` existed previously and was edited manually to import the ChatController class and add a new `/chats` route group with two endpoints.

---

## File Contents

The labels below each heading tell you what action to take:
- **Modified by command, then manually edited** — the command changes the file first; paste the block below to reach the final state.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom. Form requests must be created before the controller that uses them. Resource classes must be created before the controller that returns them. The controller must exist before routes are added to api.php.

---

### `laravel-app/app/Http/Requests/Chat/GetChatsRequest.php`

> **Modified by command, then manually edited** — define validation rules for the GET /chats endpoint with keyword search, pagination, and per_page parameters.

```bash
# Inside the laravel container
php artisan make:request Chat/GetChatsRequest
```

```php
<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GetChatsRequest extends FormRequest
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
            'keyword' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|in:10,25,50,100,250',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
```

---

### `laravel-app/app/Http/Requests/Chat/GetChatUsersRequest.php`

> **Modified by command, then manually edited** — define validation rules for the GET /chats/users endpoint with keyword search, pagination, and per_page parameters.

```bash
# Inside the laravel container
php artisan make:request Chat/GetChatUsersRequest
```

```php
<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GetChatUsersRequest extends FormRequest
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
            'keyword' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|in:10,25,50,100,250',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
```

---

### `laravel-app/app/Http/Resources/Chat/ChatUserResource.php`

> **Modified by command, then manually edited** — transform User model into JSON response with profile image and thumbnail URLs.

```bash
# Inside the laravel container
php artisan make:resource Chat/ChatUserResource
```

```php
<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_image' => $this->profile_image,
            'profile_thumbnail' => $this->profile_thumbnail,
        ];
    }
}
```

---

### `laravel-app/app/Http/Resources/Chat/ChatMessageResource.php`

> **Modified by command, then manually edited** — transform ChatMessage model into JSON response with creator relationship using ChatUserResource.

```bash
# Inside the laravel container
php artisan make:resource Chat/ChatMessageResource
```

```php
<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'content' => $this->content,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'mime_type' => $this->mime_type,
            'seen_at' => $this->seen_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => $this->whenLoaded('creator', fn() => new ChatUserResource($this->creator)),
        ];
    }
}
```

---

### `laravel-app/app/Http/Resources/Chat/ChatResource.php`

> **Modified by command, then manually edited** — transform Chat model into JSON response with member avatars, latest messages, and intelligent personal chat display logic.

```bash
# Inside the laravel container
php artisan make:resource Chat/ChatResource
```

```php
<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        if ($this->type === 'personal') {
            $otherMember = $this->members->where('user_id', '<>', $user->id)->first();
            if ($otherMember) {
                $this->name = $otherMember->user->name;
                $this->avatar = $otherMember->user->profile_image;
                $this->avatar_thumbnail = $otherMember->user->profile_thumbnail;
            }
        }
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'avatar_thumbnail' => $this->avatar_thumbnail,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'messages' => $this->whenLoaded('messages', fn() => ChatMessageResource::collection($this->messages)),
        ];
    }
}
```

---

### `laravel-app/app/Http/Controllers/API/ChatController.php`

> **Modified by command, then manually edited** — implement getChatUsers() and getChats() methods with query optimization, eager loading, and comprehensive filtering and pagination.

```bash
# Inside the laravel container
php artisan make:controller API/ChatController
```

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\GetChatsRequest;
use App\Http\Requests\Chat\GetChatUsersRequest;
use App\Http\Resources\Chat\ChatResource;
use App\Http\Resources\Chat\ChatUserResource;
use App\Models\Chat;
use App\Models\User;

class ChatController extends Controller
{
    public function getChatUsers(GetChatUsersRequest $request)
    {
        $user = $request->user();
        $keyword = $request->input('keyword', null);
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);

        $users = User::whereNot('id', $user->id)
            ->whereDoesntHave('chats', function ($query) use ($user) {
                $query->where('type', 'personal')
                    ->whereHas('members', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
            })
            ->when($keyword, function ($query, $keyword) {
                $query
                    ->where(function ($query) use ($keyword) {
                        $query->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            })->paginate($perPage, ['*'], 'page', $page);

        return response([
            'users' => ChatUserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 200);
    }

    public function getChats(GetChatsRequest $request)
    {
        $user = $request->user();
        $keyword = $request->input('keyword', null);
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);

        $chats = Chat::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->when($keyword, function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('members.user', function ($query) use ($keyword) {
                            $query->where('name', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                        });
                });
            })

            // Use LEFT JOIN to get latest message without subquery
            ->selectRaw('chats.*,
                (SELECT MAX(created_at) FROM chat_messages WHERE chat_id = chats.id) as latest_message_at')
            ->orderByDesc('latest_message_at')
            ->orderBy('created_at', 'desc')

            // load messages with limit 25 and order by created_at asc
            ->with([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'asc')
                        ->with('creator');
                },
                'members.user',
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        return response([
            'chats' => ChatResource::collection($chats),
            'meta' => [
                'current_page' => $chats->currentPage(),
                'last_page' => $chats->lastPage(),
                'per_page' => $chats->perPage(),
                'total' => $chats->total(),
            ],
        ], 200);
    }
}
```

---

### `laravel-app/routes/api.php`

> **Edited manually** — import ChatController and add /chats route group with getChats and getChatUsers endpoints protected by auth:sanctum middleware.

```php
<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BackupController;
use App\Http\Controllers\API\GoogleOAuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ChatController;
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

Route::middleware(['auth:sanctum', 'enabled'])->group(function () {
    Route::post('/signout', [AuthController::class, 'signout']);
    Route::get('/verify', [AuthController::class, 'verify']);
    Route::put('/create/password', [AuthController::class, 'createPassword']);
    Route::put('/change/password', [AuthController::class, 'changePassword']);
    Route::put('/update/profile-image', [AuthController::class, 'updateProfileImage']);
    Route::delete('/delete/profile-image', [AuthController::class, 'deleteProfileImage']);

    Route::middleware('admin')->prefix('manage')->group(function () {
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'getUsers']);
            Route::get('/read/{id}', [UserController::class, 'readUser']);
            Route::post('/create', [UserController::class, 'createUser']);
            Route::put('/update/{id}', [UserController::class, 'updateUser']);
            Route::patch('/toggle-status/{id}', [UserController::class, 'toggleUserStatus']);
            Route::delete('/delete/{id}', [UserController::class, 'deleteUser']);
        });
        Route::prefix('backups')->group(function () {
            Route::get('/', [BackupController::class, 'getBackups']);
            Route::post('/create', [BackupController::class, 'createBackup']);
            Route::get('/download/{filename}', [BackupController::class, 'downloadBackup']);
            Route::delete('/delete/{filename}', [BackupController::class, 'deleteBackup']);
        });
    });

    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'getChats']);
        Route::get('/users', [ChatController::class, 'getChatUsers']);
    });
});
```

---

## How Each File Works

### Form requests — request validation and data extraction

The `GetChatsRequest` and `GetChatUsersRequest` classes extend Laravel's `FormRequest` base class and define validation rules for their respective endpoints. Both classes validate three optional parameters: `keyword` (nullable string, max 50 characters for search text), `per_page` (nullable integer constrained to 10, 25, 50, 100, or 250 entries per page), and `page` (nullable integer minimum 1 for pagination). The `authorize()` method returns true, allowing all authenticated users to make requests to these endpoints. When a request violates validation rules, Laravel automatically returns a 422 response with validation error details without executing the controller method.

### Resource classes — JSON transformation and nested relationships

The three resource classes transform Eloquent models into consistent JSON responses. `ChatUserResource` maps the User model fields to JSON, including the `profile_image` and `profile_thumbnail` attributes which are already formatted as full URLs by the User model's accessor methods. `ChatMessageResource` transforms ChatMessage models and uses the `whenLoaded()` method to conditionally include the creator relationship when it has been eager-loaded from the database, preventing N+1 queries; if the creator is not loaded, this field is omitted from the JSON. `ChatResource` transforms Chat models and implements intelligent display logic: for personal chats (type === 'personal'), it automatically extracts the other member's name, avatar, and thumbnail from their User profile and assigns them to the chat response, making the frontend display seamless without additional logic; for group chats, it preserves the manually-set name, avatar, and description. The messages collection is conditionally included using `whenLoaded()` and is automatically transformed using `ChatMessageResource::collection()`.

### ChatController — business logic for retrieving chats and users

The `ChatController` implements two methods that serve as the core chat list endpoints. `getChatUsers($request)` retrieves all users not already in a chat with the requesting user: it uses `whereNot('id', $user->id)` to exclude the requesting user, chains `whereDoesntHave('chats.members', ...)` to exclude users who share a chat with the requesting user (ensuring each personal chat has exactly two unique users), applies optional keyword search filtering on the name or email fields using the `when()` conditional, and returns paginated results wrapped with metadata (current_page, last_page, per_page, total). `getChats($request)` retrieves all chats the requesting user is a member of: it uses `whereHas('members', ...)` to include only chats where the user is listed in the chat_members pivot table, applies optional keyword search that searches either the chat name or member names/emails using a nested `orWhereHas()` subquery, uses a raw SQL subquery `SELECT MAX(created_at) FROM chat_messages WHERE chat_id = chats.id` to calculate the latest message timestamp in each chat without loading all messages into memory, orders results first by latest_message_at (newest first, nulls last), then by chat created_at as a tiebreaker, eager-loads up to 25 recent messages per chat along with their creators, and eager-loads all members with their associated User records. Both methods extract query parameters (`keyword`, `per_page`, `page`) from the validated request, apply defaults (keyword=null, per_page=25, page=1), and return JSON responses with a `data` key and a `meta` key containing pagination metadata.

### Routes — endpoint definitions and middleware protection

The `api.php` routes file defines two GET endpoints under the `/api/chats` prefix, both protected by `auth:sanctum` middleware (requiring a valid API token from the sanctum guard). `GET /api/chats` calls `ChatController@getChats` and returns a paginated list of chats the authenticated user is a member of. `GET /api/chats/users` calls `ChatController@getChatUsers` and returns a paginated list of users available to start new chats with. Both routes accept query parameters for filtering and pagination that are validated by their respective form request classes.

---

## Common Commands

```bash
# Test the getChatUsers endpoint
curl -H "Authorization: Bearer TOKEN" "http://localhost:8000/api/chats/users?keyword=john&per_page=25&page=1"

# Test the getChats endpoint
curl -H "Authorization: Bearer TOKEN" "http://localhost:8000/api/chats?keyword=group&per_page=25&page=1"

# Run the database seeder to populate test data
php artisan migrate:fresh --seed

# View all API routes
php artisan route:list --path=api/chats

# Run tinker to test queries interactively
php artisan tinker
```
