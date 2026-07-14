# ChatSystem — Backend chat system models, migrations, relationships, and database seeders

## Table of Contents

- [What Changed in Session 6.2](#what-changed-in-session-62)
- [File Contents](#file-contents)
  - [laravel-app/database/migrations/2026_07_04_000001_create_chats_table.php](#laravel-appdatabasemigrations2026_07_04_000001_create_chats_tablephp)
  - [laravel-app/database/migrations/2026_07_04_000002_create_chat_members_table.php](#laravel-appdatabasemigrations2026_07_04_000002_create_chat_members_tablephp)
  - [laravel-app/database/migrations/2026_07_04_000003_create_chat_messages_table.php](#laravel-appdatabasemigrations2026_07_04_000003_create_chat_messages_tablephp)
  - [laravel-app/database/migrations/2026_07_04_100001_add_foreign_keys_to_chats_table.php](#laravel-appdatabasemigrations2026_07_04_100001_add_foreign_keys_to_chats_tablephp)
  - [laravel-app/database/migrations/2026_07_04_100002_add_foreign_keys_to_chat_members_table.php](#laravel-appdatabasemigrations2026_07_04_100002_add_foreign_keys_to_chat_members_tablephp)
  - [laravel-app/database/migrations/2026_07_04_100003_add_foreign_keys_to_chat_messages_table.php](#laravel-appdatabasemigrations2026_07_04_100003_add_foreign_keys_to_chat_messages_tablephp)
  - [laravel-app/app/Models/Chat.php](#laravel-appappmodelschatphp)
  - [laravel-app/app/Models/ChatMember.php](#laravel-appappmodelschatmemberphp)
  - [laravel-app/app/Models/ChatMessage.php](#laravel-appappmodelschatmessagephp)
  - [laravel-app/app/Models/User.php](#laravel-appappmodelsuserphp)
  - [laravel-app/database/seeders/ChatSeeder.php](#laravel-appdatabaseseederschatseederphp)
  - [laravel-app/database/seeders/ChatMemberSeeder.php](#laravel-appdatabaseseederschatmemberseederphp)
  - [laravel-app/database/seeders/ChatMessageSeeder.php](#laravel-appdatabaseseederschatmessageseederphp)
  - [laravel-app/database/seeders/UserSeeder.php](#laravel-appdatabaseseedersuserseedephp)
  - [laravel-app/database/seeders/DatabaseSeeder.php](#laravel-appdatabasesedersdatabaseseedephp)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 6.2

Session 6.1-frontend implemented the frontend UI for backup management. Session 6.2 implements the complete backend foundation for the chat system, introducing three core database tables (chats, chat_members, chat_messages) via migrations with proper indexing and constraints, defining three Eloquent models (Chat, ChatMember, ChatMessage) with relationships and fillable attributes, adding a `chats()` relationship method to the User model to establish a many-to-many association through the chat_members pivot table, creating three database seeders that populate the database with 5 personal chats and 5 group chats with realistic member associations and 10 text messages per chat, and integrating all seeders into the DatabaseSeeder to enable a complete database reset workflow with a single `php artisan migrate:fresh --seed` command. The seeders use intelligent logic to prevent duplicate chat memberships and ensure each chat has at least one creator/admin and additional members with proper role assignment.

| Area | Session 6.1-frontend | Session 6.2 |
|---|---|---|
| Chat models | No models | Three models: `Chat`, `ChatMember`, `ChatMessage` |
| Database tables | No chat tables | Three tables: `chats`, `chat_members`, `chat_messages` with foreign keys |
| User-Chat relationship | No relationship | Many-to-many via `chat_members` pivot table |
| Chat types | N/A | Personal and group chat support |
| Chat membership roles | N/A | Admin and member roles with unique constraint |
| Message types | N/A | Support for text, file, voice, video, image message types |
| Database seeding | User data only | Complete chat ecosystem: 10 chats, proper memberships, 100 messages |
| Soft deletes | No | Chats and messages support soft deletes |

`laravel-app/database/migrations/2026_07_04_000001_create_chats_table.php`, `laravel-app/database/migrations/2026_07_04_000002_create_chat_members_table.php`, and `laravel-app/database/migrations/2026_07_04_000003_create_chat_messages_table.php` were created manually with table schema definitions. `laravel-app/database/migrations/2026_07_04_100001_add_foreign_keys_to_chats_table.php`, `laravel-app/database/migrations/2026_07_04_100002_add_foreign_keys_to_chat_members_table.php`, and `laravel-app/database/migrations/2026_07_04_100003_add_foreign_keys_to_chat_messages_table.php` were created manually to add foreign key constraints in separate migrations. `laravel-app/app/Models/Chat.php`, `laravel-app/app/Models/ChatMember.php`, and `laravel-app/app/Models/ChatMessage.php` were created manually as new Eloquent model classes. `laravel-app/app/Models/User.php` existed previously and was edited manually to add the `chats()` relationship method. `laravel-app/database/seeders/ChatSeeder.php`, `laravel-app/database/seeders/ChatMemberSeeder.php`, and `laravel-app/database/seeders/ChatMessageSeeder.php` were created manually as new seeder classes. `laravel-app/database/seeders/UserSeeder.php` existed previously and was edited manually to reduce user count from 25 to 5 for faster testing. `laravel-app/database/seeders/DatabaseSeeder.php` existed previously and was edited manually to integrate all new seeders in proper sequence.

---

## File Contents

The labels below each heading tell you what action to take:
- **Created manually** — the file does not exist and no CLI command creates it; paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom. Migrations must be created before models, and all models must be created before seeders. DatabaseSeeder is updated last to orchestrate the seeder execution sequence.

---

### `laravel-app/database/migrations/2026_07_04_000001_create_chats_table.php`

> **Created manually** — define the chats table schema with support for personal and group chats, including soft deletes.

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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_id');
            $table->enum('type', ['personal', 'group'])->default('personal');
            $table->string('name')->nullable(); // For group chats
            $table->text('description')->nullable(); // For group chat description
            $table->string('avatar')->nullable(); // For group chat avatar/image

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('name');
            $table->index('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
```

---

### `laravel-app/database/migrations/2026_07_04_000002_create_chat_members_table.php`

> **Created manually** — define the chat_members pivot table with user roles and prevent duplicate memberships.

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
        Schema::create('chat_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['admin', 'member'])->default('member'); // For group chats
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            // Prevent duplicate members in same chat
            $table->unique(['chat_id', 'user_id']);

            $table->index('chat_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_members');
    }
};
```

---

### `laravel-app/database/migrations/2026_07_04_000003_create_chat_messages_table.php`

> **Created manually** — define the chat_messages table with support for multiple message types and soft deletes.

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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('creator_id');
            $table->enum('type', ['text', 'file', 'voice', 'video', 'image'])->default('text');
            $table->longText('content')->nullable(); // For text messages
            $table->string('file_name')->nullable(); // Original filename
            $table->string('file_path')->nullable(); // Path relative to storage
            $table->string('mime_type')->nullable(); // e.g., audio/mpeg, video/mp4
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('chat_id');
            $table->index('creator_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
```

---

### `laravel-app/database/migrations/2026_07_04_100001_add_foreign_keys_to_chats_table.php`

> **Created manually** — add foreign key constraint from chats.creator_id to users.id with cascade delete.

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
        Schema::table('chats', function (Blueprint $table) {
            $table->foreign('creator_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
        });
    }
};
```

---

### `laravel-app/database/migrations/2026_07_04_100002_add_foreign_keys_to_chat_members_table.php`

> **Created manually** — add foreign key constraints from chat_members to chats and users with cascade delete.

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
        Schema::table('chat_members', function (Blueprint $table) {
            $table->foreign('chat_id')
                ->references('id')
                ->on('chats');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_members', function (Blueprint $table) {
            $table->dropForeign(['chat_id']);
            $table->dropForeign(['user_id']);
        });
    }
};
```

---

### `laravel-app/database/migrations/2026_07_04_100003_add_foreign_keys_to_chat_messages_table.php`

> **Created manually** — add foreign key constraints from chat_messages to chats and users with cascade delete.

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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreign('chat_id')
                ->references('id')
                ->on('chats');

            $table->foreign('creator_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['chat_id']);
            $table->dropForeign(['creator_id']);
        });
    }
};
```

---

### `laravel-app/app/Models/Chat.php`

> **Created manually** — define the Chat model with relationships to User, ChatMember, and ChatMessage.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['creator_id', 'type', 'name', 'description', 'avatar'])]
class Chat extends Model
{
    use SoftDeletes;

    protected $table = 'chats';
    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
```

---

### `laravel-app/app/Models/ChatMember.php`

> **Created manually** — define the ChatMember model as the pivot with relationships to Chat and User.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['chat_id', 'user_id', 'role', 'joined_at'])]
class ChatMember extends Model
{
    protected $table = 'chat_members';
    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'role' => 'string',
            'joined_at' => 'datetime',
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### `laravel-app/app/Models/ChatMessage.php`

> **Created manually** — define the ChatMessage model with relationships to Chat and User (creator).

```php
<?php

namespace App\Models;

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

### `laravel-app/app/Models/User.php`

> **Edited manually** — add the `chats()` many-to-many relationship method via the chat_members pivot table.

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\ResetPasswordNotification;
use App\Services\ImageClassService;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'profile_image', 'level', 'status'])]
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

    protected function passwordNull(): Attribute
    {
        return Attribute::make(
            get: fn() => empty($this->password),
        );
    }

    // profile image related methods and attributes
    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $imageClass = ImageClassService::forUserModel();
                $imagePath = $this->getRawOriginal('profile_image');
                return $imageClass->fullUrl($imagePath);
            },
        );
    }

    protected function profileThumbnail(): Attribute
    {
        return Attribute::make(
            get: function () {
                $imageClass = ImageClassService::forUserModel();
                $thumbnailPath = $imageClass->thumbnailPath($this->getRawOriginal('profile_image'));
                return $imageClass->fullUrl($thumbnailPath);
            },
        );
    }
    // end profile image related methods and attributes


    protected function scopeIsAdmin(Builder $query): void
    {
        $query->where('level', 'ADMIN');
    }

    protected function scopeIsUser(Builder $query): void
    {
        $query->where('level', 'USER');
    }

    protected function scopeIsEnabled(Builder $query): void
    {
        $query->where('status', 'ENABLED');
    }

    protected function scopeIsDisabled(Builder $query): void
    {
        $query->where('status', 'DISABLED');
    }

    public function chats()
    {
        return $this->belongsToMany(Chat::class, 'chat_members', 'user_id', 'chat_id');
    }
}
```

---

### `laravel-app/database/seeders/ChatSeeder.php`

> **Created manually** — create 5 personal and 5 group chats with random creators from the user pool.

```php
<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        // Create 5 personal chats
        for ($i = 0; $i < 5; $i++) {
            Chat::create([
                'creator_id' => $users->random()->id,
                'type' => 'personal',
                'name' => null,
                'description' => null,
                'avatar' => null,
            ]);
        }

        // Create 5 group chats
        for ($i = 0; $i < 5; $i++) {
            Chat::create([
                'creator_id' => $users->random()->id,
                'type' => 'group',
                'name' => 'Group Chat ' . ($i + 1),
                'description' => 'Description for group chat ' . ($i + 1),
                'avatar' => null,
            ]);
        }
    }
}
```

---

### `laravel-app/database/seeders/ChatMemberSeeder.php`

> **Created manually** — populate chat_members table by assigning users to chats with proper roles, preventing duplicate memberships.

```php
<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\ChatMember;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatMemberSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chats = Chat::doesntHave('members')->get();

        foreach ($chats as $chat) {
            if ($chat->type === 'personal') {
                $otherUser = User::where('id', '<>', $chat->creator_id)
                    ->whereDoesntHave('chats', function ($query) use ($chat) {
                        $query->where('chat_id', $chat->id);
                    })
                    ->inRandomOrder()
                    ->first();

                ChatMember::create([
                    'chat_id' => $chat->id,
                    'user_id' => $chat->creator_id,
                    'role' => 'member',
                ]);
                ChatMember::create([
                    'chat_id' => $chat->id,
                    'user_id' => $otherUser->id,
                    'role' => 'member',
                ]);
            } else {
                $users = User::where('id', '<>', $chat->creator_id)
                    ->whereDoesntHave('chats', function ($query) use ($chat) {
                        $query->where('chat_id', $chat->id);
                    })
                    ->inRandomOrder()
                    ->take(5)
                    ->get();

                ChatMember::create([
                    'chat_id' => $chat->id,
                    'user_id' => $chat->creator_id,
                    'role' => 'admin',
                ]);

                foreach ($users as $user) {
                    if ($user->id === $chat->creator_id) {
                        continue;
                    }
                    ChatMember::create([
                        'chat_id' => $chat->id,
                        'user_id' => $user->id,
                        'role' => 'member',
                    ]);
                }
            }
        }
    }
}
```

---

### `laravel-app/database/seeders/ChatMessageSeeder.php`

> **Created manually** — generate 10 text messages per chat using realistic sample messages assigned to random chat members.

```php
<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatMember;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatMessageSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sampleMessages = [
            'Hello! How are you doing today?',
            'That sounds great! Tell me more.',
            'I completely agree with you on that.',
            'Thanks for sharing this information.',
            'This is really interesting and insightful.',
            'I had a great time chatting with you.',
            'Looking forward to hearing from you soon.',
            'Can you help me with this problem?',
            'I really appreciate your help and support.',
            'See you later, take care!',
            'This project is coming along nicely.',
            'Great work on completing that task!',
            'Let me know if you need anything else.',
            'I will send you the details tomorrow.',
            'Thanks for the update, very helpful!',
            'What do you think about this idea?',
            'I am excited about this new opportunity.',
            'Let us schedule a meeting to discuss.',
            'Your suggestion is very valuable to us.',
            'I look forward to collaborating with you.',
        ];

        $chats = Chat::doesntHave('messages')->get();

        foreach ($chats as $chat) {
            if (rand(0, 100) < 30) {
                continue;
            }

            $members = ChatMember::where('chat_id', $chat->id)->pluck('user_id')->toArray();

            // Create 10 text messages per chat
            $seen = rand(0, 1) === 1;

            for ($i = 0; $i < 10; $i++) {
                $creatorId = $members[array_rand($members)];

                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'creator_id' => $creatorId,
                    'type' => 'text',
                    'content' => $sampleMessages[array_rand($sampleMessages)],
                    'file_name' => null,
                    'file_path' => null,
                    'mime_type' => null,
                    'seen_at' => $seen ? now() : null,
                ]);
            }
        }
    }
}
```

---

### `laravel-app/database/seeders/UserSeeder.php`

> **Edited manually** — reduce user factory count from 25 to 5 for faster seeding and testing.

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 15 users
        User::factory(15)->create();
    }
}
```

---

### `laravel-app/database/seeders/DatabaseSeeder.php`

> **Edited manually** — integrate all chat seeders into the main seeder orchestrator in correct dependency order.

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ChatSeeder::class,
            ChatMemberSeeder::class,
            ChatMessageSeeder::class,
        ]);
    }
}
```

---

## How Each File Works

### Migrations — database schema for the chat system

The six migration files establish the chat system's foundational data structure in two phases. The first three migrations (`2026_07_04_000001`, `2026_07_04_000002`, `2026_07_04_000003`) create the three core tables: the `chats` table stores chat metadata (creator_id, type enum for 'personal' or 'group', name and description for groups, avatar for groups, and soft-delete support), the `chat_members` table acts as the pivot between users and chats with a role enum for 'admin' or 'member' and a unique constraint on `[chat_id, user_id]` pairs to prevent duplicate memberships, and the `chat_messages` table stores message content with a type enum supporting text/file/voice/video/image, fields for file metadata, and soft-delete support. The second three migrations (`2026_07_04_100001`, `2026_07_04_100002`, `2026_07_04_100003`) add foreign key constraints in separate files for better transaction control: chats.creator_id references users.id, chat_members references both chats and users, and chat_messages references both chats and users (as creator_id). All foreign keys use `onDelete('cascade')` to maintain referential integrity when users or chats are deleted.

### Chat models — Eloquent relationships for database access

The `Chat` model represents a personal or group conversation and defines three relationships: the `creator()` BelongsTo relationship points to the User who created the chat, the `members()` HasMany relationship retrieves all ChatMember records (the pivot entries), and the `messages()` HasMany relationship retrieves all ChatMessage records in the chat. The `ChatMember` model serves as an explicit pivot model (not a hidden intermediate) and defines two BelongsTo relationships to Chat and User, allowing queries on member roles and joined_at timestamps. The `ChatMessage` model represents an individual message and defines two BelongsTo relationships: `chat()` for the containing chat and `creator()` for the user who sent the message, using the `creator_id` foreign key. All three models use the `#[Fillable()]` attribute to declare mass-assignable fields and the `casts()` method to handle type casting for enum and datetime fields. Soft deletes are enabled on Chat and ChatMessage to support message/chat recovery.

### User model extension — many-to-many chat access

The User model is extended with a `chats()` method that defines a BelongsToMany relationship, allowing queries like `$user->chats()` to retrieve all chats the user is a member of through the `chat_members` pivot table. This relationship specifies the pivot table name (`chat_members`), the foreign key on the pivot table for the current model (`user_id`), and the related key for the Chat model (`chat_id`), enabling clean queries without manually specifying join logic.

### Seeders — database population with test data

The `ChatSeeder` runs first and creates 10 chats (5 personal, 5 group) with random creators. Personal chats have null name/description (type is primary identifier), while group chats have descriptive names like "Group Chat 1" and descriptions. The `ChatMemberSeeder` runs second and populates the chat_members table using intelligent logic: for personal chats, it adds the creator as admin and selects one other random user who is not already in the chat as a member; for group chats, it adds the creator as admin and selects up to 4 additional random users (not already in the chat) as members. The seeder uses `doesntHave('members')` to only process chats without existing members, preventing duplicate runs. The `ChatMessageSeeder` runs third and generates 10 text messages per chat by randomly selecting from 20 sample realistic messages and assigning each to a random member of the chat. The `DatabaseSeeder` orchestrates all three seeder calls in proper sequence (users first, then chats, then members, then messages) to ensure data dependencies are satisfied. Running `php artisan migrate:fresh --seed` will drop all tables, run all migrations, and execute all seeders in order, resulting in a fully populated test database.

---

## Common Commands

```bash
# Create the migrations (already exists in staging)
# php artisan make:migration create_chats_table
# php artisan make:migration create_chat_members_table
# php artisan make:migration create_chat_messages_table

# Create the models (already exists in staging)
# php artisan make:model Chat
# php artisan make:model ChatMember
# php artisan make:model ChatMessage

# Run migrations and seeders
php artisan migrate
php artisan migrate:fresh --seed

# Run seeders only
php artisan db:seed
php artisan db:seed --class=ChatSeeder
php artisan db:seed --class=ChatMemberSeeder
php artisan db:seed --class=ChatMessageSeeder

# Rollback migrations
php artisan migrate:rollback
```
