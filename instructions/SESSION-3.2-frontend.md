# ChatSystem — Auth API integration, Pinia persistence & navigation guard

## Table of Contents

- [What Changed in Session 3.2-frontend](#what-changed-in-session-32-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/package.json](#vuejs-apppackagejson)
  - [vuejs-app/.env](#vuejs-appenv)
  - [vuejs-app/src/functions/api/auth.js](#vuejs-appsrcfunctionsapiauthjs)
  - [vuejs-app/src/functions/swal.js](#vuejs-appsrcfunctionsswaljs)
  - [vuejs-app/src/stores/user.js](#vuejs-appsrcstoresuserjs)
  - [vuejs-app/src/router/index.js](#vuejs-appsrcrouterindexjs)
  - [vuejs-app/src/main.js](#vuejs-appsrcmainjs)
  - [vuejs-app/src/components/auth/Signin.vue](#vuejs-appsrccomponentsauthSigninvue)
  - [vuejs-app/src/components/auth/Signup.vue](#vuejs-appsrccomponentsauthSignupvue)
  - [vuejs-app/src/components/auth/Signout.vue](#vuejs-appsrccomponentsauthSignoutvue)
  - [vuejs-app/src/components/pages/Dashboard.vue](#vuejs-appsrccomponentspagesDashboardvue)
- [How Each File Works](#how-each-file-works)
  - [New dependency stack](#new-dependency-stack)
  - [.env and VITE_APP_API_URL](#env-and-vite_app_api_url)
  - [functions/api/auth.js — axios API layer](#functionsapiauthjs--axios-api-layer)
  - [functions/swal.js — SweetAlert2 modal utilities](#functionsswaljs--sweetalert2-modal-utilities)
  - [stores/user.js — Pinia user store with persistence](#storesuserjs--pinia-user-store-with-persistence)
  - [main.js — pinia-plugin-persistedstate and navigation guard](#mainjs--pinia-plugin-persistedstate-and-navigation-guard)
  - [Signin.vue & Signup.vue — live forms with validation](#signinvue--signupvue--live-forms-with-validation)
  - [Signout.vue — side-effect component](#signoutvue--side-effect-component)
  - [Dashboard.vue — displaying persisted user data](#dashboardvue--displaying-persisted-user-data)
- [Common Commands](#common-commands)

---

## What Changed in Session 3.2-frontend

Session 3.1-frontend applied AdminLTE markup and installed its CSS/JS dependency stack, leaving the sign-in and sign-up forms as static HTML with no event handling. Session 3.2-frontend connects those forms to a real Laravel API — adding an axios-based API layer, SweetAlert2 loading and message modals, a Pinia user store with Sanctum token management and persisted state, a global navigation guard that verifies the token on every route change, and completing the sign-out component. The auth forms gain `v-model` bindings, `@submit.prevent` handlers, and inline 422 validation error display.

| Area | Session 3.1-frontend | Session 3.2-frontend |
|---|---|---|
| HTTP communication | None | `axios` installed; `functions/api/auth.js` defines `apiSignUp`, `apiSignIn`, `apiSignOut`, `apiVerify` |
| User feedback modals | None | `sweetalert2` installed; `functions/swal.js` provides `LoadingModal`, `MessageModal`, `CloseModal` |
| Pinia persistence | `pinia` only — no persistence plugin | `pinia-plugin-persistedstate` added; user store uses `persist: true` to survive page reloads |
| User store | Empty shell with `id`/`name`/`email` state only | Full store — `isAuthenticated` getter; `setState`, `resetState`, and Sanctum token management actions |
| Navigation guard | None — all routes accessible unconditionally | `router.beforeEach` in `main.js` calls `apiVerify` and redirects based on `route.meta.guarded` |
| Route meta | No guard meta on any route | `guarded: false` on sign-in/sign-up; `guarded: true` on dashboard; no meta on sign-out |
| `main.js` | Bootstrap/AdminLTE JS side-effect imports + Pinia + Router | Added `piniaPluginPersistedstate`, `useUserStore`, `apiVerify`; added `router.beforeEach` navigation guard |
| `Signin.vue` | Static form — no bindings or event handlers | Live form — `v-model`, `@submit.prevent`, `is-invalid` validation display, `signIn()` async handler |
| `Signup.vue` | Static form — no bindings or event handlers | Live form — `v-model`, `@submit.prevent`, `is-invalid` validation display, `signUp()` async handler |
| `Signout.vue` | `<h1>Signout</h1>` stub (unchanged in Session 3.1) | `onMounted` calls `apiSignOut`, removes Sanctum token, resets store, redirects to sign-in |
| `Dashboard.vue` | Bootstrap alert with hard-coded confirmation text and signout link | Displays `userStore.name` and `userStore.email` from the persisted Pinia store |
| `.env` | Does not exist | Created — sets `VITE_APP_API_URL=http://localhost:8000/api` for all axios requests |

`package.json` was updated automatically by the `npm install` commands that pulled in `axios`, `sweetalert2`, and `pinia-plugin-persistedstate`; it was not edited by hand. `vuejs-app/.env`, `src/functions/api/auth.js`, and `src/functions/swal.js` are new files that did not exist before this session and were created manually. `src/stores/user.js`, `src/router/index.js`, `src/main.js`, `src/components/auth/Signin.vue`, `src/components/auth/Signup.vue`, `src/components/auth/Signout.vue`, and `src/components/pages/Dashboard.vue` all existed from Session 3.1-frontend and were edited manually.

---

## File Contents

The labels below each heading tell you what action to take:
- **Modified by command** — run the install commands first; the file content block shows the resulting state after npm updates it automatically.
- **Created manually** — the file does not exist yet; create it and paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/package.json`

> **Modified by command** — run the install commands inside the Vue.js container to add axios, SweetAlert2, and pinia-plugin-persistedstate; the block below shows the resulting `package.json`.

```bash
# Inside the Vue.js container
npm install axios
npm install sweetalert2
npm install pinia-plugin-persistedstate
```

---

### `vuejs-app/.env`

> **Created manually** — create this file at the root of `vuejs-app/` and paste the block below to set the API base URL consumed by all axios requests.

```
VITE_APP_API_URL=http://localhost:8000/api
```

---

### `vuejs-app/src/functions/api/auth.js`

> **Created manually** — create this file at `src/functions/api/auth.js` and paste the block below to define all auth-related API calls.

```js
import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export async function apiSignUp(user) {
  return await axios.post(APP_API_URL + '/signup', user);
}
export async function apiSignIn(user) {
  return await axios.post(APP_API_URL + '/signin', user);
}
export async function apiSignOut(token) {
  return await axios.post(APP_API_URL + '/signout', null, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  });
}
export async function apiVerify(token) {
  return await axios.get(APP_API_URL + '/verify', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  });
}
```

---

### `vuejs-app/src/functions/swal.js`

> **Created manually** — create this file at `src/functions/swal.js` and paste the block below to define reusable SweetAlert2 modal helpers.

```js
import Swal from 'sweetalert2';

export const LoadingModal = (text = 'Loading...') => {
  return Swal.fire({
    text: text,
    allowOutsideClick: false,
    allowEscapeKey: false,
    preConfirm: () => false,
    width: '200px',
  }).then(Swal.showLoading());
}
export const MessageModal = async (options, callback) => {
  return await Swal.fire({
    ...options,
    showConfirmButton: false,
  }).then(async () => {
    if (typeof callback === "function") {
      return await callback();
    }
  })
}
export const CloseModal = () => {
  return Swal.close();
}
```

---

### `vuejs-app/src/stores/user.js`

> **Edited manually** — the file already exists from Session 3.0-frontend as an empty shell; paste the block below to replace it with the full user store including a getter, Sanctum token management actions, and Pinia persistence.

```js
import { defineStore } from 'pinia';

export const useUserStore = defineStore('user',
  {
    state: () => ({
      id: null,
      name: null,
      email: null,
    }),
    getters: {
      isAuthenticated: (state) => !!state.id,
    },
    actions: {
      // User state management
      setState(user) {
        this.id = user.id;
        this.name = user.name;
        this.email = user.email;
      },
      resetState() {
        this.id = null;
        this.name = null;
        this.email = null;
      },

      // User Sanctum Token management
      setSanctumToken(token) {
        localStorage.setItem('SANCTUM-TOKEN', token);
      },
      getSanctumToken() {
        return localStorage.getItem('SANCTUM-TOKEN');
      },
      removeSanctumToken() {
        localStorage.removeItem('SANCTUM-TOKEN');
      },

      // Reset user state and remove Sanctum token (e.g., on sign out)
      reset() {
        this.resetState();
        this.removeSanctumToken();
      },
    },
    persist: true,
  }
);
```

---

### `vuejs-app/src/router/index.js`

> **Edited manually** — the file already exists from Session 3.0-frontend; paste the block below to add `meta: { guarded }` flags to every route that the navigation guard inspects.

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
      meta: { guarded: false },
    },
    {
      path: '/signout',
      name: 'auth.signout',
      component: Signout,
      // This route has no guarded meta because it use for both authenticated and unauthenticated users.
      // The authentication state will be handled in the Signout component.
    },
    {
      path: '/signup',
      name: 'auth.signup',
      component: Signup,
      meta: { guarded: false },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: Dashboard,
      meta: { guarded: true },
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

### `vuejs-app/src/main.js`

> **Edited manually** — the file already exists from Session 3.1-frontend; paste the block below to wire up `pinia-plugin-persistedstate` and add the global `router.beforeEach` navigation guard.

```js
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import App from './App.vue'
import router from './router'
import { useUserStore } from '@/stores/user';
import { apiVerify } from '@/functions/api/auth';

const app = createApp(App)

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);
app.use(pinia);
app.use(router);
app.mount('#app');


const userStore = useUserStore();
router.beforeEach(async (to, from) => {
  const { guarded } = to.meta;
  if (guarded === undefined) { // if the route is not guarded, we don't need to verify the token
    return;
  }

  try {
    const token = userStore.getSanctumToken();
    const response = await apiVerify(token);
    const { data } = response;
    userStore.setState(data.user);
  } catch (error) {
    if (error.response && error.response.status === 401) {
      userStore.reset();
    }
  }

  if (guarded && !userStore.isAuthenticated) { // if the route is guarded and the user is not authenticated, redirect to signin page
    return { name: 'auth.signin' };
  }
  if (!guarded && userStore.isAuthenticated) { // if the route is not guarded and the user is authenticated, redirect to dashboard page
    return { name: 'dashboard' };
  }
});
```

---

### `vuejs-app/src/components/auth/Signin.vue`

> **Edited manually** — the file already exists from Session 3.1-frontend as a static AdminLTE form; paste the block below to replace it with a live form connected to the sign-in API.

```vue
<template>
  <div class="login-page">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <router-link to="/" class="h1"><b>Admin</b>LTE</router-link>
        </div>
        <div class="card-body">
          <p class="login-box-msg">Sign in to start your session</p>
          <form @submit.prevent="signIn">
            <div class="input-group mb-3">
              <input type="email" v-model="user.email" class="form-control" placeholder="Email"
                :class="{ 'is-invalid': !!userError.email }" />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
              <div class="invalid-feedback">
                {{ userError.email }}
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="password" v-model="user.password" class="form-control" placeholder="Password" autocomplete
                :class="{ 'is-invalid': !!userError.password }" />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
              <div class="invalid-feedback">
                {{ userError.password }}
              </div>
            </div>
            <div class="row">
              <div class="col-8"></div>
              <div class="col-4">
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
              </div>
            </div>
          </form>
          <p class="mb-0">
            <router-link :to="{ name: 'auth.signup' }" class="text-center">Register a new membership</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";
import { reactive } from "vue";
import { apiSignIn } from "@/functions/api/auth";
import { LoadingModal, MessageModal, CloseModal } from "@/functions/swal";
import { useUserStore } from "@/stores/user";
const router = useRouter();
const userStore = useUserStore();

const user = reactive({
  email: "",
  password: "",
});

const userError = reactive({
  email: "",
  password: "",
});

const defaultUser = JSON.parse(JSON.stringify(user));
const defaultUserError = JSON.parse(JSON.stringify(userError));

function resetAllState() {
  Object.assign(user, defaultUser);
  Object.assign(userError, defaultUserError);
}

async function signIn() {
  try {
    LoadingModal('Signing In...');
    const response = await apiSignIn(user);
    const { data } = response;
    userStore.setState(data.user);
    userStore.setSanctumToken(data.token);
    resetAllState();
    router.replace({ name: "dashboard" });
    return CloseModal();
  } catch (error) {
    const { response } = error;
    if (!response) {
      return MessageModal({ icon: "error", title: "Error", text: error.message });
    }
    const { status, data } = response;
    if (status === 422) {
      Object.keys(userError).forEach((key) => {
        userError[key] = data.errors[key]
          ? data.errors[key][0]
          : "";
      });
      return CloseModal();
    }
    return MessageModal({ icon: "error", title: "Error", text: data.message });
  }
}
</script>
```

---

### `vuejs-app/src/components/auth/Signup.vue`

> **Edited manually** — the file already exists from Session 3.1-frontend as a static AdminLTE form; paste the block below to replace it with a live form connected to the sign-up API.

```vue
<template>
  <div class="login-page">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <router-link to="/" class="h1"><b>Admin</b>LTE</router-link>
        </div>
        <div class="card-body">
          <p class="login-box-msg">Sign up for a new membership</p>
          <form @submit.prevent="signUp">
            <div class="input-group mb-3">
              <input type="text" v-model="user.name" class="form-control" placeholder="Name"
                :class="{ 'is-invalid': !!userError.name }" />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-user"></span>
                </div>
              </div>
              <div class="invalid-feedback">
                {{ userError.name }}
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="email" v-model="user.email" class="form-control" placeholder="Email"
                :class="{ 'is-invalid': !!userError.email }" />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
              <div class="invalid-feedback">
                {{ userError.email }}
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="password" v-model="user.password" class="form-control" placeholder="Password" autocomplete
                :class="{ 'is-invalid': !!userError.password }" />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
              <div class="invalid-feedback">
                {{ userError.password }}
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="password" v-model="user.password_confirmation" class="form-control"
                placeholder="Confirm Password" autocomplete />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-8"></div>
              <div class="col-4">
                <button type="submit" class="btn btn-primary btn-block">Sign up</button>
              </div>
            </div>
          </form>
          <p class="mb-1">
            <router-link :to="{ name: 'auth.signin' }" class="text-center">I already have an account</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";
import { reactive } from "vue";
import { apiSignUp } from "@/functions/api/auth";
import { LoadingModal, MessageModal, CloseModal } from "@/functions/swal";
const router = useRouter();

const user = reactive({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const userError = reactive({
  name: "",
  email: "",
  password: "",
});

const defaultUser = JSON.parse(JSON.stringify(user));
const defaultUserError = JSON.parse(JSON.stringify(userError));

function resetAllState() {
  Object.assign(user, defaultUser);
  Object.assign(userError, defaultUserError);
}

async function signUp() {
  try {
    LoadingModal('Signing Up...');
    await apiSignUp(user);
    resetAllState();
    return MessageModal({
      icon: "success",
      title: "Success",
      text: "Your account has been created successfully."
    },
      () => {
        router.replace({ name: "auth.signin" });
      });
  } catch (error) {
    const { response } = error;
    if (!response) {
      return MessageModal({ icon: "error", title: "Error", text: error.message });
    }
    const { status, data } = response;
    if (status === 422) {
      Object.keys(userError).forEach((key) => {
        userError[key] = data.errors[key]
          ? data.errors[key][0]
          : "";
      });
      return CloseModal();
    }
    return MessageModal({ icon: "error", title: "Error", text: data.message });
  }
}
</script>
```

---

### `vuejs-app/src/components/auth/Signout.vue`

> **Edited manually** — the file already exists from Session 3.1-frontend as an unchanged stub; paste the block below to replace it with the sign-out logic that clears all session state and redirects to sign-in.

```vue
<template></template>
<script setup>
import { apiSignOut } from "@/functions/api/auth";
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/user";
const router = useRouter();
const userStore = useUserStore();

onMounted(async () => {
  const token = userStore.getSanctumToken();
  apiSignOut(token); // no need to await since we will remove the token regardless of the response
  userStore.reset();
  router.replace({ name: "auth.signin" });
});
</script>
```

---

### `vuejs-app/src/components/pages/Dashboard.vue`

> **Edited manually** — the file already exists from Session 3.1-frontend as a Bootstrap alert; paste the block below to add `userStore.name` and `userStore.email` display from the persisted Pinia store.

```vue
<template>
  <div class="alert alert-success" role="alert">
    {{ userStore.name }}
    <br>
    {{ userStore.email }}
    <br>
    You are logged in successfully. This is your dashboard.
    <router-link :to="{ name: 'auth.signout' }"><i class="fas fa-sign-out-alt text-danger"></i></router-link>
  </div>
</template>
<script setup>
import { useUserStore } from "@/stores/user";
const userStore = useUserStore();
</script>
```

---

## How Each File Works

### New dependency stack

Three packages are added in this session to support HTTP communication, user feedback, and store persistence:

| Package | Role |
|---|---|
| `axios ^1.16.1` | Promise-based HTTP client used for all API calls; passes a `Bearer` token header for authenticated endpoints |
| `sweetalert2 ^11.26.24` | Modal library used for loading spinners and result messages during async operations |
| `pinia-plugin-persistedstate ^4.7.1` | Pinia plugin that serialises the user store to `localStorage` so authentication state survives page reloads |

---

### `.env` and `VITE_APP_API_URL`

Vite exposes variables prefixed with `VITE_` to the client at build time via `import.meta.env`. The single variable `VITE_APP_API_URL` holds the base URL of the Laravel API (`http://localhost:8000/api`). All four functions in `auth.js` construct their endpoint URLs by concatenating this value with a path segment. Changing the base URL in `.env` propagates to every API call without touching any source files.

---

### `functions/api/auth.js` — axios API layer

All four functions are thin wrappers around `axios`:

| Function | Method | Endpoint | Auth header |
|---|---|---|---|
| `apiSignUp(user)` | POST | `/signup` | No |
| `apiSignIn(user)` | POST | `/signin` | No |
| `apiSignOut(token)` | POST | `/signout` | `Bearer {token}` |
| `apiVerify(token)` | GET | `/verify` | `Bearer {token}` |

Each function is `async` and returns the resolved axios response object. Callers destructure `response.data` to access the payload. Axios rejects the promise for any non-2xx HTTP status, which is why all callers use `try/catch` to handle 422 validation errors and other failures separately.

---

### `functions/swal.js` — SweetAlert2 modal utilities

Three named exports standardise how modals appear across the application:

**`LoadingModal(text)`** — fires a narrow (200 px) modal with no confirm button, locked against outside-click and Escape dismissal, then immediately calls `showLoading()` to display the spinner. Used at the start of every `async` handler so the user sees feedback before the API call resolves.

**`MessageModal(options, callback)`** — fires a modal using the spread `options` object (which supplies `icon`, `title`, and `text`) with `showConfirmButton: false` so it auto-dismisses on outside click. An optional `callback` function is awaited after the modal closes — used in `signUp()` to navigate to the sign-in page only after the user has seen the success message.

**`CloseModal()`** — programmatically closes any open SweetAlert2 modal. Called after a 422 response to dismiss the loading spinner before the inline field errors are rendered.

---

### `stores/user.js` — Pinia user store with persistence

The store holds `id`, `name`, and `email` as nullable state fields. The `isAuthenticated` getter returns `true` only when `id` is non-null — this boolean is the single source of truth consumed by the navigation guard.

Actions are split into two groups:
- **State actions** (`setState`, `resetState`) — write or clear the three user fields.
- **Token actions** (`setSanctumToken`, `getSanctumToken`, `removeSanctumToken`) — read and write the `SANCTUM-TOKEN` key in `localStorage` directly. The Sanctum token is deliberately kept in `localStorage` rather than Pinia state so it is excluded from the Pinia serialisation snapshot and is not double-stored.
- **Reset action** (`reset`) — calls both `resetState()` and `removeSanctumToken()` to clear all user-related state in one step, used on sign-out.

`persist: true` instructs `pinia-plugin-persistedstate` to serialise the store's reactive state (`id`, `name`, `email`) to `localStorage` under the store id `"user"`. This means the user remains marked as authenticated across hard refreshes until `resetState()` is explicitly called.

---

### `main.js` — pinia-plugin-persistedstate and navigation guard

**Plugin wiring** — `createPinia()` is called first, then `pinia.use(piniaPluginPersistedstate)` registers the plugin before any store is accessed. If the plugin were registered after `app.use(pinia)` the hydrated state from `localStorage` would not be available on the first mount.

**`router.beforeEach` guard** — runs before every route change and implements a three-step flow:

1. **Skip unguarded routes** — if `to.meta.guarded === undefined` (the sign-out route), return immediately without making an API call.
2. **Verify the token** — call `apiVerify(token)`. On success, refresh the store with the latest user object from the server. On failure (network error, 401, expired token), swallow the error and let `isAuthenticated` remain at whatever value the persisted state holds.
3. **Redirect if needed** — if the target route is guarded and the user is not authenticated, redirect to `auth.signin`. If the target route is unguarded and the user is authenticated, redirect to `dashboard`.

The guard is registered **after** `app.mount('#app')` so the Pinia stores are fully initialised before `useUserStore()` is called outside a component context.

---

### `Signin.vue` & `Signup.vue` — live forms with validation

Both components follow the same pattern:

1. Two `reactive` objects — `user` (form field values) and `userError` (per-field error strings).
2. Deep-cloned defaults (`defaultUser`, `defaultUserError`) captured once at setup time so `resetAllState()` can restore a clean form without re-declaring the reactive objects.
3. The submit handler calls `LoadingModal` first, then awaits the API function. On success the store is updated and `router.replace` navigates away. On a 422 response, `CloseModal` dismisses the spinner and the `userError` fields are populated from `response.data.errors` — Vue's reactivity then adds the `is-invalid` CSS class and renders the error string inside `.invalid-feedback`.

**`Signin.vue`** additionally calls `userStore.setState(data.user)` and `userStore.setSanctumToken(data.token)` to persist the authenticated session before navigating to the dashboard.

**`Signup.vue`** does not update the store — after a successful registration the user is redirected to the sign-in page via the `MessageModal` callback so they explicitly authenticate.

---

### `Signout.vue` — side-effect component

The component has an empty `<template>` — it renders nothing. All logic runs in `onMounted`:

1. Retrieve the current Sanctum token from `localStorage`.
2. Call `apiSignOut(token)` **without** `await` — the call is fire-and-forget. The token is invalidated on the server if the network allows, but the client clears its state regardless of the response.
3. Call `userStore.reset()` to clear all user state and remove the Sanctum token from `localStorage`.
4. `router.replace({ name: "auth.signin" })` navigates to the sign-in page, replacing the sign-out entry in the history stack so the back button does not return to the sign-out route.

The sign-out route intentionally has no `meta.guarded` flag — an unauthenticated user who navigates to `/signout` directly will go through the same cleanup steps (all no-ops on empty state) and land on the sign-in page.

---

### `Dashboard.vue` — displaying persisted user data

The dashboard now injects `useUserStore()` and renders `userStore.name` and `userStore.email` directly from Pinia state. Because the store uses `persist: true`, these values survive a hard page refresh — the user does not see empty fields on reload. The sign-out link is unchanged from Session 3.1-frontend.

---

## Common Commands

```bash
# Shell into the Vue.js container
docker compose exec vuejs-container sh

# Install new dependencies for this session
npm install axios
npm install sweetalert2
npm install pinia-plugin-persistedstate

# Start the Vite dev server
npm run dev -- --host=0.0.0.0 --port=5173

# Start all services
docker compose up --build
```
