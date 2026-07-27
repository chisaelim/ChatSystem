# ChatSystem — Laravel Reverb WebSocket integration with real-time event broadcasting for chat and message updates

## Table of Contents

- [What Changed in Session 8.3](#what-changed-in-session-83)
- [File Contents](#file-contents)
  - [config/broadcasting.php](#configbroadcastingphp)
  - [config/reverb.php](#configreverbphp)
  - [app/Events/ChatCreated.php](#appeventschatcreatedphp)
  - [app/Events/ChatDeleted.php](#appeventschatdeletedphp)
  - [app/Events/ChatUpdated.php](#appeventschatupdatedphp)
  - [app/Events/MessageCreated.php](#appeventsmessagecreatedphp)
  - [app/Events/MessageDeleted.php](#appeventsmessagedeletedphp)
  - [app/Events/MessageUpdated.php](#appeventsmessageupdatedphp)
  - [routes/channels.php](#routeschannelsphp)
  - [resources/js/echo.js](#resourcesjsechojs)
  - [resources/js/app.js](#resourcesjsappjs)
  - [bootstrap/app.php](#bootstrapappphp)
  - [app/Http/Controllers/API/ChatController.php](#apphttpcontrollersapichatcontrollerphp)
  - [laravel-app/.env.example](#laravel-appenvexample)
  - [compose.yaml](#composeyaml)
  - [docker/laravel/supervisord.development.conf](#dockerlaravelsupervisorddevelopmentconf)
- [How Each File Works](#how-each-file-works)
  - [Reverb WebSocket Server](#reverb-websocket-server)
  - [Broadcasting Configuration](#broadcasting-configuration)
  - [Event Classes](#event-classes)
  - [Channel Authorization](#channel-authorization)
  - [Echo JavaScript Client](#echo-javascript-client)
  - [Event Broadcasting in Controller](#event-broadcasting-in-controller)
  - [Docker Integration](#docker-integration)
- [Common Commands](#common-commands)

---

## What Changed in Session 8.3

Session 8.2 implemented the frontend send and delete message functionality that completes the frontend CRUD operations for text messages, featuring a two-way bound message input field with character limit validation, a send message handler that posts new messages via `apiCreateChatMessage()` and syncs them to the store before clearing the input and auto-scrolling to bottom, and a delete message handler with SweetAlert confirmation dialog that calls `apiDeleteChatMessage()` and removes the message from the store via `removeChatMessage()`. Session 8.3 implements real-time WebSocket broadcasting infrastructure using Laravel Reverb, enabling instant push notifications for chat and message events across all connected clients without polling. The session installs Laravel Reverb package via Composer to provide the WebSocket server, publishes broadcasting and reverb configuration files via `php artisan install:broadcasting` to define connection settings and app credentials, creates six broadcast event classes (`ChatCreated`, `ChatDeleted`, `ChatUpdated`, `MessageCreated`, `MessageDeleted`, `MessageUpdated`) using `php artisan make:event` commands that implement `ShouldBroadcastNow` interface for synchronous broadcasting, configures private channel authorization in `routes/channels.php` with `ChatEvent.{userId}` channel for user-specific chat notifications and `MessageEvent.{chatId}` channel for chat-specific message notifications, installs `laravel-echo` and `pusher-js` npm packages for JavaScript WebSocket client, configures Laravel Echo in `resources/js/echo.js` with Reverb broadcaster settings using Vite environment variables, updates `bootstrap/app.php` to register broadcasting routes with Sanctum authentication middleware and channel file path, modifies `ChatController` to broadcast events after create/update/delete operations on both chats and messages by calling `broadcast(new EventClass(...))->toOthers()` to exclude the acting user from receiving their own broadcast, adds Reverb environment variables to `.env.example` including app credentials and connection settings, exposes port 8080 in `compose.yaml` for Reverb WebSocket connections, and adds `laravel-reverb` supervisor program to `supervisord.development.conf` to run `php artisan reverb:start` continuously in the Docker container alongside the Laravel Octane server, queue worker, and schedule worker.

| Area | Session 8.2 | Session 8.3 |
|---|---|---|
| Real-time updates | Not implemented | Full WebSocket broadcasting via Reverb |
| Event broadcasting | Not configured | Six event classes for chats and messages |
| WebSocket server | None | Laravel Reverb on port 8080 |
| Broadcasting config | Not present | broadcasting.php and reverb.php published |
| Channel authorization | Not configured | Private channels with user/chat authorization |
| JavaScript client | Not present | Laravel Echo with Pusher.js configured |
| Chat CRUD broadcasting | Not implemented | Broadcasts ChatCreated, ChatUpdated, ChatDeleted |
| Message CRUD broadcasting | Not implemented | Broadcasts MessageCreated, MessageUpdated, MessageDeleted |
| Composer dependencies | No Reverb package | laravel/reverb ^1.11 installed |
| npm dependencies | No WebSocket packages | laravel-echo ^2.4.0 and pusher-js ^8.6.0 installed |
| Bootstrap config | No broadcasting routes | Broadcasting routes with Sanctum auth registered |
| Environment variables | No Reverb settings | REVERB_APP_ID, KEY, SECRET, HOST, PORT, SCHEME added |
| Docker ports | 8000 only | 8000 and 8080 exposed |
| Supervisor programs | 3 programs | 4 programs (added laravel-reverb) |
| Controller event dispatch | Not implemented | broadcast()->toOthers() after CRUD operations |
| Channel privacy | Not configured | User ID and chat membership authorization |

`composer.json` was modified by command when running `composer require laravel/reverb:^1.11` to install the Laravel Reverb package and its dependencies including Pusher HTTP PHP client and ReactPHP socket libraries for WebSocket functionality. `package.json` was modified by command when running `npm install laravel-echo@^2.4.0 pusher-js@^8.6.0 --save-dev` to install the JavaScript WebSocket client packages. `config/broadcasting.php` was generated by command when running `php artisan install:broadcasting` to publish Laravel's broadcast configuration file, then manually edited to ensure the Reverb connection is configured with app credentials and client options. `config/reverb.php` was generated by command when running `php artisan install:broadcasting` to publish Reverb's server configuration file, then manually edited to configure server host, port, scaling options, and application settings. `app/Events/ChatCreated.php` was generated by command using `php artisan make:event ChatCreated`, then manually edited to implement `ShouldBroadcastNow`, define the private channel `ChatEvent.{userId}`, set the broadcast event name to `ChatCreated`, and return a `ChatResource` with loaded messages and members in the `broadcastWith()` payload. `app/Events/ChatDeleted.php` was generated by command using `php artisan make:event ChatDeleted`, then manually edited to implement `ShouldBroadcastNow`, define the private channel `ChatEvent.{userId}`, set the broadcast event name to `ChatDeleted`, and return only the `chat_id` integer in the `broadcastWith()` payload. `app/Events/ChatUpdated.php` was generated by command using `php artisan make:event ChatUpdated`, then manually edited to implement `ShouldBroadcastNow`, define the private channel `ChatEvent.{userId}`, set the broadcast event name to `ChatUpdated`, and return a `ChatResource` with loaded messages and members in the `broadcastWith()` payload. `app/Events/MessageCreated.php` was generated by command using `php artisan make:event MessageCreated`, then manually edited to implement `ShouldBroadcastNow`, define the private channel `MessageEvent.{chatId}`, set the broadcast event name to `MessageCreated`, and return a `ChatMessageResource` with loaded creator in the `broadcastWith()` payload. `app/Events/MessageDeleted.php` was generated by command using `php artisan make:event MessageDeleted`, then manually edited to implement `ShouldBroadcastNow`, define the private channel `MessageEvent.{chatId}`, set the broadcast event name to `MessageDeleted`, and return only the `message_id` integer in the `broadcastWith()` payload. `app/Events/MessageUpdated.php` was generated by command using `php artisan make:event MessageUpdated`, then manually edited to implement `ShouldBroadcastNow`, define the private channel `MessageEvent.{chatId}`, set the broadcast event name to `MessageUpdated`, and return a `ChatMessageResource` with loaded creator in the `broadcastWith()` payload. `routes/channels.php` was generated by command when running `php artisan install:broadcasting` to publish the channel authorization file, then manually edited to define two private channel authorization callbacks: `ChatEvent.{userId}` that verifies the authenticated user's ID matches the route parameter to ensure users only receive their own chat notifications, and `MessageEvent.{chatId}` that checks if the authenticated user is a member of the chat using `$user->chats()->where('chats.id', $chatId)->exists()` to authorize access to chat-specific message broadcasts. `resources/js/echo.js` was generated by command when running `php artisan install:broadcasting` to publish the Laravel Echo configuration file, then manually edited to import `laravel-echo` and `pusher-js` packages, set `window.Pusher` for Reverb compatibility, and configure the Echo instance with `broadcaster: 'reverb'`, connection credentials from Vite environment variables (`VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`), and enabled transports of `['ws', 'wss']` for WebSocket connections. `resources/js/app.js` was edited manually to add an import statement `import './echo';` below the existing comment block to initialize the Laravel Echo WebSocket client when the application JavaScript bundle loads. `bootstrap/app.php` was edited manually to add `channels: __DIR__ . '/../routes/channels.php'` parameter in the `withRouting()` method to register the broadcast channel routes, and add the `->withBroadcasting()` method call with arguments `__DIR__ . '/../routes/channels.php'` for the channel file path and `['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']]` to ensure channel authorization requests are authenticated via Sanctum API tokens. `app/Http/Controllers/API/ChatController.php` was edited manually to import all six broadcast event classes (`ChatCreated`, `ChatDeleted`, `ChatUpdated`, `MessageCreated`, `MessageDeleted`, `MessageUpdated`), add broadcast calls after successful chat creation by looping through `$chat->members` and calling `broadcast(new ChatCreated($chat, $member->user_id))->toOthers()` for each member, add broadcast calls after successful chat update by looping through members and calling `broadcast(new ChatUpdated($chat, $member->user_id))->toOthers()`, add broadcast call after successful chat deletion by looping through members and calling `broadcast(new ChatDeleted($chat->id, $member->user_id))->toOthers()`, add broadcast call after successful message creation with `broadcast(new MessageCreated($message, $chatId))->toOthers()`, add broadcast call after successful message update with `broadcast(new MessageUpdated($message, $chatId))->toOthers()`, and add broadcast call after successful message deletion with `broadcast(new MessageDeleted($message->id, $chatId))->toOthers()`. `laravel-app/.env.example` was edited manually to add `BROADCAST_CONNECTION=reverb` to set the default broadcast driver, and add a complete Reverb configuration block including `REVERB_APP_ID=`, `REVERB_APP_KEY=`, `REVERB_APP_SECRET=`, `REVERB_HOST="localhost"`, `REVERB_PORT=8080`, `REVERB_SCHEME=http` for server settings, and matching Vite environment variables `VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"`, `VITE_REVERB_HOST="${REVERB_HOST}"`, `VITE_REVERB_PORT="${REVERB_PORT}"`, `VITE_REVERB_SCHEME="${REVERB_SCHEME}"` to expose Reverb credentials to the frontend JavaScript build. `compose.yaml` was edited manually to add port mapping `"8080:8080"` to the `laravel-service` ports array to expose the Reverb WebSocket server running inside the Docker container on port 8080 to the host machine. `docker/laravel/supervisord.development.conf` was edited manually to add a new `[program:laravel-reverb]` section with `command=php artisan reverb:start --host=0.0.0.0 --port=8080` to start the Reverb WebSocket server, `directory=/var/www/html` for the working directory, `autostart=true` and `autorestart=true` to ensure the server runs continuously and restarts on failure, and `stdout_logfile=/dev/stdout` with `stderr_logfile=/dev/stderr` to pipe logs to the container console.

---

## File Contents

The labels below tell you what action to take:
- **Modified by command** — package manager command updated the file; no content block provided.
- **Generated by command, then manually edited** — CLI command scaffolds the file; paste the block showing final contents.
- **Edited manually** — file already exists from a previous session; paste the block to replace its contents.

---

### `config/broadcasting.php`

> **Generated by command** — publish Laravel broadcast configuration file and configure Reverb connection settings.

```bash
php artisan install:broadcasting
```

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "reverb", "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over WebSockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
```

---

### `config/reverb.php`

> **Generated by command, then manually edited** — publish Reverb server configuration file and configure server options and app credentials.

The `php artisan install:broadcasting` command from the previous section also publishes this file.

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Reverb Server
    |--------------------------------------------------------------------------
    |
    | This option controls the default server used by Reverb to handle
    | incoming messages as well as broadcasting message to all your
    | connected clients. At this time only "reverb" is supported.
    |
    */

    'default' => env('REVERB_SERVER', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Servers
    |--------------------------------------------------------------------------
    |
    | Here you may define details for each of the supported Reverb servers.
    | Each server has its own configuration options that are defined in
    | the array below. You should ensure all the options are present.
    |
    */

    'servers' => [

        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'path' => env('REVERB_SERVER_PATH', ''),
            'hostname' => env('REVERB_HOST'),
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10_000),
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
                'server' => [
                    'url' => env('REDIS_URL'),
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => env('REDIS_PORT', '6379'),
                    'username' => env('REDIS_USERNAME'),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => env('REDIS_DB', '0'),
                    'timeout' => env('REDIS_TIMEOUT', 60),
                ],
            ],
            'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
            'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb Applications
    |--------------------------------------------------------------------------
    |
    | Here you may define how Reverb applications are managed. If you choose
    | to use the "config" provider, you may define an array of apps which
    | your server will support, including their connection credentials.
    |
    */

    'apps' => [

        'provider' => 'config',

        'apps' => [
            [
                'key' => env('REVERB_APP_KEY'),
                'secret' => env('REVERB_APP_SECRET'),
                'app_id' => env('REVERB_APP_ID'),
                'options' => [
                    'host' => env('REVERB_HOST'),
                    'port' => env('REVERB_PORT', 443),
                    'scheme' => env('REVERB_SCHEME', 'https'),
                    'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
                ],
                'allowed_origins' => ['*'],
                'ping_interval' => env('REVERB_APP_PING_INTERVAL', 60),
                'activity_timeout' => env('REVERB_APP_ACTIVITY_TIMEOUT', 30),
                'max_connections' => env('REVERB_APP_MAX_CONNECTIONS'),
                'max_message_size' => env('REVERB_APP_MAX_MESSAGE_SIZE', 10_000),
                'accept_client_events_from' => env('REVERB_APP_ACCEPT_CLIENT_EVENTS_FROM', 'members'),
                'rate_limiting' => [
                    'enabled' => env('REVERB_APP_RATE_LIMITING_ENABLED', false),
                    'max_attempts' => env('REVERB_APP_RATE_LIMIT_MAX_ATTEMPTS', 60),
                    'decay_seconds' => env('REVERB_APP_RATE_LIMIT_DECAY_SECONDS', 60),
                    'terminate_on_limit' => env('REVERB_APP_RATE_LIMIT_TERMINATE', false),
                ],
            ],
        ],

    ],

];
```

---

### `app/Events/ChatCreated.php`

> **Generated by command, then manually edited** — create ChatCreated event class and implement ShouldBroadcastNow with private channel and ChatResource payload.

```bash
php artisan make:event ChatCreated
```

```php
<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\Chat\ChatResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;
    public $userId;

    public function __construct(Chat $chat, int $userId)
    {
        $this->chat = $chat;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ChatEvent.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ChatCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'chat' => new ChatResource($this->chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
                        ->with('creator');
                },
                'members.user'
            ])),
        ];
    }
}
```

---

### `app/Events/ChatDeleted.php`

> **Generated by command, then manually edited** — create ChatDeleted event class and implement ShouldBroadcastNow with private channel and chat_id payload.

```bash
php artisan make:event ChatDeleted
```

```php
<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatId;
    public $userId;

    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ChatEvent.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ChatDeleted';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
        ];
    }
}
```

---

### `app/Events/ChatUpdated.php`

> **Generated by command, then manually edited** — create ChatUpdated event class and implement ShouldBroadcastNow with private channel and ChatResource payload.

```bash
php artisan make:event ChatUpdated
```

```php
<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\Chat\ChatResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;
    public $userId;

    public function __construct(Chat $chat, int $userId)
    {
        $this->chat = $chat;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ChatEvent.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ChatUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'chat' => new ChatResource($this->chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
                        ->with('creator');
                },
                'members.user'
            ])),
        ];
    }
}
```

---

### `app/Events/MessageCreated.php`

> **Generated by command, then manually edited** — create MessageCreated event class and implement ShouldBroadcastNow with private channel and ChatMessageResource payload.

```bash
php artisan make:event MessageCreated
```

```php
<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\Chat\ChatMessageResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $chatId;

    public function __construct(ChatMessage $message, int $chatId)
    {
        $this->message = $message;
        $this->chatId = $chatId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('MessageEvent.' . $this->chatId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => new ChatMessageResource($this->message->load('creator')),
        ];
    }
}
```

---

### `app/Events/MessageDeleted.php`

> **Generated by command, then manually edited** — create MessageDeleted event class and implement ShouldBroadcastNow with private channel and message_id payload.

```bash
php artisan make:event MessageDeleted
```

```php
<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;

    public $chatId;

    public function __construct(int $messageId, int $chatId)
    {
        $this->messageId = $messageId;
        $this->chatId = $chatId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('MessageEvent.' . $this->chatId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageDeleted';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
        ];
    }
}
```

---

### `app/Events/MessageUpdated.php`

> **Generated by command, then manually edited** — create MessageUpdated event class and implement ShouldBroadcastNow with private channel and ChatMessageResource payload.

```bash
php artisan make:event MessageUpdated
```

```php
<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\Chat\ChatMessageResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $chatId;

    public function __construct(ChatMessage $message, int $chatId)
    {
        $this->message = $message;
        $this->chatId = $chatId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('MessageEvent.' . $this->chatId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => new ChatMessageResource($this->message->load('creator')),
        ];
    }
}
```

---

### `routes/channels.php`

> **Generated by command, then manually edited** — publish channel authorization file and define private channel callbacks for ChatEvent and MessageEvent.

The `php artisan install:broadcasting` command from an earlier section also publishes this file.

```php
<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ChatEvent.{userId}', function ($user, $userId) {
    // Check if the user is a participant of the chat
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('MessageEvent.{chatId}', function ($user, $chatId) {
    // Check if the user is a participant of the chat
    return $user->chats()->where('chats.id', $chatId)->exists();
});
```

---

### `resources/js/echo.js`

> **Generated by command, then manually edited** — publish Laravel Echo configuration file and configure Reverb broadcaster with credentials from Vite environment variables.

The `php artisan install:broadcasting` command from an earlier section also publishes this file.

```js
import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

---

### `resources/js/app.js`

> **Edited manually** — import echo.js to initialize Laravel Echo WebSocket client on application load.

```js
//

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
```

---

### `bootstrap/app.php`

> **Edited manually** — register broadcasting routes with channel file path and Sanctum authentication middleware.

```php
<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckEnabled;
use App\Http\Middleware\UnicodeCorrection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__ . '/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware
            ->append([
                UnicodeCorrection::class
            ])
            ->alias([
                'admin' => AdminMiddleware::class,
                'enabled' => CheckEnabled::class,
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

---

### `app/Http/Controllers/API/ChatController.php`

> **Edited manually** — import broadcast event classes and add broadcast calls after CRUD operations for chats and messages.

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
use App\Http\Requests\Chat\DeleteChatMessageRequest;
use App\Http\Requests\Chat\GetChatMessagesRequest;
use App\Http\Requests\Chat\MarkAllChatMessagesAsSeenRequest;
use App\Http\Requests\Chat\UpdateChatMessageRequest;
use App\Http\Resources\Chat\ChatMessageResource;
use App\Models\ChatMessage;

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
}
```

---

### `laravel-app/.env.example`

> **Edited manually** — add Reverb environment variables for broadcast connection and WebSocket server configuration.

```env
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

BROADCAST_CONNECTION=reverb
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


REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

### `compose.yaml`

> **Edited manually** — expose port 8080 for Reverb WebSocket server in the Laravel service container.

```yaml
services:
    laravel-service:
        container_name: laravel-container
        build:
            context: .
            dockerfile: docker/laravel/Dockerfile
        working_dir: /var/www/html
        volumes:
            - ./laravel-app:/var/www/html
            - ./docker/laravel/entrypoint.development.sh:/usr/local/bin/entrypoint.development.sh
            - ./docker/laravel/supervisord.development.conf:/etc/supervisor/conf.d/supervisord.development.conf
        ports:
            - "8000:8000"
            - "8080:8080"
        depends_on:
            - mysql-service
        command: [ "bash", "/usr/local/bin/entrypoint.development.sh" ]

    vuejs-service:
        container_name: vuejs-container
        build:
            context: .
            dockerfile: docker/vuejs/Dockerfile
        working_dir: /app
        volumes:
            - ./vuejs-app:/app
            - ./docker/vuejs/entrypoint.development.sh:/usr/local/bin/entrypoint.development.sh
        ports:
            - "5173:5173"
        depends_on:
            - laravel-service
        command: [ "sh", "/usr/local/bin/entrypoint.development.sh" ]

    mysql-service:
        image: mysql:8.0
        container_name: mysql-container
        environment:
            MYSQL_DATABASE: chat_system
            MYSQL_USER: chat_user
            MYSQL_PASSWORD: chat_password
            MYSQL_ROOT_PASSWORD: root
            TZ: UTC
        volumes:
            - mysql_data:/var/lib/mysql

    phpmyadmin:
        image: phpmyadmin:5.2.2
        container_name: phpmyadmin-container
        depends_on:
            - mysql-service
        environment:
            UPLOAD_LIMIT: 50M
            PMA_HOST: mysql-service
            PMA_PORT: 3306
            PMA_USER: root
            PMA_PASSWORD: root
        ports:
            - "9000:80"
volumes:
    mysql_data:
```

---

### `docker/laravel/supervisord.development.conf`

> **Edited manually** — add laravel-reverb supervisor program to run WebSocket server alongside Laravel Octane, queue worker, and schedule worker.

```ini
[unix_http_server]
file=/var/run/supervisor.sock
chmod=0700

[supervisorctl]
serverurl=unix:///var/run/supervisor.sock

[rpcinterface:supervisor]
supervisor.rpcinterface_factory=supervisor.rpcinterface:make_main_rpcinterface

[supervisord]
nodaemon=true
logfile=/dev/stdout
logfile_maxbytes=0

[program:laravel-server]
command=php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000 --workers=2 --max-requests=500 --watch
directory=/var/www/html
environment=CHOKIDAR_USEPOLLING=true
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:laravel-queue]
command=php artisan queue:work --tries=3 --timeout=60
directory=/var/www/html
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:laravel-schedule]
command=php artisan schedule:work
directory=/var/www/html
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:laravel-reverb]
command=php artisan reverb:start --host=0.0.0.0 --port=8080
directory=/var/www/html
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

---

## How Each File Works

### Reverb WebSocket Server

Laravel Reverb is a first-party WebSocket server that replaces the need for external services like Pusher or Socket.io for real-time broadcasting. It runs as a separate long-running process via `php artisan reverb:start` and listens for incoming WebSocket connections on port 8080. The `config/reverb.php` configuration defines the server host as `0.0.0.0` to accept connections from any interface inside the Docker container, sets the port to 8080, configures scaling options for multi-server deployments via Redis (currently disabled), and defines application credentials (`REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`) that authenticate both the Laravel backend broadcasting events and the JavaScript frontend subscribing to channels. The `REVERB_HOST` environment variable points to `localhost` in development so the JavaScript client knows where to connect, while `REVERB_SCHEME` determines whether to use HTTP (`ws://`) or HTTPS (`wss://`) for the WebSocket protocol. The supervisor configuration ensures Reverb starts automatically when the Docker container launches and restarts if it crashes, with logs piped to stdout/stderr for visibility in Docker logs.

### Broadcasting Configuration

The `config/broadcasting.php` file sets the default broadcast driver to `reverb` via `BROADCAST_CONNECTION=reverb` environment variable, defining the Reverb connection with `driver: 'reverb'` that uses the Reverb broadcast adapter, app credentials from environment variables for authentication, and connection options including host, port, scheme, and TLS settings that match the Reverb server configuration. The `bootstrap/app.php` registration calls `withBroadcasting()` to enable broadcast routing, pointing to `routes/channels.php` for channel authorization logic, applying `['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']]` to ensure all channel subscription requests are authenticated via Sanctum tokens and prefixed with `/api` to match the API routing pattern. This means when a JavaScript client calls `Echo.private('ChatEvent.1')`, it first sends a POST request to `/api/broadcasting/auth` with the channel name and socket ID, Laravel checks the authorization callback in `channels.php`, and if authorized returns a signature that the client uses to subscribe to the private channel on the Reverb WebSocket server.

### Event Classes

Each broadcast event class implements `ShouldBroadcastNow` interface which tells Laravel to broadcast the event synchronously instead of queuing it for async processing. The `broadcastOn()` method returns an array of channel objects where the event should be broadcast. For chat events (`ChatCreated`, `ChatUpdated`, `ChatDeleted`), the channel is `new PrivateChannel('ChatEvent.' . $this->userId)` which creates a user-specific private channel so each user only receives notifications for their own chats without seeing other users' chat updates. For message events (`MessageCreated`, `MessageUpdated`, `MessageDeleted`), the channel is `new PrivateChannel('MessageEvent.' . $this->chatId)` which creates a chat-specific private channel so all members of that chat receive the same message notifications in real-time. The `broadcastAs()` method defines the event name that JavaScript listeners will use (e.g., `Echo.private('ChatEvent.1').listen('ChatCreated', callback)`). The `broadcastWith()` method returns the data payload that will be sent to connected clients. For create and update events, it returns a fully loaded resource with relationships (`ChatResource` with messages and members, or `ChatMessageResource` with creator) so the frontend can immediately render the new or updated data without making additional API requests. For delete events, it returns only the ID (`chat_id` or `message_id`) since the frontend just needs to know which item to remove from the UI.

### Channel Authorization

The `routes/channels.php` file defines authorization callbacks for private channels. The `Broadcast::channel('ChatEvent.{userId}', ...)` callback receives the authenticated user from the Sanctum token and the `userId` route parameter from the channel name, returning `(int) $user->id === (int) $userId` to ensure users can only subscribe to their own user-specific channel and not other users' channels. The `Broadcast::channel('MessageEvent.{chatId}', ...)` callback receives the authenticated user and the `chatId` route parameter, returning `$user->chats()->where('chats.id', $chatId)->exists()` which queries the database through the `User` model's `chats()` relationship to verify the user is a member of the chat before allowing them to subscribe to that chat's message channel. This prevents unauthorized users from eavesdropping on chat conversations they are not part of. If the authorization callback returns `false` or throws an exception, Laravel responds with a 403 Forbidden status and the WebSocket client will not be able to subscribe to that channel.

### Echo JavaScript Client

The `resources/js/echo.js` file imports the `laravel-echo` package which provides a JavaScript API for subscribing to channels and listening for broadcast events, and imports `pusher-js` which provides the Pusher protocol client that Reverb uses for WebSocket communication (Reverb implements the Pusher protocol for compatibility with existing Pusher clients). Setting `window.Pusher = Pusher` makes the Pusher library globally available as required by Laravel Echo. The `new Echo({...})` instantiation creates a global Echo instance on `window.Echo` that can be accessed from any JavaScript file, configuring it with `broadcaster: 'reverb'` to use the Reverb driver, `key: import.meta.env.VITE_REVERB_APP_KEY` to authenticate with the app key defined in `.env` and exposed to Vite via `VITE_` prefix, `wsHost`, `wsPort`, and `wssPort` from environment variables to specify where to connect (localhost:8080 in development), `forceTLS` set to `true` when `VITE_REVERB_SCHEME` is `https` to use secure WebSocket connections in production, and `enabledTransports: ['ws', 'wss']` to support both insecure and secure WebSocket protocols. The `resources/js/app.js` imports `./echo.js` to initialize the Echo client when the application JavaScript bundle loads, ensuring WebSocket connections are established as soon as the user loads the page.

### Event Broadcasting in Controller

The `ChatController` imports all six broadcast event classes and calls `broadcast(new EventClass(...))->toOthers()` after successful create, update, and delete operations. The `broadcast()` helper function dispatches the event to the broadcasting system, which serializes the event and sends it to all WebSocket clients subscribed to the event's channels. The `->toOthers()` method excludes the current user's socket connection from receiving the broadcast, preventing duplicate updates since the acting user already sees the result of their action in the HTTP response. For chat operations (`createPersonalChat`, `createGroupChat`, `updateGroupChat`, `deleteChat`), the controller loops through `$chat->members` and broadcasts the event to each member's user-specific channel `ChatEvent.{userId}`, ensuring all chat participants receive the notification regardless of which chat they are currently viewing. For message operations (`createChatMessage`, `updateChatMessage`, `deleteChatMessage`), the controller broadcasts to the chat-specific channel `MessageEvent.{chatId}`, so all users who have that chat open and are subscribed to its channel receive the message update instantly. The broadcast calls happen after database commit but before the HTTP response, ensuring the WebSocket notification is sent immediately and reaches other connected clients while the acting user is still processing the response.

### Docker Integration

The `compose.yaml` file adds port mapping `"8080:8080"` to expose the Reverb WebSocket server running on port 8080 inside the `laravel-container` to port 8080 on the host machine, allowing the JavaScript client running in the browser to connect to `ws://localhost:8080` or `http://localhost:8080` for channel authentication requests. The `supervisord.development.conf` adds a fourth supervisor program `[program:laravel-reverb]` that runs `php artisan reverb:start --host=0.0.0.0 --port=8080` to start the Reverb server, with `--host=0.0.0.0` making it listen on all network interfaces inside the container (not just localhost) so connections from outside the container can reach it, and `--port=8080` specifying the port to match the Docker port mapping. The `autostart=true` and `autorestart=true` settings ensure Reverb starts when supervisor launches and automatically restarts if it crashes or exits unexpectedly. All four programs (`laravel-server`, `laravel-queue`, `laravel-schedule`, `laravel-reverb`) run simultaneously in the same container, managed by supervisor, allowing the application to handle HTTP requests via Octane, process queued jobs, run scheduled tasks, and maintain WebSocket connections all in one container.

---

## Common Commands

```bash
# Install Laravel Reverb package
composer require laravel/reverb:^1.11

# Install JavaScript WebSocket client packages
npm install laravel-echo@^2.4.0 pusher-js@^8.6.0 --save-dev

# Publish broadcasting and reverb configuration files and resources
php artisan install:broadcasting

# Generate broadcast event classes
php artisan make:event ChatCreated
php artisan make:event ChatDeleted
php artisan make:event ChatUpdated
php artisan make:event MessageCreated
php artisan make:event MessageDeleted
php artisan make:event MessageUpdated

# Start Reverb WebSocket server (run in supervisor in production/docker)
php artisan reverb:start --host=0.0.0.0 --port=8080

# Rebuild Docker containers to apply docker-compose and supervisor changes
docker-compose up --build -d

# View Reverb server logs in Docker
docker logs -f laravel-container

# Test WebSocket connection from browser console
# (after loading a page with Echo initialized)
Echo.private('ChatEvent.1')
    .listen('ChatCreated', (e) => console.log('Chat created:', e));
```
