# ChatSystem — Backend group chat member management API endpoints and request/resource classes

## Table of Contents

- [What Changed in Session 7.2](#what-changed-in-session-72)
- [File Contents](#file-contents)
  - [laravel-app/app/Http/Requests/Chat/GetGroupChatMembersRequest.php](#laravel-appapphttprequestschatgetgroupchatmembersrequestphp)
  - [laravel-app/app/Http/Requests/Chat/AddGroupChatMemberRequest.php](#laravel-appapphttprequestschataddgroupchatmemberrequestphp)
  - [laravel-app/app/Http/Requests/Chat/RemoveGroupChatMemberRequest.php](#laravel-appapphttprequestschatremovegroupchatmemberrequestphp)
  - [laravel-app/app/Http/Resources/Chat/ChatMemberResource.php](#laravel-appapphttpresourceschatchatmemberresourcephp)
  - [laravel-app/app/Http/Controllers/API/ChatController.php](#laravel-appapphttpcontrollersapichatcontrollerphp)
  - [laravel-app/routes/api.php](#laravel-approutesapiphp)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 7.2

Session 7.1 implemented comprehensive frontend chat components with Pinia store integration and API client functions for chat creation, reading, deletion, and updates. Session 7.2 extends the backend API with full group chat member management capabilities, adding three new controller methods to retrieve paginated chat members with optional keyword filtering, add new members to group chats with admin-only authorization, and remove members from group chats with role-based access control. Four new files are introduced: three Form Request classes (`GetGroupChatMembersRequest`, `AddGroupChatMemberRequest`, `RemoveGroupChatMemberRequest`) for request validation with pagination and authorization rules, and one Resource class (`ChatMemberResource`) for transforming ChatMember model data into consistent JSON responses. Three new API routes are registered in the chat prefix namespace to expose these member management operations as RESTful endpoints. The ChatController is expanded with three new public methods (`getGroupChatMembers`, `addGroupChatMember`, `removeGroupChatMember`) implementing comprehensive authorization checks, error handling with database transactions, and pagination support for member listing.

| Area | Session 7.1 | Session 7.2 |
|---|---|---|
| Chat member retrieval | Not implemented | `getGroupChatMembers()` with keyword search and pagination |
| Add chat member API | Not implemented | `addGroupChatMember()` with admin-only authorization |
| Remove chat member API | Not implemented | `removeGroupChatMember()` with admin-only authorization |
| ChatMember resource transformation | Not implemented | `ChatMemberResource` for JSON serialization |
| Member request validation | Not implemented | Three new Form Request classes |
| Member management routes | Not implemented | Three new routes in chat namespace |

`laravel-app/app/Http/Requests/Chat/GetGroupChatMembersRequest.php` was created manually to validate member listing requests with pagination parameters and optional keyword filtering. `laravel-app/app/Http/Requests/Chat/AddGroupChatMemberRequest.php` was created manually to validate user_id field for member addition with exists rule. `laravel-app/app/Http/Requests/Chat/RemoveGroupChatMemberRequest.php` was created manually as a Form Request class placeholder for member removal validation. `laravel-app/app/Http/Resources/Chat/ChatMemberResource.php` was created manually to transform ChatMember model instances into JSON responses including member metadata and related user data. `laravel-app/app/Http/Controllers/API/ChatController.php` was edited manually to add three new methods implementing member management logic with authorization checks and transaction support. `laravel-app/routes/api.php` was edited manually to register three new member management routes under the chat namespace.

---

## File Contents

The labels below tell you what action to take:
- **Created manually** — file does not exist and no CLI command creates it; paste the block to replace its contents.
- **Edited manually** — file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `laravel-app/app/Http/Requests/Chat/GetGroupChatMembersRequest.php`

> **Created manually** — validate member listing requests with pagination parameters and optional keyword filtering for group chat member retrieval.

```php
<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GetGroupChatMembersRequest extends FormRequest
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

### `laravel-app/app/Http/Requests/Chat/AddGroupChatMemberRequest.php`

> **Created manually** — validate user_id field for member addition with exists rule to ensure the user exists in the database.

```php
<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddGroupChatMemberRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
        ];
    }
}
```

---

### `laravel-app/app/Http/Requests/Chat/RemoveGroupChatMemberRequest.php`

> **Created manually** — Form Request class placeholder for member removal validation and future rule additions.

```php
<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RemoveGroupChatMemberRequest extends FormRequest
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
            //
        ];
    }
}
```

---

### `laravel-app/app/Http/Resources/Chat/ChatMemberResource.php`

> **Created manually** — transform ChatMember model instances into JSON responses including member ID, role, timestamps, and related user data via ChatUserResource.

```php
<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMemberResource extends JsonResource
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
            'role' => $this->role,
            'joined_at' => $this->joined_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new ChatUserResource($this->whenLoaded('user')),
        ];
    }
}
```

---

### `laravel-app/app/Http/Controllers/API/ChatController.php`

> **Edited manually** — add three new methods for group chat member management: listing members with pagination and filtering, adding new members with admin authorization, and removing members with role-based access control.

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\AddGroupChatMemberRequest;
use App\Http\Requests\Chat\CreateGroupChatRequest;
use App\Http\Requests\Chat\CreatePersonalChatRequest;
use App\Http\Requests\Chat\DeleteChatRequest;
use App\Http\Requests\Chat\GetChatsRequest;
use App\Http\Requests\Chat\GetChatUsersRequest;
use App\Http\Requests\Chat\GetGroupChatMembersRequest;
use App\Http\Requests\Chat\LeaveGroupChatRequest;
use App\Http\Requests\Chat\ReadChatRequest;
use App\Http\Requests\Chat\RemoveGroupChatMemberRequest;
use App\Http\Requests\Chat\UpdateGroupChatRequest;
use App\Http\Resources\Chat\ChatMemberResource;
use App\Http\Resources\Chat\ChatResource;
use App\Http\Resources\Chat\ChatUserResource;
use App\Models\Chat;
use App\Models\ChatMember;
use App\Models\User;
use App\Services\ImageClassService;
use DB;
use Exception;

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

    public function createPersonalChat(CreatePersonalChatRequest $request)
    {
        $user = $request->user();
        $otherUserId = $request->user_id;

        // Check if chat already exists between these two users
        $existingChat = Chat::where('type', 'personal')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('members', function ($query) use ($otherUserId) {
                $query->where('user_id', $otherUserId);
            })
            ->first();

        if ($existingChat) {
            return response([
                'message' => 'Personal chat already exists',
                'chat' => new ChatResource($existingChat->load([
                    'messages' => function ($query) {
                        $query->limit(25)
                            ->orderBy('created_at', 'asc')
                            ->with('creator');
                    },
                    'members.user'
                ]))
            ], 200);
        }

        try {
            DB::beginTransaction();

            $chat = Chat::create([
                'creator_id' => $user->id,
                'type' => 'personal',
            ]);

            // Add both members
            ChatMember::create([
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'role' => 'member',
            ]);

            ChatMember::create([
                'chat_id' => $chat->id,
                'user_id' => $otherUserId,
                'role' => 'member',
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to create chat'
            ], 500);
        }

        return response([
            'message' => 'Chat created.',
            'chat' => new ChatResource($chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'asc')
                        ->with('creator');
                },
                'members.user'
            ]))
        ], 201);
    }

    public function createGroupChat(CreateGroupChatRequest $request)
    {
        $imageClass = ImageClassService::forChatModel();
        $user = $request->user();
        $avatarPath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('avatar')) {
                $avatarPath = $imageClass->store($request->file('avatar'));
            }

            $chat = Chat::create([
                'creator_id' => $user->id,
                'type' => 'group',
                'name' => $request->name,
                'description' => $request->description,
                'avatar' => $avatarPath,
            ]);

            // Add creator as admin
            ChatMember::create([
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'role' => 'admin',
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $imageClass->delete($avatarPath);
            return response([
                'message' => 'Failed to create chat'
            ], 500);
        }

        return response([
            'message' => 'Chat created.',
            'chat' => new ChatResource($chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'asc')
                        ->with('creator');
                },
                'members.user'
            ]))
        ], 201);
    }

    public function readChat(ReadChatRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'asc')
                        ->with('creator');
                },
                'members.user',
            ])
            ->firstOrFail();

        return response([
            'chat' => new ChatResource($chat)
        ], 200);
    }

    public function deleteChat(DeleteChatRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Only group admin can delete
        $currentMember = $chat->members()->where('user_id', $user->id)->first();
        if ($chat->type === 'group' && $currentMember->role !== 'admin') {
            return response([
                'message' => 'Unauthorized to delete this chat'
            ], 403);
        }

        try {
            DB::beginTransaction();
            $chat->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to delete chat'
            ], 500);
        }

        return response([
            'message' => 'Chat deleted.'
        ], 200);
    }

    public function updateGroupChat(UpdateGroupChatRequest $request)
    {
        $imageClass = ImageClassService::forChatModel();
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Only creator or admin can update
        $currentMember = $chat->members()->where('user_id', $user->id)->first();
        if ($currentMember->role !== 'admin') {
            return response([
                'message' => 'Unauthorized to update this chat'
            ], 403);
        }

        $oldAvatarPath = $chat->getRawOriginal('avatar');
        $newAvatarPath = null;
        $shouldDeleteOldAvatar = false;

        try {
            DB::beginTransaction();

            $chat->name = $request->name;
            $chat->description = $request->description;

            // Handle avatar update logic
            if ($request->has('avatar')) {
                if ($request->hasFile('avatar')) {
                    // Avatar present with file - update and delete old
                    $newAvatarPath = $imageClass->store($request->file('avatar'));
                    $chat->avatar = $newAvatarPath;
                    $shouldDeleteOldAvatar = true;
                } else {
                    // Avatar present but null - delete avatar
                    $chat->avatar = null;
                    $shouldDeleteOldAvatar = true;
                }
            }
            // If avatar not present in request - do nothing (keep existing)

            $chat->save();

            if ($shouldDeleteOldAvatar && $oldAvatarPath) {
                $imageClass->delete($oldAvatarPath);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            if ($newAvatarPath) {
                $imageClass->delete($newAvatarPath);
            }
            return response([
                'message' => 'Failed to update chat'
            ], 500);
        }

        return response([
            'message' => 'Chat updated.',
            'chat' => new ChatResource($chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'asc')
                        ->with('creator');
                },
                'members.user'
            ]))
        ], 200);
    }

    public function leaveGroupChat(LeaveGroupChatRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // If no members left, delete the chat
            $remainingMembers = $chat->members()->count();
            if ($remainingMembers === 1) {
                $chat->delete();
            } else {
                // transfer admin role if the leaving member is an admin
                $currentMember = $chat->members()->where('user_id', $user->id)->first();
                if ($currentMember && $currentMember->role === 'admin') {
                    // Update another member to admin using update query
                    ChatMember::where('chat_id', $chat->id)
                        ->where('user_id', '!=', $user->id)
                        ->limit(1)
                        ->update(['role' => 'admin']);
                }
                // Remove the leaving member from the chat
                $chat->members()->where('user_id', $user->id)->delete();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to leave chat'
            ], 500);
        }

        return response([
            'message' => 'Left chat successfully.'
        ], 200);
    }

    public function getGroupChatMembers(GetGroupChatMembersRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $keyword = $request->input('keyword', null);
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        $members = $chat->members()
            ->when($keyword, function ($query, $keyword) {
                $query->whereHas('user', function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('role', 'asc') // Admins first // admin start with a, members with m, so asc will put admin first
            ->with('user')
            ->paginate($perPage, ['*'], 'page', $page);

        return response([
            'members' => ChatMemberResource::collection($members),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ], 200);
    }

    public function addGroupChatMember(AddGroupChatMemberRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Only group admin can add members
        $currentMember = $chat->members()->where('user_id', $user->id)->first();
        if ($currentMember->role !== 'admin') {
            return response([
                'message' => 'Unauthorized to add members to this chat'
            ], 403);
        }

        if ($request->user_id == $user->id) {
            return response([
                'message' => 'You cannot add yourself to the chat. You are already a member.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Add new member
            $member = ChatMember::firstOrCreate([
                'chat_id' => $chat->id,
                'user_id' => $request->user_id,
            ], [
                'role' => 'member',
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to add member to chat'
            ], 500);
        }
        return response([
            'message' => 'Member added successfully',
            'member' => new ChatMemberResource($member->load('user'))
        ], 200);
    }

    public function removeGroupChatMember(RemoveGroupChatMemberRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');
        $memberId = $request->route('memberId');

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Only group admin can remove members
        $currentMember = $chat->members()->where('user_id', $user->id)->first();
        if ($currentMember->role !== 'admin') {
            return response([
                'message' => 'Unauthorized to remove members from this chat'
            ], 403);
        }

        if ($memberId == $currentMember->id) {
            return response([
                'message' => 'You cannot remove yourself from the chat. Use leave chat instead.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Remove member
            $memberToRemove = ChatMember::where('chat_id', $chat->id)
                ->where('id', $memberId)
                ->firstOrFail();

            $memberToRemove->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to remove member from chat'
            ], 500);
        }
        return response([
            'message' => 'Member removed successfully'
        ], 200);
    }
}
```

---

### `laravel-app/routes/api.php`

> **Edited manually** — register three new member management routes under the chat namespace for retrieving, adding, and removing group chat members.

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
        // Chat creation and management
        Route::post('/personal/create', [ChatController::class, 'createPersonalChat']);
        Route::post('/group/create', [ChatController::class, 'createGroupChat']);
        Route::get('/read/{chatId}', [ChatController::class, 'readChat']);
        Route::delete('/delete/{chatId}', [ChatController::class, 'deleteChat']);
        Route::put('/group/update/{chatId}', [ChatController::class, 'updateGroupChat']);
        Route::delete('/group/leave/{chatId}', [ChatController::class, 'leaveGroupChat']);

        Route::get('/group/{chatId}/members', [ChatController::class, 'getGroupChatMembers']);
        Route::post('/group/{chatId}/members/add', [ChatController::class, 'addGroupChatMember']);
        Route::delete('/group/{chatId}/members/remove/{memberId}', [ChatController::class, 'removeGroupChatMember']);
    });
});
```

---

## How Each File Works

### Form Request Classes

The three new Form Request classes (`GetGroupChatMembersRequest`, `AddGroupChatMemberRequest`, `RemoveGroupChatMemberRequest`) provide automatic request validation before controller methods are invoked. `GetGroupChatMembersRequest` validates pagination parameters (`per_page` accepts 10, 25, 50, 100, or 250; `page` must be at least 1) and an optional `keyword` string up to 50 characters for filtering members by name or email. `AddGroupChatMemberRequest` validates that `user_id` is a required integer that exists in the users table, preventing invalid user references. `RemoveGroupChatMemberRequest` is a placeholder for future validation rules, allowing the route to pass through authorization checks. All three inherit from Laravel's `FormRequest` base class which automatically handles validation and returns 422 Unprocessable Entity responses with field-level error messages on validation failure.

### ChatMemberResource

`ChatMemberResource` transforms a `ChatMember` model instance into a consistent JSON response structure. It includes the member's ID, role ('admin' or 'member'), join timestamp (`joined_at`), and creation/update timestamps. The `whenLoaded()` method ensures the related user data is only included in the JSON response if the `user` relationship was eagerly loaded in the query, preventing N+1 queries. The user data is transformed via `ChatUserResource` for consistency with other chat-related responses.

### Controller Methods

**`getGroupChatMembers()`** retrieves all members of a group chat with optional filtering and pagination. It first verifies the requesting user is a member of the chat using `whereHas()` with authorization, then queries the chat's members filtered by optional keyword search (searching member user names and emails), orders members alphabetically by role (admins before members, since 'a' < 'm'), and paginates the results. The response includes a collection of `ChatMemberResource` instances and pagination metadata.

**`addGroupChatMember()`** adds a new member to a group chat. It validates that the requesting user is a group admin (non-admin members receive 403 Forbidden), prevents adding the same user twice, and uses `firstOrCreate()` with a transaction to atomically create the new member record or return the existing one. The response returns the newly added member's data via `ChatMemberResource`.

**`removeGroupChatMember()`** removes a member from a group chat. It enforces admin-only access for removal operations, prevents admins from removing themselves (directing them to use leave chat instead), queries the member to remove by ID and chat ID, deletes the member record within a transaction, and returns a success message. Error responses include validation failures and authorization denials.

### Routes

Three new routes are registered under the `/api/chats` namespace with authentication middleware:
- `GET /api/chats/group/{chatId}/members` maps to `getGroupChatMembers()` for listing members
- `POST /api/chats/group/{chatId}/members/add` maps to `addGroupChatMember()` for adding members
- `DELETE /api/chats/group/{chatId}/members/remove/{memberId}` maps to `removeGroupChatMember()` for removing members

---

## Common Commands

```bash
# Test retrieving group chat members with pagination
curl -X GET "http://localhost/api/chats/group/1/members?per_page=25&page=1" \
  -H "Authorization: Bearer $TOKEN"

# Test adding a member to a group chat
curl -X POST "http://localhost/api/chats/group/1/members/add" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 2}'

# Test removing a member from a group chat
curl -X DELETE "http://localhost/api/chats/group/1/members/remove/3" \
  -H "Authorization: Bearer $TOKEN"

# Run tests for ChatController
php artisan test tests/Feature/Chat/ChatControllerTest.php

# Run tests for Form Request validation
php artisan test tests/Feature/Chat/FormRequestTest.php
```
