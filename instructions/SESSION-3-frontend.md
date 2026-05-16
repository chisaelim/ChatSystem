# ChatSystem — Vue Router setup & auth page stubs

## Table of Contents

- [What Changed in Session 3.0-frontend](#what-changed-in-session-30-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/vite.config.js](#vuejs-appviteconfigjs)
  - [vuejs-app/src/App.vue](#vuejs-appsrcAppvue)
  - [vuejs-app/src/router/index.js](#vuejs-appsrcrouterindexjs)
  - [vuejs-app/src/components/auth/Signin.vue](#vuejs-appsrccomponentsauthSigninvue)
  - [vuejs-app/src/components/auth/Signup.vue](#vuejs-appsrccomponentsauthSignupvue)
  - [vuejs-app/src/components/auth/Signout.vue](#vuejs-appsrccomponentsauthSignoutvue)
  - [vuejs-app/src/components/pages/Dashboard.vue](#vuejs-appsrccomponentspagesDashboardvue)
- [How Each File Works](#how-each-file-works)
  - [vite.config.js — Docker server config](#viteconfigjs--docker-server-config)
  - [App.vue — root component](#appvue--root-component)
  - [router/index.js — route definitions](#routerindexjs--route-definitions)
  - [Auth & page components — stubs](#auth--page-components--stubs)
- [Common Commands](#common-commands)

---

## What Changed in Session 3.0-frontend

Sessions 2.x established the Laravel backend: authentication endpoints, Sanctum tokens, Form Requests, and API Resources. Session 3.0-frontend shifts focus entirely to the Vue.js front-end. The raw `npm create vue@latest` scaffold is cleaned up — default views and components are removed — and replaced with a custom routing structure built around the three auth flows (sign in, sign up, sign out) and a protected dashboard page.

| Area | Session 2.2 | Session 3.0-frontend |
|---|---|---|
| Frontend state | Raw scaffold output from `npm create vue@latest` | Customised for the project; default views deleted |
| `App.vue` | Scaffold default (included nav links and `RouterView`) | Stripped to a single `<router-view>` |
| Vue Router | Default routes pointing to `HomeView` and `AboutView` | Custom routes: `/`, `/signup`, `/signout`, `/dashboard`, catch-all redirect |
| Components | Scaffold defaults (`HelloWorld`, `TheWelcome`, etc.) | `auth/Signin`, `auth/Signup`, `auth/Signout`, `pages/Dashboard` stubs |
| `vite.config.js` | Scaffold defaults — no server block | Docker-compatible HMR, polling watcher, explicit host and port |
| Laravel backend | Form Requests, API Resources, Sanctum auth | Unchanged |

`vite.config.js` already existed from the scaffold and was edited manually to add the `server` block required for Vite to work correctly inside a Docker volume. `App.vue` and `src/router/index.js` also already existed from the scaffold and were edited manually — their bodies were replaced entirely. The four component files (`Signin.vue`, `Signup.vue`, `Signout.vue`, `Dashboard.vue`) are new and were created manually as stubs; they have no content beyond a placeholder heading. Default scaffold files in `src/views/` and `src/components/` (e.g. `HomeView.vue`, `AboutView.vue`, `HelloWorld.vue`) were deleted.

---

## File Contents

The labels below each heading tell you what action to take:
- **Edited manually** — the file already exists from the scaffold; paste the block to replace its contents with the session version.
- **Created manually** — the file does not exist yet; create it and paste the block.

Follow the sections in order from top to bottom.

---

### `vuejs-app/vite.config.js`

> **Edited manually** — the file already exists from the scaffold; replace the entire contents with the block below to add Docker-compatible server settings.

```js
import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
      protocol: 'ws',
      port: 5173,
    },
    watch: {
      usePolling: true,
      useFsEvents: true,
      interval: 1000,
    }
  }
})
```

---

### `vuejs-app/src/App.vue`

> **Edited manually** — the file already exists from the scaffold; replace the entire contents with the block below to remove default nav and hand routing control to `vue-router`.

```vue
<script setup></script>

<template>
  <router-view></router-view>
</template>

<style scoped></style>
```

---

### `vuejs-app/src/router/index.js`

> **Edited manually** — the file already exists from the scaffold; replace the entire contents with the block below to define the project-specific routes.

```js
import Signin from '@/components/auth/Signin.vue';
import Signout from '@/components/auth/Signout.vue';
import Signup from '@/components/auth/Signup.vue';
import Dashboard from '@/components/pages/Dashboard.vue';
import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'auth.signin',
      component: Signin,
    },
    {
      path: '/signout',
      name: 'auth.signout',
      component: Signout,
    },
    {
      path: '/signup',
      name: 'auth.signup',
      component: Signup,
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: Dashboard,
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/dashboard',
    }
  ],
})

export default router
```

---

### `vuejs-app/src/components/auth/Signin.vue`

> **Created manually** — create the file at this path and paste the stub below; it will be fleshed out in a later session.

```vue
<template>
  <h1>Signin</h1>
</template>

<script setup></script>
```

---

### `vuejs-app/src/components/auth/Signup.vue`

> **Created manually** — create the file at this path and paste the stub below; it will be fleshed out in a later session.

```vue
<template>
  <h1>Signup</h1>
</template>

<script setup></script>
```

---

### `vuejs-app/src/components/auth/Signout.vue`

> **Created manually** — create the file at this path and paste the stub below; it will be fleshed out in a later session.

```vue
<template>
  <h1>Signout</h1>
</template>

<script setup></script>
```

---

### `vuejs-app/src/components/pages/Dashboard.vue`

> **Created manually** — create the file at this path and paste the stub below; it will be fleshed out in a later session.

```vue
<template>
    <h1>Dashboard</h1>
</template>

<script setup></script>
```

---

## How Each File Works

### vite.config.js — Docker server config

The default Vite scaffold has no `server` block — it assumes Vite is running directly on the developer's machine. When Vite runs inside a Docker container with a mounted volume, three problems arise without extra config:

**`host: '0.0.0.0'` and `port: 5173`** — By default Vite binds to `127.0.0.1`, which is only reachable from inside the container. Setting `host: '0.0.0.0'` makes the dev server listen on all network interfaces, so the host machine can reach it at `http://localhost:5173`.

**`hmr.protocol` and `hmr.port`** — Vite's Hot Module Replacement (HMR) opens a WebSocket from the browser back to the dev server. When the connection goes through Docker port-forwarding, Vite can infer the wrong WebSocket URL. Explicitly setting `protocol: 'ws'` and `port: 5173` pins the values so HMR connects correctly.

**`watch.usePolling` and `watch.interval`** — Linux inotify (the default file-watch mechanism) does not fire events when files change on a bind-mounted volume from a Windows or macOS host. `usePolling: true` switches to polling instead, and `interval: 1000` sets the polling frequency to once per second. `useFsEvents: true` keeps the native event mechanism active as a complement on platforms where it does work.

---

### App.vue — root component

`App.vue` is the single component that Vue mounts into `<div id="app">` in `index.html`. The default scaffold includes a nav bar with `<RouterLink>` elements and wraps everything in a styled container. For this project, all navigation is handled programmatically (via `router.push()`) or through link components inside individual page components. The root therefore needs to do nothing more than render whichever route component is currently active — that is the sole job of `<router-view>`.

---

### router/index.js — route definitions

The router is created with `createWebHistory`, which uses the browser's History API to produce clean URLs (no `#` hash). Routes are defined as follows:

| Path | Name | Component | Purpose |
|---|---|---|---|
| `/` | `auth.signin` | `Signin.vue` | Default landing page — the sign-in form |
| `/signup` | `auth.signup` | `Signup.vue` | Registration form |
| `/signout` | `auth.signout` | `Signout.vue` | Triggers token deletion and redirects |
| `/dashboard` | `dashboard` | `Dashboard.vue` | Protected home screen after sign-in |
| `/:pathMatch(.*)*` | — | redirect to `/dashboard` | Catch-all for unknown URLs |

Route names follow the `group.action` convention (`auth.signin`, `auth.signup`, `auth.signout`) so they can be referenced in `<RouterLink :to="{ name: 'auth.signup' }">` or `router.push({ name: 'dashboard' })` without hardcoding URL strings.

The catch-all redirect sends any unmatched URL to `/dashboard` rather than a 404 page. This is a pragmatic choice for a small app where all meaningful entry points are defined; it will be refined with route guards once authentication state is managed in a Pinia store.

Component imports use the `@` alias (resolved to `src/` by `vite.config.js`) so paths stay short and don't break when files are moved.

---

### Auth & page components — stubs

Each of the four components (`Signin.vue`, `Signup.vue`, `Signout.vue`, `Dashboard.vue`) is a minimal single-file component containing only a heading. They exist at this stage to satisfy the router imports — without them Vite would throw a module-not-found error at startup. The actual forms, API calls, and state management will be added in subsequent sessions.

The components are organised into two subdirectories:
- `src/components/auth/` — components that deal with the authentication flow
- `src/components/pages/` — top-level page components that are navigated to as routes

This separation keeps auth-specific logic isolated from general page components as the project grows.

---

## Common Commands

```bash
# Shell into the Vue.js container
docker compose exec vuejs-container sh

# Install dependencies (run once, or after updating package.json)
npm install

# Start the Vite dev server (also runs automatically via entrypoint.development.sh)
npm run dev -- --host=0.0.0.0 --port=5173

# Start all services (Laravel, Vue.js, MySQL, phpMyAdmin)
docker compose up --build
```
