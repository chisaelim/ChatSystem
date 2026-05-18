# ChatSystem — Password reset frontend

## Table of Contents

- [What Changed in Session 3.6-frontend](#what-changed-in-session-36-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/.env](#vuejs-appenv)
  - [vuejs-app/src/components/auth/ResetPassword.vue](#vuejs-appsrccomponentsauthResetPasswordvue)
  - [vuejs-app/src/components/auth/SetNewPassword.vue](#vuejs-appsrccomponentsauthSetNewPasswordvue)
  - [vuejs-app/src/functions/api/auth.js](#vuejs-appsrcfunctionsapiauthjs)
  - [vuejs-app/src/router/index.js](#vuejs-appsrcrouterindexjs)
  - [vuejs-app/src/components/auth/Signin.vue](#vuejs-appsrccomponentsauthSigninvue)
- [How Each File Works](#how-each-file-works)
  - [.env — frontend environment variables](#env--frontend-environment-variables)
  - [ResetPassword.vue — request password reset email page](#resetpasswordvue--request-password-reset-email-page)
  - [SetNewPassword.vue — set new password landing page](#setnewpasswordvue--set-new-password-landing-page)
  - [auth.js — reset-password API helper](#authjs--reset-password-api-helper)
  - [router/index.js — reset-password and set-new-password route registration](#routerindexjs--reset-password-and-set-new-password-route-registration)
  - [Signin.vue — forgot-password link](#signinvue--forgot-password-link)
- [Common Commands](#common-commands)

---

## What Changed in Session 3.6-frontend

Session 3.5 added the full password reset flow on the Laravel backend — two new `FormRequest` classes, `ResetPasswordNotification`, a `User` model override, and two new API routes — but the Vue.js frontend had no pages for requesting a reset link or entering a new password, and the sign-in page gave users no path to either. Session 3.6-frontend wires the frontend into that backend: `vuejs-app/.env` gains `VITE_APP_RESET_PASSWORD_URL` so the reset callback path is declared once and referenced everywhere; `vuejs-app/src/functions/api/auth.js` reads the new variable and exports a new `apiSendResetPasswordEmail` function; `vuejs-app/src/components/auth/ResetPassword.vue` is a new single-file component with an email form that calls `apiSendResetPasswordEmail` and shows a success or error modal; `vuejs-app/src/components/auth/SetNewPassword.vue` is a new single-file component that reads the `forwarded-url` query parameter on submit, POSTs the password fields directly to that backend URL via Axios, and redirects to sign-in on success; `vuejs-app/src/router/index.js` registers `/reset-password` and `/set-new-password` as unguarded routes pointing at the two new components; and `vuejs-app/src/components/auth/Signin.vue` gains a "Forgot your password?" footer link pointing to the new reset-password route.

| Area | Session 3.5 | Session 3.6-frontend |
|---|---|---|
| `vuejs-app/.env` | Has `VITE_APP_URL`, `VITE_APP_VERIFY_EMAIL_URL`, `VITE_APP_API_URL` | Adds `VITE_APP_RESET_PASSWORD_URL` (reset password callback path) |
| Reset password request page | Does not exist | New `ResetPassword.vue` accepts email input and calls `apiSendResetPasswordEmail` |
| Set new password page | Does not exist | New `SetNewPassword.vue` reads `forwarded-url` query param, POSTs password fields to the backend URL, redirects to sign-in on success |
| Reset password API helper (`auth.js`) | Does not exist | New `apiSendResetPasswordEmail(email)` export POSTs `{ email, callback_url }` to `/send/reset-password-email` |
| Router (`index.js`) | Has sign-in, sign-out, sign-up, verify-email routes | Adds `/reset-password` → `ResetPassword` (`auth.reset-password`) and `/set-new-password` → `SetNewPassword` (`auth.set-new-password`), both `guarded: false` |
| Sign-in page (`Signin.vue`) | Footer links to sign-up only | Adds "Forgot your password?" link pointing to `auth.reset-password` |

`vuejs-app/.env` existed from a previous session and was edited manually to add `VITE_APP_RESET_PASSWORD_URL`. `vuejs-app/src/components/auth/ResetPassword.vue` is a new file that did not exist before this session and was created manually. `vuejs-app/src/components/auth/SetNewPassword.vue` is a new file that did not exist before this session and was created manually. `vuejs-app/src/functions/api/auth.js` existed from a previous session and was edited manually to read the new env variable and export `apiSendResetPasswordEmail`. `vuejs-app/src/router/index.js` existed from a previous session and was edited manually to import both new components and register the two new routes. `vuejs-app/src/components/auth/Signin.vue` existed from a previous session and was edited manually to add the "Forgot your password?" footer link.

---

## File Contents

The labels below each heading tell you what action to take:
- **Created manually** — the file does not exist yet; create it and paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/.env`

> **Edited manually** — the file already exists; paste the block below to replace its contents with the new `VITE_APP_RESET_PASSWORD_URL` variable added below the existing verify-email URL.

```
VITE_APP_URL=http://localhost:5173
VITE_APP_VERIFY_EMAIL_URL=http://localhost:5173/verify/email
VITE_APP_RESET_PASSWORD_URL=http://localhost:5173/set-new-password


VITE_APP_API_URL=http://localhost:8000/api
```

---

### `vuejs-app/src/components/auth/ResetPassword.vue`

> **Created manually** — create this file at `src/components/auth/ResetPassword.vue` and paste the block below to define the page where a user submits their email to receive a password reset link.

```vue
<template>
  <div class="login-page">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <router-link to="/" class="h1"><b>Admin</b>LTE</router-link>
        </div>
        <div class="card-body">
          <p class="login-box-msg">Enter your email to receive a password reset link</p>
          <form @submit.prevent="sendResetPasswordEmail">
            <div class="input-group mb-3">
              <input v-model="user.email" :class="{ 'is-invalid': !!userError.email }" type="email" class="form-control"
                placeholder="Email" />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
              <div class="invalid-feedback">
                {{ userError.email }}
              </div>
            </div>
            <div class="row">
              <div class="col-8"></div>
              <div class="col-4">
                <button type="submit" class="btn btn-primary btn-block">Send Link</button>
              </div>
            </div>
          </form>

          <p class="mb-1">
            <router-link :to="{ name: 'auth.signin' }" class="text-center">Go back to login</router-link>
          </p>
          <p class="mb-0">
            <router-link :to="{ name: 'auth.signup' }" class="text-center">Register a new membership</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { reactive } from "vue";
import { apiSendResetPasswordEmail } from "@/functions/api/auth";
import { LoadingModal, MessageModal, CloseModal } from "@/functions/swal";

const user = reactive({
  email: "",
});

const userError = reactive({
  email: "",
});

const defaultUser = JSON.parse(JSON.stringify(user));
const defaultUserError = JSON.parse(JSON.stringify(userError));

function resetAllState() {
  Object.assign(user, defaultUser);
  Object.assign(userError, defaultUserError);
}

async function sendResetPasswordEmail() {
  try {
    LoadingModal();
    const response = await apiSendResetPasswordEmail(user.email);
    resetAllState();
    return MessageModal({ icon: "success", title: "Success", text: response.data.message });
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

### `vuejs-app/src/components/auth/SetNewPassword.vue`

> **Created manually** — create this file at `src/components/auth/SetNewPassword.vue` and paste the block below to define the page that receives the password reset link from the email and submits the new password to the Laravel backend.

```vue
<template>
  <div class="login-page">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <router-link to="/" class="h1"><b>Admin</b>LTE</router-link>
        </div>
        <div class="card-body">
          <p class="login-box-msg">Enter your new password</p>
          <form @submit.prevent="setNewPassword">
            <div class="input-group mb-3">
              <input v-model="user.password" type="password" class="form-control"
                :class="{ 'is-invalid': !!userError.password }" placeholder="Password" autocomplete />
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
              <input v-model="user.password_confirmation" type="password" class="form-control"
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
                <button type="submit" class="btn btn-primary btn-block">Reset</button>
              </div>
            </div>
          </form>
          <p class="mb-1">
            <router-link :to="{ name: 'auth.signin' }" class="text-center">Go back to login</router-link>
          </p>
          <p class="mb-0">
            <router-link :to="{ name: 'auth.signup' }" class="text-center">Register a new membership</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from "axios";
import Swal from "sweetalert2";
import { reactive } from "vue";
import { useRoute, useRouter } from "vue-router";
import { LoadingModal, MessageModal, CloseModal } from "@/functions/swal";
const route = useRoute();
const router = useRouter();

const user = reactive({
  password: "",
  password_confirmation: "",
});

const userError = reactive({
  password: "",
});

const defaultUser = JSON.parse(JSON.stringify(user));
const defaultUserError = JSON.parse(JSON.stringify(userError));

function resetAllState() {
  Object.assign(user, defaultUser);
  Object.assign(userError, defaultUserError);
}

async function setNewPassword() {
  try {
    LoadingModal('Setting new password...');
    const response = await axios.post(new URL(route.query['forwarded-url']), user);
    resetAllState();
    await MessageModal({ icon: "success", title: "Success", text: response.data.message }, () => {
      router.push({ name: 'auth.signin' });
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

### `vuejs-app/src/functions/api/auth.js`

> **Edited manually** — the file already exists from a previous session; paste the block below to add `APP_RESET_PASSWORD_URL` from the new env variable and export the new `apiSendResetPasswordEmail` function.

```js
import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;
const APP_VERIFY_EMAIL_URL = import.meta.env.VITE_APP_VERIFY_EMAIL_URL;
const APP_RESET_PASSWORD_URL = import.meta.env.VITE_APP_RESET_PASSWORD_URL;

export async function apiSignUp(user) {
  return await axios.post(APP_API_URL + '/signup', {
    ...user,
    callback_url: APP_VERIFY_EMAIL_URL,
  });
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
export async function apiSendVerificationEmail(email) {
  return await axios.post(APP_API_URL + '/send/verification-email', { email, callback_url: APP_VERIFY_EMAIL_URL });
}
export async function apiSendResetPasswordEmail(email) {
  return await axios.post(APP_API_URL + '/send/reset-password-email', { email, callback_url: APP_RESET_PASSWORD_URL });
}
```

---

### `vuejs-app/src/router/index.js`

> **Edited manually** — the file already exists from a previous session; paste the block below to add the `ResetPassword` and `SetNewPassword` imports and register the two new routes.

```js
import ResetPassword from '@/components/auth/ResetPassword.vue';
import SetNewPassword from '@/components/auth/SetNewPassword.vue';
import Signin from '@/components/auth/Signin.vue';
import Signout from '@/components/auth/Signout.vue';
import Signup from '@/components/auth/Signup.vue';
import VerifyEmail from '@/components/auth/VerifyEmail.vue';
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
      path: '/verify/email',
      name: 'auth.verify.email',
      component: VerifyEmail,
      meta: { guarded: false },
    },
    {
      path: '/reset-password',
      name: 'auth.reset-password',
      component: ResetPassword,
      meta: { guarded: false },
    },
    {
      path: '/set-new-password',
      name: 'auth.set-new-password',
      component: SetNewPassword,
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

### `vuejs-app/src/components/auth/Signin.vue`

> **Edited manually** — the file already exists from a previous session; paste the block below to add the "Forgot your password?" footer link pointing to `auth.reset-password`.

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
          <p class="mb-1">
            <router-link :to="{ name: 'auth.signup' }" class="text-center">Register a new membership</router-link>
          </p>
          <p class="mb-0">
            <router-link :to="{ name: 'auth.reset-password' }" class="text-center">Forgot your password?</router-link>
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

## How Each File Works

### `.env` — frontend environment variables

`VITE_APP_RESET_PASSWORD_URL` is the full path of the new `SetNewPassword` page (`http://localhost:5173/set-new-password`). This value is imported in `auth.js` as `APP_RESET_PASSWORD_URL` and passed as `callback_url` in `apiSendResetPasswordEmail`. The Laravel backend embeds this URL in the reset email's action link, appending the signed backend reset URL as a `forwarded-url` query parameter — the same mechanism used by email verification in session 3.3 — so that when the user clicks the link their browser lands on the `SetNewPassword` page with the full backend URL available for extraction.

---

### `ResetPassword.vue` — request password reset email page

The component's template shows a standard AdminLTE card with a single email input, a "Send Link" submit button, and two footer links (sign-in and sign-up). The email field binds to `user.email` and renders an `is-invalid` class if `userError.email` is non-empty.

In `<script setup>`, `sendResetPasswordEmail` calls `apiSendResetPasswordEmail(user.email)` wrapped in the same `LoadingModal` / `MessageModal` / `CloseModal` pattern used throughout the auth components. On success the form state is reset via `resetAllState()` and a success modal shows the backend's response message. On a 422 response, validation errors are mapped onto `userError.email` and the modal is closed so the inline error is visible. Any other Axios error triggers an error modal with the response message or the raw `error.message` if no response is available.

---

### `SetNewPassword.vue` — set new password landing page

The component's template shows a password and confirm-password input, a "Reset" submit button, and the same sign-in / sign-up footer links as `ResetPassword.vue`. Only the `password` field has inline validation display; `password_confirmation` has no matching error key.

In `<script setup>`, `setNewPassword` reads `route.query['forwarded-url']` — the percent-encoded backend URL placed there by the email action link (`http://localhost:8000/api/set/new-password?token=...&email=...`) — wraps it in `new URL()`, and calls `axios.post(new URL(route.query['forwarded-url']), user)`. Because the `token` and `email` are in the query string of the backend URL and Laravel's `Request` object merges query string parameters with the request body, `SetNewPasswordRequest`'s validation rules for `token` and `email` resolve correctly even though those fields are not in the POST body. On success, `resetAllState()` clears the form and `MessageModal` is awaited with a callback that calls `router.push({ name: 'auth.signin' })`, redirecting the user to sign-in after dismissing the modal. On a 422 response, `userError.password` is populated from `error.response.data.errors`. On any other error, the raw response message or `error.message` is shown in an error modal.

---

### `auth.js` — reset-password API helper

`VITE_APP_RESET_PASSWORD_URL` is imported from `import.meta.env` at the module level alongside the existing `APP_VERIFY_EMAIL_URL` and `APP_API_URL` constants.

`apiSendResetPasswordEmail(email)` — a new named export that POSTs `{ email, callback_url: APP_RESET_PASSWORD_URL }` to `/send/reset-password-email`, matching the `SendResetPasswordEmailRequest` validation rules added in session 3.5. The function is consumed by `ResetPassword.vue`'s `sendResetPasswordEmail` handler.

---

### `router/index.js` — reset-password and set-new-password route registration

The file gains two imports (`ResetPassword` and `SetNewPassword`) and two route objects:

```js
{
  path: '/reset-password',
  name: 'auth.reset-password',
  component: ResetPassword,
  meta: { guarded: false },
},
{
  path: '/set-new-password',
  name: 'auth.set-new-password',
  component: SetNewPassword,
  meta: { guarded: false },
}
```

Both routes use `guarded: false` so the navigation guard does not redirect unauthenticated users away from them — a user must be able to reach both pages without being signed in. The `/set-new-password` path matches `VITE_APP_RESET_PASSWORD_URL` exactly; keeping them in sync ensures the `forwarded-url` the email delivers leads to the correct component.

---

### `Signin.vue` — forgot-password link

A single `<p>` element is appended below the existing "Register a new membership" link:

```html
<p class="mb-0">
  <router-link :to="{ name: 'auth.reset-password' }" class="text-center">Forgot your password?</router-link>
</p>
```

No logic changes are made to the component; the `<script setup>` block is identical to the previous session.

---

## Common Commands

```bash
# Restart the Vue.js dev server to reload updated .env variables
docker compose restart vuejs-container

# Start all services
docker compose up --build
```
