# ChatSystem — Laravel Octane + RoadRunner development environment

## Table of Contents

- [What Changed in Session 3.7](#what-changed-in-session-37)
- [File Contents](#file-contents)
  - [laravel-app/app/Notifications/EmailVerificationNotification.php](#laravel-appappnotificationsEmailVerificationNotificationphp)
  - [laravel-app/app/Notifications/ResetPasswordNotification.php](#laravel-appappnotificationsResetPasswordNotificationphp)
  - [laravel-app/.env.example](#laravel-appenvexample)
  - [docker/laravel/Dockerfile](#dockerlaravelDockerfile)
  - [docker/laravel/supervisord.development.conf](#dockerlaravelsupervisordevelopmentconf)
  - [docker/laravel/entrypoint.development.sh](#dockerlaravelentrypointdevelopmentsh)
  - [compose.yaml](#composeyaml)
  - [laravel-app/composer.json](#laravel-appcomposerjson)
  - [laravel-app/package.json](#laravel-apppackagejson)
  - [laravel-app/.gitignore](#laravel-appgitignore)
  - [laravel-app/config/octane.php](#laravel-appconfigoctanephp)
- [How Each File Works](#how-each-file-works)
  - [composer.json — RoadRunner package dependencies](#composerjson--roadrunner-package-dependencies)
  - [package.json — chokidar file-watcher dependency](#packagejson--chokidar-file-watcher-dependency)
  - [.gitignore — RoadRunner binary and auto-generated config exclusions](#gitignore--roadrunner-binary-and-auto-generated-config-exclusions)
  - [EmailVerificationNotification.php — queued email dispatch](#emailverificationnotificationphp--queued-email-dispatch)
  - [ResetPasswordNotification.php — queued email dispatch](#resetpasswordnotificationphp--queued-email-dispatch)
  - [.env.example — project-specific environment defaults](#envexample--project-specific-environment-defaults)
  - [Dockerfile — Node.js via multi-stage copy](#dockerfile--nodejs-via-multi-stage-copy)
  - [octane.php — published and configured Octane settings](#octanephp--published-and-configured-octane-settings)
  - [supervisord.development.conf — development-specific process supervisor config](#supervisorddevelopmentconf--development-specific-process-supervisor-config)
  - [entrypoint.development.sh — container startup sequence](#entrypointdevelopmentsh--container-startup-sequence)
  - [compose.yaml — mount development supervisord config](#composeyaml--mount-development-supervisord-config)
- [Common Commands](#common-commands)

---

## What Changed in Session 3.7

Session 3.6-frontend completed the password reset frontend loop — the Vue.js app can now request a reset link and submit a new password — but the Laravel container was still running the application through a basic PHP CLI server (`php artisan serve`) without any persistent worker process. Session 3.7 replaces that with Laravel Octane backed by RoadRunner: `laravel-app/composer.json` gains the two RoadRunner packages required by Octane (`spiral/roadrunner-cli`, `spiral/roadrunner-http`); `laravel-app/package.json` gains `chokidar` so Octane's `--watch` flag has a file-system watcher to delegate to; `laravel-app/.gitignore` excludes the RoadRunner binary (`rr`) and its auto-generated config (`.rr.yaml`); `laravel-app/config/octane.php` is published for the first time and edited to enable `DisconnectFromDatabases` and `CollectGarbage` in the `OperationTerminated` listener list; `laravel-app/.env.example` is updated with the project's real environment defaults (MySQL host, Gmail SMTP settings); both `EmailVerificationNotification` and `ResetPasswordNotification` gain `implements ShouldQueue` so email delivery is handled asynchronously by the queue worker rather than blocking an Octane request worker; `docker/laravel/Dockerfile` is updated to copy the Node.js runtime from the official `node:latest` image via a multi-stage build instead of installing it through apt; a new `docker/laravel/supervisord.development.conf` is created as a development-specific supervisor config that starts Octane with `--watch`, `--workers=2`, `--max-requests=500`, and `CHOKIDAR_USEPOLLING=true`; `docker/laravel/entrypoint.development.sh` is updated to run `npm install` before supervisord and to invoke the new development conf; and `compose.yaml` mounts `supervisord.development.conf` instead of the original `supervisord.conf`.

| Area | Session 3.6-frontend | Session 3.7 |
|---|---|---|
| PHP application server | `php artisan serve` (single-threaded, restarts per request) | Laravel Octane + RoadRunner (persistent workers, in-memory boot) |
| `composer.json` | Has `laravel/sanctum`, `laravel/tinker` | Adds `laravel/octane ^2.17` (persistent application server), `spiral/roadrunner-cli ^2.6.0` and `spiral/roadrunner-http ^3.3.0` (RoadRunner adapter packages) |
| `package.json` | Has `vite`, `tailwindcss`, `concurrently` | Adds `chokidar ^3.6.0` (required by Octane's `--watch` flag) |
| `.gitignore` | Does not exclude RoadRunner artefacts | Adds `rr` (RoadRunner binary downloaded by Octane) and `.rr.yaml` (auto-generated RoadRunner config) |
| `config/octane.php` | Does not exist (Octane config never published) | Published via `artisan vendor:publish`; `DisconnectFromDatabases` and `CollectGarbage` enabled in `OperationTerminated` |
| `.env.example` | Default Laravel skeleton values | Updated with `DB_HOST=mysql-service`, Gmail SMTP settings, project-specific defaults |
| Email notifications | `EmailVerificationNotification` and `ResetPasswordNotification` extend `Notification` synchronously | Both gain `implements ShouldQueue` — delivery is pushed to the database queue and handled by the queue worker |
| Docker image Node.js | Node.js not present in the Laravel container | Added via `COPY --from=node:latest /usr/local /usr/local` (multi-stage build) |
| Supervisord config | Single `supervisord.conf` used for all environments | Original `supervisord.conf` kept for production; new `supervisord.development.conf` created with `--watch`, `CHOKIDAR_USEPOLLING=true` |
| Entrypoint script | Runs `composer install`, `key:generate`, `migrate`, then supervisord | Adds `npm install` step; references `supervisord.development.conf` |
| `compose.yaml` | Mounts `supervisord.conf` into the container | Mounts `supervisord.development.conf` instead |

`laravel-app/composer.json` existed from a previous session and was modified by running `composer require` inside the Laravel container to add `laravel/octane` and the two RoadRunner adapter packages. `laravel-app/package.json` existed from a previous session and was modified by running `npm install --save-dev` inside the Laravel container to add `chokidar`. `laravel-app/.gitignore` existed from a previous session and was edited manually to add `rr` and `.rr.yaml`. `laravel-app/config/octane.php` is a new file scaffolded by `php artisan vendor:publish` and then manually edited to enable `DisconnectFromDatabases` and `CollectGarbage`. `laravel-app/.env.example` existed from a previous session and was edited manually to reflect the project's real environment defaults. `laravel-app/app/Notifications/EmailVerificationNotification.php` existed from a previous session and was edited manually to add `implements ShouldQueue`. `laravel-app/app/Notifications/ResetPasswordNotification.php` existed from a previous session and was edited manually to add `implements ShouldQueue`. `docker/laravel/Dockerfile` existed from a previous session and was edited manually to replace the apt Node.js installation with a multi-stage copy. `docker/laravel/supervisord.development.conf` is a new file that did not exist before this session and was created manually. `docker/laravel/entrypoint.development.sh` existed from a previous session and was edited manually to add `npm install` and update the supervisord path. `compose.yaml` existed from a previous session and was edited manually to mount the new development supervisord config. `laravel-app/composer.lock`, `laravel-app/package-lock.json`, and `vuejs-app/package-lock.json` were updated automatically by their respective package managers and require no manual action.

---

## File Contents

The labels below each heading tell you what action to take:
- **Modified by command** — the file already exists; run the command shown to let the package manager add the dependency.
- **Generated by command, then manually edited** — run the command below inside the Laravel container to scaffold the file, then paste the block to replace its contents.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.
- **Created manually** — the file does not exist yet; create it and paste the block.

Follow the sections in order from top to bottom.

---

### `laravel-app/app/Notifications/EmailVerificationNotification.php`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `implements ShouldQueue` added to the class declaration so email dispatch is handled by the queue worker.

```php
<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use URL;

class EmailVerificationNotification extends Notification implements ShouldQueue
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

### `laravel-app/app/Notifications/ResetPasswordNotification.php`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `implements ShouldQueue` added to the class declaration so email dispatch is handled by the queue worker.

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
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

### `laravel-app/.env.example`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `APP_URL=http://localhost:8000` for queueing callback URLs to work correctly.

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
```

---

### `docker/laravel/Dockerfile`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `supervisor` added to the apt packages, `sockets` added to the PHP extensions, and a multi-stage copy of the Node.js runtime from the official `node:latest` image instead of installing it through apt.

```dockerfile
FROM php:8.4-cli

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    supervisor \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip pcntl sockets \
    && rm -rf /var/lib/apt/lists/*

COPY docker/laravel/*.sh /usr/local/bin/

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

COPY --from=node:latest /usr/local /usr/local

COPY laravel-app /var/www/html

RUN chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod +x /usr/local/bin/*.sh

RUN composer install

EXPOSE 8000
```

---

### `docker/laravel/supervisord.development.conf`

> **Created manually** — create this file at `docker/laravel/supervisord.development.conf` and paste the block below to define the development-specific process supervisor configuration with Octane `--watch` and `CHOKIDAR_USEPOLLING=true`.

```properties
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
```

---

### `docker/laravel/entrypoint.development.sh`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `npm install` added after `composer install` and the supervisord path to `supervisord.development.conf`.

```bash
#!/bin/bash
set -e

# composer install
# wait $!
# npm install
# wait $!
php artisan key:generate
wait $!
php artisan migrate
wait $!
exec supervisord -c /etc/supervisor/conf.d/supervisord.development.conf
```

---

### `compose.yaml`

> **Edited manually** — the file already exists; paste the block below to replace its contents with the `laravel-service` volumes section updated to mount `supervisord.development.conf`.

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

### `laravel-app/composer.json`

> **Modified by command** — run the commands below inside the Laravel container in order; `composer.json` and `composer.lock` are updated automatically after each.

```bash
# 1. Install Octane
composer require laravel/octane 
```

---

### RoadRunner binary setup

> **Run command** — run the command below inside the Laravel container to download the RoadRunner binary (`rr`) and generate `.rr.yaml`. Must be run after installing Octane.

```bash
php artisan octane:install --server=roadrunner
```

![Alt text](/instructions/images/Screenshot%202026-05-23%20at%209.31.54%20at%20night.png)


---

### `laravel-app/package.json`

> **Modified by command** — run the command below inside the Laravel container to add `chokidar` for Octane's `--watch`. `package.json` and `package-lock.json` are updated automatically.

```bash
npm install chokidar --save-dev
```

---

### `laravel-app/.gitignore`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `supervisord.pid`, `rr`, and `.rr.yaml` added at the bottom.

```
*.log
.DS_Store
.env
.env.backup
.env.production
.phpactor.json
.phpunit.result.cache
/.codex
/.cursor/
/.idea
/.nova
/.phpunit.cache
/.vscode
/.zed
/auth.json
/node_modules
/public/build
/public/fonts-manifest.dev.json
/public/hot
/public/storage
/storage/*.key
/storage/pail
/vendor
_ide_helper.php
Homestead.json
Homestead.yaml
Thumbs.db
supervisord.pid
rr
.rr.yaml
```

---

### `laravel-app/config/octane.php`

> **Generated by command, then manually edited** — run the command below inside the Laravel container to publish the Octane config, then paste the block to replace the generated file with `DisconnectFromDatabases` and `CollectGarbage` uncommented in the `OperationTerminated` listener list.

```bash
php artisan vendor:publish --provider="Laravel\Octane\OctaneServiceProvider"
```

```php
<?php

use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CloseMonologHandlers;
use Laravel\Octane\Listeners\CollectGarbage;
use Laravel\Octane\Listeners\DisconnectFromDatabases;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
use Laravel\Octane\Listeners\FlushOnce;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\FlushUploadedFiles;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;
use Laravel\Octane\Octane;

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines the default "server" that will be used by Octane
    | when starting, restarting, or stopping your server via the CLI. You
    | are free to change this to the supported server of your choosing.
    |
    | Supported: "roadrunner", "swoole", "frankenphp"
    |
    */

    'server' => env('OCTANE_SERVER', 'roadrunner'),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When this configuration value is set to "true", Octane will inform the
    | framework that all absolute links must be generated using the HTTPS
    | protocol. Otherwise your links may be generated using plain HTTP.
    |
    */

    'https' => env('OCTANE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state for
    | the next request. You may even add your own listeners to the list.
    |
    */

    'listeners' => [
        WorkerStarting::class => [
            EnsureUploadedFilesAreValid::class,
            EnsureUploadedFilesCanBeMoved::class,
        ],

        RequestReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            ...Octane::prepareApplicationForNextRequest(),
            //
        ],

        RequestHandled::class => [
            //
        ],

        RequestTerminated::class => [
            // FlushUploadedFiles::class,
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TaskTerminated::class => [
            //
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TickTerminated::class => [
            //
        ],

        OperationTerminated::class => [
            FlushOnce::class,
            FlushTemporaryContainerInstances::class,
            DisconnectFromDatabases::class,
            CollectGarbage::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            CloseMonologHandlers::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | The bindings listed below will either be pre-warmed when a worker boots
    | or they will be flushed before every new request. Flushing a binding
    | will force the container to resolve that binding again when asked.
    |
    */

    'warm' => [
        ...Octane::defaultServicesToWarm(),
    ],

    'flush' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Tables
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may define additional tables as required by the
    | application. These tables can be used to store data that needs to be
    | quickly accessed by other workers on the particular Swoole server.
    |
    */

    'tables' => [
        'example:1000' => [
            'name' => 'string:1000',
            'votes' => 'int',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Cache Table
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may leverage the Octane cache, which is powered
    | by a Swoole table. You may set the maximum number of rows as well as
    | the number of bytes per row using the configuration options below.
    |
    */

    'cache' => [
        'rows' => 1000,
        'bytes' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watching
    |--------------------------------------------------------------------------
    |
    | The following list of files and directories will be watched when using
    | the --watch option offered by Octane. If any of the directories and
    | files are changed, Octane will automatically reload your workers.
    |
    */

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When executing long-lived PHP scripts such as Octane, memory can build
    | up before being cleared by PHP. You can force Octane to run garbage
    | collection if your application consumes this amount of megabytes.
    |
    */

    'garbage' => 50,

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | The following setting configures the maximum execution time for requests
    | being handled by Octane. You may set this value to 0 to indicate that
    | there isn't a specific time limit on Octane request execution time.
    |
    */

    'max_execution_time' => 30,

];
```

---

## How Each File Works

### `EmailVerificationNotification.php` — queued email dispatch

Adding `implements ShouldQueue` instructs Laravel's notification system to serialise the notification and push it onto the configured queue (the `jobs` database table) instead of sending the email synchronously inside the Octane request worker. The `laravel-queue` supervisor process then picks up the job and calls `toMail`. This prevents the SMTP round-trip from blocking the Octane worker for the duration of the request, which is especially important with persistent workers — a slow SMTP server would otherwise hold a worker slot for seconds on every signup.

---

### `ResetPasswordNotification.php` — queued email dispatch

The same `implements ShouldQueue` change applied to `EmailVerificationNotification` is applied here for the same reason: the password reset email is dispatched through the queue worker rather than inside the request worker. The `Queueable` trait was already present from session 3.5; adding `implements ShouldQueue` activates it.

---

### `.env.example` — project-specific environment defaults

`DB_HOST` is set to `mysql-service` (the Docker Compose service name) rather than the default `127.0.0.1`, which would be unreachable from inside the Laravel container. The `MAIL_*` block is pre-filled with Gmail SMTP settings (`smtp.gmail.com:587`, TLS encryption) as a reference so developers know which credentials format to use when configuring their own `.env`. `SESSION_DRIVER=database` and `QUEUE_CONNECTION=database` are retained from previous sessions; both are correct for the multi-worker Octane setup because file-based drivers would cause state inconsistencies across workers.

---

### `Dockerfile` — Node.js via multi-stage copy

`COPY --from=node:latest /usr/local /usr/local` copies the entire `/usr/local` tree from the official `node:latest` image — which contains the `node` and `npm` binaries — into the PHP image's `/usr/local`. This approach avoids adding `nodejs` and `npm` to the apt layer (which would pull the older Debian-packaged version) and keeps the final image layer count low. Node.js is needed at runtime because the `laravel-server` supervisor program runs Octane with `--watch`, and chokidar requires `node` to execute.

---

### `supervisord.development.conf` — development-specific process supervisor config

The file is intentionally separate from `docker/laravel/supervisord.conf` (which remains unchanged for production use) so that the two environments can diverge without conflicts. The key differences from the production config are in the `[program:laravel-server]` block:

- `--watch` — enables chokidar file watching so workers reload automatically when PHP source files change.
- `--workers=2` — limits the worker count to two, reducing memory usage on development machines while still allowing concurrent requests during testing.
- `--max-requests=500` — recycles each worker after 500 requests to prevent slow memory accumulation between container restarts.
- `environment=CHOKIDAR_USEPOLLING=true` — forces chokidar to use filesystem polling rather than native inotify events. On macOS with Docker Desktop, file changes made on the host are delivered to the Linux container through a virtual filesystem layer that does not propagate inotify events; polling is the reliable fallback. The `environment=` directive in a supervisord `[program]` block injects the variable directly into that process's environment, bypassing Docker Compose's `environment:` section which Supervisor does not inherit.

---

### `entrypoint.development.sh` — container startup sequence

`npm install` is added after `composer install` so that `chokidar` (declared in `package.json`) is installed inside the container before supervisord starts the Octane process that depends on it. The `wait $!` after each command ensures the previous step finishes fully before the next begins. The supervisord invocation is updated from `supervisord.conf` to `supervisord.development.conf` so the development-specific Octane flags and chokidar polling setting take effect.

---

### `compose.yaml` — mount development supervisord config

The `laravel-service` volumes section replaces the previous `supervisord.conf` mount with `supervisord.development.conf`, mapping the host file `./docker/laravel/supervisord.development.conf` to `/etc/supervisor/conf.d/supervisord.development.conf` inside the container. The entrypoint script references this exact path, so Supervisor loads the development config on startup.

---

### `composer.json` — RoadRunner package dependencies

`spiral/roadrunner-cli` provides the `rr` binary download command (`./vendor/bin/rr get-binary`) that Octane calls during its first start to place the RoadRunner Go server binary in the project root. `spiral/roadrunner-http` provides the PHP worker-side HTTP bridge that RoadRunner uses to pass each incoming HTTP request from the Go process into a PHP worker. Both packages are required at runtime (not just dev) because the production server also runs Octane with RoadRunner.

---

### `package.json` — chokidar file-watcher dependency

`chokidar` is a Node.js file-watching library. Octane's `--watch` flag does not implement file watching itself — it delegates entirely to chokidar. When Octane detects a file change via chokidar it sends a graceful reload signal to RoadRunner, which restarts all PHP workers without dropping in-flight requests. `chokidar` is placed in `devDependencies` because it is only needed during development; the production supervisord config does not pass `--watch`.

---

### `.gitignore` — RoadRunner binary and auto-generated config exclusions

`rr` is the RoadRunner binary that Octane downloads via `./vendor/bin/rr get-binary` on first start and places in the project root. `.rr.yaml` is the RoadRunner server config that Octane auto-generates at startup (it exists on disk but contains zero bytes because Octane manages it internally). Both artefacts are environment-specific and should not be committed to version control. The `supervisord.pid` file is also added to `.gitignore` since it is generated at runtime by supervisord and does not need to be tracked.

---

### `octane.php` — published and configured Octane settings

`vendor:publish` copies the default `config/octane.php` stub from the `laravel/octane` package into the application's `config/` directory so it can be version-controlled and customised.

Two listeners are enabled in the `OperationTerminated` event (fired after every request, task, or tick completes):

- `DisconnectFromDatabases::class` — closes all open database connections at the end of each operation. Without this, each Octane worker accumulates persistent connections that are never released back to MySQL's connection pool. In a chat application with many concurrent users this would quickly exhaust `DB_CONNECTION` limits.
- `CollectGarbage::class` — calls `gc_collect_cycles()` when worker memory usage exceeds the `garbage` threshold (50 MB). Because Octane workers are long-lived processes, PHP's automatic garbage collection schedule does not align with request boundaries; this listener forces a collection pass at a controlled point.

The `watch` array lists the paths chokidar monitors when `--watch` is active. The `max_execution_time` of 30 seconds acts as a request timeout; RoadRunner will kill a worker that exceeds it. The `server` key reads `OCTANE_SERVER` from the environment, defaulting to `'roadrunner'`.

---

## Common Commands

```bash
# Install Octane (modifies composer.json and composer.lock automatically)
docker exec laravel-container composer require laravel/octane

# Install RoadRunner server (downloads rr binary, creates .rr.yaml)
docker exec laravel-container php artisan octane:install --server=roadrunner

# Add RoadRunner adapter packages (modifies composer.json and composer.lock automatically)
docker exec laravel-container composer require spiral/roadrunner-cli:^2.6.0 spiral/roadrunner-http:^3.3.0

# Add chokidar (modifies package.json and package-lock.json automatically)
docker exec laravel-container npm install chokidar --save-dev

# Rebuild the Laravel image and restart all services (required after Dockerfile or entrypoint changes)
docker compose down && docker compose up --build

# Publish the Octane config inside the running Laravel container
docker exec laravel-container php artisan vendor:publish --provider="Laravel\Octane\OctaneServiceProvider"

# Check supervisor process status inside the container
docker exec laravel-container supervisorctl status

# Tail container logs to verify Octane and RoadRunner started correctly
docker logs laravel-container --tail 50

# Verify chokidar is available inside the container after rebuild
docker exec laravel-container node -e "require('chokidar')" && echo "chokidar OK"
```
