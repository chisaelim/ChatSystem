# ChatSystem — Backend voice message creation and file serving with request validation and file storage

## Table of Contents

- [What Changed in Session 9.0](#what-changed-in-session-90)
- [File Contents](#file-contents)
  - [laravel-app/app/Http/Requests/Chat/CreateVoiceChatMessageRequest.php](#laravel-appapphttprequestschatcreatevoicechatmessagerequestphp)
  - [laravel-app/app/Models/ChatMessage.php](#laravel-appappmodelschatmessagephp)
  - [laravel-app/app/Http/Controllers/API/ChatController.php](#laravel-appapphttpcontrollersapichatcontrollerphp)
  - [laravel-app/routes/api.php](#laravel-approutesapiphp)
- [How Each File Works](#how-each-file-works)
  - [Voice Message Request Validation](#voice-message-request-validation)
  - [File Path Attribute Accessor](#file-path-attribute-accessor)
  - [Voice Message Creation and File Serving](#voice-message-creation-and-file-serving)
  - [API Routes for Voice Messages](#api-routes-for-voice-messages)
- [Common Commands](#common-commands)

---

## What Changed in Session 9.0

Session 8.4 implemented the frontend WebSocket integration to consume real-time event broadcasts from the Laravel Reverb backend, enabling instant chat and message updates across all connected clients by installing laravel-echo and pusher-js packages, configuring Echo with custom Sanctum-aware channel authorization, and adding WebSocket event listener subscriptions in the Pinia store to sync chat and message updates in real-time. Session 9.0 implements the backend voice message functionality that allows users to record and upload voice messages to chats with file storage and retrieval, featuring a new `CreateVoiceChatMessageRequest` validation class that validates the uploaded voice file must be a webm format and not exceed 10MB, adds `createVoiceChatMessage()` controller method that stores the voice file with a UUID filename in the `chat-files/{chatId}` directory, creates a ChatMessage database record with type `voice` and file metadata, broadcasts the MessageCreated event to WebSocket subscribers, and returns the created message; adds `getChatFile()` controller method that authorizes the requesting user is a chat member and serves the file from local storage with the correct MIME type, adds a `filePath()` attribute accessor to the ChatMessage model that generates a route URL to the file endpoint instead of storing the raw file path, ensuring clients can retrieve files through the API endpoint rather than direct filesystem access, and updates the API routes to register the two new endpoints `/chats/{chatId}/messages/create-voice` (POST) and `/chats/{chatId}/files/{filename}` (GET). The session demonstrates the complete voice message workflow: validation on upload, secure file storage with UUID filenames, model attribute transformations to generate URLs, authorization checks on retrieval, and real-time event broadcasting to connected WebSocket clients.

| Area | Session 8.4 | Session 9.0 |
|---|---|---|
| Voice message support | Not implemented | CreateVoiceChatMessageRequest validation class added |
| Voice file upload | Not supported | createVoiceChatMessage() method uploads and stores files |
| File storage location | N/A | Files stored in storage/app/chat-files/{chatId}/ |
| File path handling | N/A | filePath() accessor generates route URLs instead of raw paths |
| File retrieval | Not supported | getChatFile() method serves files with authorization |
| Voice message routes | Not registered | POST /chats/{chatId}/messages/create-voice and GET /chats/{chatId}/files/{filename} added |
| Message type support | Text only | Text and voice message types supported |
| WebSocket events | Real-time listeners active | Events broadcast for voice messages too |

`laravel-app/app/Http/Requests/Chat/CreateVoiceChatMessageRequest.php` was created manually to define validation rules for voice message file uploads, requiring the `voice` form field to be present, be a file, have webm MIME type, and not exceed 10240 KB (10 MB). `laravel-app/app/Models/ChatMessage.php` was edited manually to add a `filePath()` protected attribute accessor using the Eloquent Attribute API that transforms the raw `file_path` database column into a generated route URL by calling `route('chat.file', ['chatId' => $this->chat_id, 'filename' => basename(...)])`, allowing the API to return file URLs instead of raw paths and enabling centralized file access control through the endpoint. `laravel-app/app/Http/Controllers/API/ChatController.php` was edited manually to add two new public methods: `createVoiceChatMessage()` that accepts the CreateVoiceChatMessageRequest and chatId route parameter, verifies the user is a chat member, stores the uploaded file with a UUID filename to `chat-files/{chatId}`, creates a ChatMessage record with type `voice` and file metadata, broadcasts the MessageCreated event, and returns a 201 response with the created message, and `getChatFile()` that accepts a Request, chatId, and filename, verifies the user is a chat member, confirms the file exists on disk, and returns it as a download with correct MIME type or 404 if not found. `laravel-app/routes/api.php` was edited manually to register two new routes within the chats route group: `Route::post('/{chatId}/messages/create-voice', [ChatController::class, 'createVoiceChatMessage'])` to create voice messages and `Route::get('/{chatId}/files/{filename}', [ChatController::class, 'getChatFile'])->name('chat.file')` to retrieve files and generate file URLs.

---

## File Contents

The labels below tell you what action to take:
- **Generated by command** — scaffold generator or CLI command created the file fully; command block only.
- **Generated by command, then manually edited** — CLI command created a stub; developer replaces the body with a command block, then file content block.
- **Edited manually** — file already existed; paste the file content block to replace its contents.
- **Created manually** — file does not exist and no CLI command creates it; paste the file content block only.

Follow the sections in order from top to bottom.

---

### `laravel-app/app/Http/Requests/Chat/CreateVoiceChatMessageRequest.php`

> **Created manually** — define validation rules for voice message file uploads requiring webm format and maximum file size.

```php
<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateVoiceChatMessageRequest extends FormRequest
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
            'voice' => 'required|file|mimes:webm|max:10240',
        ];
    }
}
```

---

### `laravel-app/app/Models/ChatMessage.php`

> **Edited manually** — add filePath() attribute accessor to transform raw file paths into generated route URLs for secure file access.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['chat_id', 'creator_id', 'type', 'content', 'file_name', 'file_path', 'mime_type', 'seen_at'])]
class ChatMessage extends Model
{
    use SoftDeletes;

    protected $table = 'chat_messages';
    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'seen_at' => 'datetime',
        ];
    }

    protected function filePath(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getRawOriginal('file_path') ? route('chat.file', ['chatId' => $this->chat_id, 'filename' => basename($this->getRawOriginal('file_path'))]) : null,
        );
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
```

---

### `laravel-app/app/Http/Controllers/API/ChatController.php`

> **Edited manually** — add createVoiceChatMessage() and getChatFile() methods to handle voice message uploads and file downloads with authorization and event broadcasting.

```php
<?php

namespace App\Http\Controllers\API;

use App\Events\ChatCreated;
use App\Events\ChatDeleted;
use App\Events\ChatUpdated;
use App\Events\MessageCreated;
use App\Events\MessageDeleted;
use App\Events\MessageUpdated;
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
use App\Http\Requests\Chat\CreateChatMessageRequest;
use App\Http\Requests\Chat\CreateVoiceChatMessageRequest;
use App\Http\Requests\Chat\DeleteChatMessageRequest;
use App\Http\Requests\Chat\GetChatMessagesRequest;
use App\Http\Requests\Chat\MarkAllChatMessagesAsSeenRequest;
use App\Http\Requests\Chat\UpdateChatMessageRequest;
use App\Http\Resources\Chat\ChatMessageResource;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

            // load messages with limit 25 and order by created_at desc (newest first)
            ->with([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
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
                            ->orderBy('created_at', 'desc')
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


            foreach ($chat->members as $member) {
                broadcast(new ChatCreated($chat, $member->user_id))->toOthers();
            }

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
                        ->orderBy('created_at', 'desc')
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

            foreach ($chat->members as $member) {
                broadcast(new ChatCreated($chat, $member->user_id))->toOthers();
            }

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
                        ->orderBy('created_at', 'desc')
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
                        ->orderBy('created_at', 'desc')
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

            foreach ($chat->members as $member) {
                broadcast(new ChatDeleted($chat->id, $member->user_id))->toOthers();
            }

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

            foreach ($chat->members as $member) {
                broadcast(new ChatUpdated($chat, $member->user_id))->toOthers();
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
                        ->orderBy('created_at', 'desc')
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

    public function getChatMessages(GetChatMessagesRequest $request, $chatId)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        $messages = ChatMessage::where('chat_id', $chatId)
            ->with('creator')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response([
            'chat_messages' => ChatMessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ], 200);
    }

    public function createChatMessage(CreateChatMessageRequest $request, $chatId)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        $message = ChatMessage::create([
            'chat_id' => $chatId,
            'creator_id' => $user->id,
            'type' => 'text',
            'content' => $request->input('content'),
        ]);

        broadcast(new MessageCreated($message, $chatId))->toOthers();

        return response([
            'message' => 'Message created.',
            'chat_message' => new ChatMessageResource($message->load('creator'))
        ], 201);
    }

    public function updateChatMessage(UpdateChatMessageRequest $request, $chatId, $messageId)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Find message and verify it belongs to the user
        $message = ChatMessage::where('id', $messageId)
            ->where('chat_id', $chatId)
            ->where('creator_id', $user->id)
            ->where('type', 'text') // Only allow editing text messages
            ->firstOrFail();

        $message->update([
            'content' => $request->input('content'),
        ]);

        broadcast(new MessageUpdated($message, $chatId))->toOthers();

        return response([
            'message' => 'Message updated.',
            'chat_message' => new ChatMessageResource($message->load('creator'))
        ], 200);
    }

    public function deleteChatMessage(DeleteChatMessageRequest $request, $chatId, $messageId)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Find message and verify it belongs to the user
        $message = ChatMessage::where('id', $messageId)
            ->where('chat_id', $chatId)
            ->where('creator_id', $user->id)
            ->firstOrFail();

        $message->delete();

        broadcast(new MessageDeleted($message->id, $chatId))->toOthers();

        return response([
            'message' => 'Message deleted.'
        ], 200);
    }

    public function markAllChatMessagesAsSeen(MarkAllChatMessagesAsSeenRequest $request, $chatId)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Mark all messages as seen except those created by current user
        ChatMessage::where('chat_id', $chatId)
            ->where('creator_id', '!=', $user->id)
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);

        return response([
            'message' => 'All messages marked as seen.'
        ], 200);
    }

    public function createVoiceChatMessage(CreateVoiceChatMessageRequest $request, $chatId)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        $file = $request->file('voice');
        $extension = $file->guessExtension() ?? 'webm';
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs("chat-files/{$chatId}", $filename, 'local');

        $message = ChatMessage::create([
            'chat_id' => $chatId,
            'creator_id' => $user->id,
            'type' => 'voice',
            'content' => 'Sent a voice message.',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
        ]);

        broadcast(new MessageCreated($message, $chatId))->toOthers();

        return response([
            'message' => 'Voice message created.',
            'chat_message' => new ChatMessageResource($message->load('creator'))
        ], 201);
    }

    public function getChatFile(Request $request, $chatId, $filename)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        $path = "chat-files/{$chatId}/{$filename}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
```

---

### `laravel-app/routes/api.php`

> **Edited manually** — register two new API routes for voice message creation and file retrieval endpoints.

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

        Route::get('/{chatId}/messages', [ChatController::class, 'getChatMessages']);
        Route::post('/{chatId}/messages/create', [ChatController::class, 'createChatMessage']);
        Route::patch('/{chatId}/messages/update/{messageId}', [ChatController::class, 'updateChatMessage']);
        Route::delete('/{chatId}/messages/delete/{messageId}', [ChatController::class, 'deleteChatMessage']);
        Route::post('/{chatId}/messages/seen-all', [ChatController::class, 'markAllChatMessagesAsSeen']);
        Route::post('/{chatId}/messages/create-voice', [ChatController::class, 'createVoiceChatMessage']);
        Route::get('/{chatId}/files/{filename}', [ChatController::class, 'getChatFile'])->name('chat.file');
    });
});
```

---

## How Each File Works

### Voice Message Request Validation

The `CreateVoiceChatMessageRequest` class uses Laravel's form request validation to ensure voice file uploads meet security and format requirements. It validates that a `voice` form field is present, is a file type, has the webm MIME type (the standard format for Web Audio API recordings), and does not exceed 10 MB (10240 KB). The request's `authorize()` method returns `true` to allow all authenticated users to upload voice messages—actual authorization is checked in the controller method by verifying chat membership.

### File Path Attribute Accessor

The `filePath()` accessor in ChatMessage uses Eloquent's Attribute API to transform the database value on retrieval. Instead of returning the raw filesystem path `"chat-files/123/uuid.webm"`, it generates a full API route URL by calling the `route()` helper with the `chat.file` route name and parameters `chatId` and `filename`. The getter uses `getRawOriginal()` to access the stored path without triggering the accessor recursively, extracts the filename with `basename()`, and returns `null` if no file path is stored (for text messages). This pattern keeps file access centralized at the `/api/chats/{chatId}/files/{filename}` endpoint, allowing the API to enforce authorization and serve files with correct MIME types instead of exposing raw filesystem paths to the client.

### Voice Message Creation and File Serving

The `createVoiceChatMessage()` method handles voice message creation by first verifying the user is a member of the target chat using a whereHas query. It then retrieves the uploaded file from the request, generates a UUID filename with the guessed file extension (or defaults to webm), and stores the file in the `storage/app/chat-files/{chatId}/` directory using Laravel's `storeAs()` method, which returns the relative path. A new ChatMessage record is created with type `voice`, the file metadata (original filename, storage path, and MIME type), and a placeholder content message. The MessageCreated event is broadcast to all other WebSocket subscribers, and a 201 response is returned with the created message resource.

The `getChatFile()` method serves files by verifying the requesting user is a chat member, confirming the file exists on local disk, and returning it as a streaming response with correct MIME type. If the file does not exist, `abort_unless()` triggers a 404 error. This authorization check ensures users can only access files from chats they belong to.

### API Routes for Voice Messages

Two new routes register the voice message endpoints within the chats route group under the `auth:sanctum` and `enabled` middleware stack:
- `POST /api/chats/{chatId}/messages/create-voice` dispatches to `createVoiceChatMessage()` to upload and store a new voice message.
- `GET /api/chats/{chatId}/files/{filename}` dispatches to `getChatFile()` to retrieve and serve a stored file; the route is named `chat.file` so the ChatMessage model's filePath() accessor can generate URLs to this endpoint.

---

## Common Commands

```bash
# Create a new form request class for voice message validation
php artisan make:request Chat/CreateVoiceChatMessageRequest

# Test voice message upload endpoint
curl -X POST http://localhost:8000/api/chats/1/messages/create-voice \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "voice=@path/to/recording.webm"

# Test file retrieval endpoint
curl -X GET http://localhost:8000/api/chats/1/files/uuid.webm \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o downloaded.webm
```
