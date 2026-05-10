# ChatSystem — Project Initialization

## Table of Contents

- [What Changed in Session 2](#what-changed-in-session-2)
- [New Project Structure](#new-project-structure)
- [New Services Overview](#new-services-overview)
- [File Contents](#file-contents)
  - [compose.yaml](#composeyaml)
  - [docker/laravel/Dockerfile](#dockerlaraveldockerfile)
  - [docker/laravel/entrypoint.development.sh](#dockerlaravelentrypointdevelopmentsh)
  - [docker/vuejs/Dockerfile](#dockervuejsdockerfile)
  - [docker/vuejs/entrypoint.development.sh](#dockervuejsentrypointdevelopmentsh)
- [How Each File Works](#how-each-file-works)
- [Environment Variables](#environment-variables)
- [Service Startup Order](#service-startup-order)
- [Running the Stack](#running-the-stack)
- [Ports](#ports)
- [Common Docker Commands](#common-docker-commands)
- [Troubleshooting](#troubleshooting)

---

## What Changed in Session 2

Session 1 kept containers alive with `sleep infinity` and required you to manually shell in to run dev servers. Session 2 automates all of that:

| Area | Session 1 | Session 2 |
|---|---|---|
| Container startup | `sleep infinity` | Entrypoint scripts auto-run servers |
| Database | None | MySQL 8.0 service added |
| DB admin UI | None | phpMyAdmin added |
| Laravel DB config | None | Configured in `.env.example` (copied to `.env`) |
| `composer install` | Manual (inside container) | Runs automatically on every start |
| `php artisan migrate` | Manual | Runs automatically on every start |
| `npm install` | Manual (inside container) | Runs automatically on every start |
| Dev servers | Started manually | Started automatically |

---

## New Project Structure

```
ChatSystem/
├── compose.yaml                        # Docker Compose configuration
├── .gitattributes                      # Enforces LF line endings and case sensitivity
├── .gitignore                          # Ignores OS/editor junk files
├── .editorconfig                       # Enforces consistent formatting across editors
├── docker/
│   ├── laravel/
│   │   ├── Dockerfile                  # PHP 8.4-cli + Composer 2.9 image
│   │   ├── Dockerfile.dockerignore    # Build context exclusions for laravel-service
│   │   └── entrypoint.development.sh  # Entrypoint script for laravel-service
│   └── vuejs/
│       ├── Dockerfile                  # Node 24.12.0 (Alpine) image
│       ├── Dockerfile.dockerignore    # Build context exclusions for vuejs-service
│       └── entrypoint.development.sh  # Entrypoint script for vuejs-service
├── laravel-app/                        # Laravel source code (mounted into laravel-container)
└── vuejs-app/                          # Vue.js source code (mounted into vuejs-container)
```

---

## New Services Overview

| Container | Image | Role | Port |
|---|---|---|---|
| `laravel-container` | `php:8.4-cli` + Composer 2.9 | Laravel API / backend | 8000 |
| `vuejs-container` | `node:24.12.0-alpine` | Vue.js frontend | 5173 |
| `mysql-container` | `mysql:8.0` | Relational database | 3306 |
| `phpmyadmin-container` | `phpmyadmin:5.2.2` | Database admin UI | 9000 |

---

## File Contents

Copy each block below and paste it into the corresponding file.

---

### `compose.yaml`

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
    ports:
      - "8000:8000"
    depends_on:
      - mysql-service
    command: ["bash", "/usr/local/bin/entrypoint.development.sh"]

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
    command: ["sh", "/usr/local/bin/entrypoint.development.sh"]

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

### `docker/laravel/Dockerfile`

```dockerfile
FROM php:8.4-cli

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY docker/laravel/*.sh /usr/local/bin/

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

COPY laravel-app /var/www/html

RUN chown -R www-data:www-data /var/www/html/bootstrap/cache \
 && chown -R www-data:www-data /var/www/html/storage \
 && chmod +x /usr/local/bin/*.sh

RUN composer install

EXPOSE 8000
```

---

### `docker/laravel/entrypoint.development.sh`

```bash
#!/bin/bash
set -e

# composer install
# wait $!
php artisan key:generate
wait $!
php artisan migrate
wait $!
php artisan serve --host=0.0.0.0 --port=8000
```

---

### `docker/vuejs/Dockerfile`

```dockerfile
FROM node:24.12.0-alpine

WORKDIR /app

COPY docker/vuejs/*.sh /usr/local/bin/

COPY vuejs-app /app

RUN chmod +x /usr/local/bin/*.sh

EXPOSE 5173
```

---

### `docker/vuejs/entrypoint.development.sh`

```sh
#!/bin/sh
set -e

# npm install
# wait $!
npm run dev -- --host=0.0.0.0 --port=5173
```

---

## How Each File Works

### `compose.yaml`

- **`laravel-service`** — builds from `docker/laravel/Dockerfile`, mounts `./laravel-app` over `/var/www/html` so live edits are reflected without rebuilding. The entrypoint script is also volume-mounted so changes to it take effect on the next `docker compose up`. Database connection settings come from `laravel-app/.env.example`. `depends_on: mysql-service` ensures MySQL starts first.
- **`vuejs-service`** — builds from `docker/vuejs/Dockerfile`, mounts `./vuejs-app` over `/app`. `depends_on: laravel-service` starts Vue.js after Laravel.
- **`mysql-service`** — uses the official `mysql:8.0` image. Data is stored in the named volume `mysql_data` so it survives `docker compose down`. Not exposed on a host port — only accessible to other containers via Docker's internal DNS.
- **`phpmyadmin` service** — connects to MySQL using the root credentials. Access at `http://localhost:9000`.
- **`volumes: mysql_data`** — top-level declaration that makes `mysql_data` a Docker-managed named volume.

### `docker/laravel/Dockerfile`

1. Installs system libraries needed by PHP extensions (`pdo_mysql`, `mbstring`, `bcmath`, `gd`, `zip`, `pcntl`).
2. Copies entrypoint scripts into `/usr/local/bin/` inside the image.
3. Copies Composer from its official image.
4. Copies the Laravel source into `/var/www/html` (the volume mount at runtime overlays this with your live host files).
5. Fixes ownership on `storage/` and `bootstrap/cache/` so Laravel can write to them.
6. Runs `composer install` at build time to pre-cache vendor dependencies.
7. Exposes port `8000`.

### `docker/laravel/entrypoint.development.sh`

Runs in order on every container start:

1. **`set -e`** — aborts immediately on any error.
2. **`composer install`** — ensures all PHP dependencies are installed/updated.
3. **`php artisan key:generate`** — writes `APP_KEY` to `.env` (skipped automatically if one already exists).
4. **`php artisan migrate`** — applies any pending database migrations.
5. **`php artisan serve --host=0.0.0.0 --port=8000`** — starts the development server on all interfaces.

`wait $!` after each command ensures the previous step fully completes before the next begins.

### `docker/vuejs/Dockerfile`

1. Copies entrypoint scripts into `/usr/local/bin/`.
2. Copies the Vue.js source into `/app` (the volume mount at runtime overlays this).
3. Marks all `.sh` scripts as executable.
4. Exposes port `5173`.

### `docker/vuejs/entrypoint.development.sh`

Runs in order on every container start:

1. **`set -e`** — aborts immediately on any error.
2. **`rm -f package-lock.json`** — removes a stale lockfile that may have been generated on a different OS/architecture, preventing native module conflicts.
3. **`npm install`** — installs all Node.js dependencies.
4. **`npm run dev -- --host=0.0.0.0 --port=5173`** — starts the Vite dev server on all interfaces.

`wait $!` after each command ensures the previous step fully completes before the next begins.

---

## Environment Variables

Laravel's database connection is configured in `laravel-app/.env.example`. Copy it to `laravel-app/.env` if it doesn't exist — the entrypoint runs `php artisan key:generate` which handles the app key automatically.

| Variable | Value | Purpose |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Laravel uses the MySQL driver |
| `DB_HOST` | `mysql-service` | Docker service name — resolved by Docker's internal DNS, not `localhost` |
| `DB_PORT` | `3306` | MySQL default port |
| `DB_DATABASE` | `chat_system` | Database name |
| `DB_USERNAME` | `chat_user` | Application user |
| `DB_PASSWORD` | `chat_password` | Password for `chat_user` |

> `DB_HOST` must be `mysql-service` (the service name in `compose.yaml`), not `127.0.0.1`. Each container is isolated — `localhost` inside the Laravel container refers to itself, not the MySQL container.

---

## Service Startup Order

`depends_on` controls the startup sequence:

```
mysql-service  →  laravel-service  →  vuejs-service
```

1. **MySQL** starts and accepts connections.
2. **Laravel** starts, runs `composer install` → `key:generate` → `migrate` → `artisan serve`.
3. **Vue.js** starts, runs `npm install` → `npm run dev`.

> `depends_on` only waits for the container process to start, not for the service inside to be fully ready. On the very first run, MySQL needs a few seconds to initialise its data directory. If `php artisan migrate` fails because MySQL isn't ready yet, run `docker compose up` again — MySQL will be ready immediately on the second run.

---

## Running the Stack

### 1. Build and start all containers

```bash
docker compose up --build
```

> Use `docker compose up --build -d` to run in the background (detached mode).

### 2. Verify all containers are running

```bash
docker compose ps
```

### 3. Access the services

| Service | URL |
|---|---|
| Laravel backend | http://localhost:8000 |
| Vue.js frontend | http://localhost:5173 |
| phpMyAdmin | http://localhost:9000 |

### 4. View live logs

```bash
# All services
docker compose logs -f

# One service
docker compose logs -f laravel-service
```

---

## Ports

| Service | Container port | Host port | URL |
|---|---|---|---|
| Laravel | 8000 | 8000 | http://localhost:8000 |
| Vue.js | 5173 | 5173 | http://localhost:5173 |
| MySQL | 3306 | none (internal only) | accessible only to other containers |
| phpMyAdmin | 80 | 9000 | http://localhost:9000 |

---

## Common Docker Commands

| Action | Command |
|---|---|
| Build and start all containers | `docker compose up --build` |
| Start in background (detached) | `docker compose up --build -d` |
| Stop all containers | `docker compose down` |
| Stop and remove volumes (wipes DB) | `docker compose down -v` |
| View running containers | `docker compose ps` |
| Follow all logs live | `docker compose logs -f` |
| Follow logs for one service | `docker compose logs -f laravel-service` |
| Shell into Laravel container | `docker exec -it laravel-container bash` |
| Shell into Vue.js container | `docker exec -it vuejs-container sh` |
| Shell into MySQL container | `docker exec -it mysql-container bash` |
| Connect to MySQL directly | `docker exec -it mysql-container mysql -u chat_user -p chat_system` |
| Rebuild a single service | `docker compose build laravel-service` |
| Restart a single service | `docker compose restart laravel-service` |
| Remove stopped containers | `docker compose rm` |

---

## Troubleshooting

### Laravel fails on first `docker compose up` with a migration error

MySQL takes a few seconds to initialise on the very first run. Run `docker compose up` again — MySQL will be ready on the second start.

### `php artisan migrate` fails with "Access denied"

The credentials in `laravel-app/.env` must exactly match `MYSQL_USER`, `MYSQL_PASSWORD`, and `MYSQL_DATABASE` in the `mysql-service` service in `compose.yaml`.

### Vue.js shows a blank page or HMR does not work

The `--host=0.0.0.0` flag is required for Vite to be reachable outside the container. Check `docker/vuejs/entrypoint.development.sh`.

### Port already in use

Another process is using 8000, 5173, 3306, or 9000. Stop that process or change the host-side port (left number) in the `ports` mapping in `compose.yaml`.

### Data is lost after `docker compose down`

`docker compose down` keeps the `mysql_data` named volume — your data is safe.  
`docker compose down -v` **deletes the volume and all data**. Only use it for a clean reset.
