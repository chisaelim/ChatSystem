# ChatSystem — Google OAuth sign-in and sign-up in the Vue.js frontend

## Table of Contents

- [What Changed in Session 4.1-frontend](#what-changed-in-session-41-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/.env](#vuejs-appenv)
  - [vuejs-app/src/functions/api/google-oauth.js](#vuejs-appsrcfunctionsapigoogle-oauthjs)
  - [vuejs-app/src/components/google-oauth/GoogleOAuth.vue](#vuejs-appsrccomponentsgoogle-oauthgoogleoauthvue)
  - [vuejs-app/src/components/auth/Signin.vue](#vuejs-appsrccomponentsauthsigninvue)
  - [vuejs-app/src/components/auth/Signup.vue](#vuejs-appsrccomponentsauthsignupvue)
  - [vuejs-app/src/router/index.js](#vuejs-appsrcrouterindexjs)
- [How Each File Works](#how-each-file-works)
  - [.env — Google OAuth callback URL env var](#env--google-oauth-callback-url-env-var)
  - [google-oauth.js — API helper functions for the Google OAuth flow](#google-oauthjs--api-helper-functions-for-the-google-oauth-flow)
  - [GoogleOAuth.vue — OAuth callback landing page](#googleoauthvue--oauth-callback-landing-page)
  - [Signin.vue — Sign-in page with Google button](#signinvue--sign-in-page-with-google-button)
  - [Signup.vue — Sign-up page with Google button](#signupvue--sign-up-page-with-google-button)
  - [router/index.js — Google OAuth callback route](#routerindexjs--google-oauth-callback-route)
- [Common Commands](#common-commands)

---

## What Changed in Session 4.1-frontend

Session 4 added backend support for Google OAuth via Laravel Socialite, exposing three API endpoints (`/google/oauth/redirect`, `/google/oauth/callback`, `/google/oauth/exchange/token`). Session 4.1-frontend wires the Vue.js frontend to those endpoints: `vuejs-app/.env` gains `VITE_APP_GOOGLE_OAUTH_CALLBACK_URL` so the API helpers know which URL Google should redirect the browser back to; a new `vuejs-app/src/functions/api/google-oauth.js` module provides `apiGoogleOAuthRedirect` and `apiGoogleOAuthExchangeToken` helper functions; a new `vuejs-app/src/components/google-oauth/GoogleOAuth.vue` component handles the OAuth callback page by reading the short-lived token from the URL query string and exchanging it for a permanent Sanctum token; `vuejs-app/src/components/auth/Signin.vue` and `vuejs-app/src/components/auth/Signup.vue` each gain a "Sign in/up with Google" button that calls `apiGoogleOAuthRedirect` and redirects the browser to Google's authorization page; and `vuejs-app/src/router/index.js` registers the `/google/oauth/callback` route pointing to the new `GoogleOAuth` component.

| Area | Session 4 | Session 4.1-frontend |
|---|---|---|
| Google OAuth support | Backend API endpoints only | Adds Vue.js frontend UI and API helper module |
| `vuejs-app/.env` | Has `VITE_APP_URL`, `VITE_APP_VERIFY_EMAIL_URL`, `VITE_APP_RESET_PASSWORD_URL`, `VITE_APP_API_URL` | Adds `VITE_APP_GOOGLE_OAUTH_CALLBACK_URL` |
| API helper modules | `vuejs-app/src/functions/api/auth.js` handles email/password flows | New `vuejs-app/src/functions/api/google-oauth.js` handles OAuth redirect and token exchange |
| Components | `Signin.vue` and `Signup.vue` have email/password forms only | Both gain a "Sign in/up with Google" button; new `GoogleOAuth.vue` handles the callback |
| Router | Routes for sign-in, sign-up, email verify, password reset, dashboard | Adds `/google/oauth/callback` route pointing to `GoogleOAuth` |

`vuejs-app/.env` existed from a previous session and was edited manually to append `VITE_APP_GOOGLE_OAUTH_CALLBACK_URL`. `vuejs-app/src/functions/api/google-oauth.js` is a new file created manually with `apiGoogleOAuthRedirect` and `apiGoogleOAuthExchangeToken`. `vuejs-app/src/components/google-oauth/GoogleOAuth.vue` is a new file created manually as the OAuth callback landing page. `vuejs-app/src/components/auth/Signin.vue` existed from a previous session and was edited manually to add the "Sign in with Google" button and `googleSignIn` handler. `vuejs-app/src/components/auth/Signup.vue` existed from a previous session and was edited manually to add the "Sign up with Google" button and `googleSignUp` handler. `vuejs-app/src/router/index.js` existed from a previous session and was edited manually to import `GoogleOAuth` and register the `/google/oauth/callback` route.

---

## File Contents

The labels below each heading tell you what action to take:
- **Created manually** — the file does not exist yet; create it at the path shown and paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/.env`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `VITE_APP_GOOGLE_OAUTH_CALLBACK_URL` appended below the existing reset-password URL variable.

```
VITE_APP_URL=http://localhost:5173
VITE_APP_VERIFY_EMAIL_URL=http://localhost:5173/verify/email
VITE_APP_RESET_PASSWORD_URL=http://localhost:5173/set-new-password
VITE_APP_GOOGLE_OAUTH_CALLBACK_URL=http://localhost:5173/google/oauth/callback


VITE_APP_API_URL=http://localhost:8000/api
```

---

### `vuejs-app/src/functions/api/google-oauth.js`

> **Created manually** — this file does not exist yet; create it at the path shown and paste the block below with the two OAuth API helper functions.

```js
import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

const APP_GOOGLE_OAUTH_CALLBACK_URL = import.meta.env.VITE_APP_GOOGLE_OAUTH_CALLBACK_URL;

export async function apiGoogleOAuthRedirect() {
  try {
    return await axios.get(APP_API_URL + '/google/oauth/redirect', { params: { callback_url: APP_GOOGLE_OAUTH_CALLBACK_URL } });
  } catch (error) {
    throw error;
  }
}
export async function apiGoogleOAuthExchangeToken(token) {
  try {
    return await axios.post(APP_API_URL + '/google/oauth/exchange/token', null, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
  } catch (error) {
    throw error;
  }
}
```

---

### `vuejs-app/src/components/google-oauth/GoogleOAuth.vue`

> **Created manually** — this file does not exist yet; create it at the path shown and paste the block below as the OAuth callback landing page component.

```vue
<template></template>
<script setup>
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { LoadingModal, MessageModal, CloseModal } from "@/functions/swal";
import { apiGoogleOAuthExchangeToken } from "@/functions/api/google-oauth";
import { useUserStore } from "@/stores/user";
import { useRouter } from 'vue-router';

const userStore = useUserStore();
const router = useRouter();
const route = useRoute();

onMounted(async () => {
  try {
    LoadingModal("Processing Google authentication...");
    const error = route.query.error;
    if (error === 'google_oauth_failed') {
      return MessageModal({ icon: "error", title: "Error", text: "Google authentication failed. Please try again." }, () => {
        return router.replace({ name: 'auth.signin' });
      });
    }

    const token = route.query.token;
    const response = await apiGoogleOAuthExchangeToken(token);
    userStore.setState(response.data.user);
    userStore.setSanctumToken(response.data.token);
    CloseModal();
    return router.replace({ name: 'dashboard' });
  } catch (e) {
    return MessageModal({ icon: "error", title: "Error", text: "Google authentication failed. Please try again." }, () => {
      return router.replace({ name: 'auth.signin' });
    });
  }
});
</script>
```

---

### `vuejs-app/src/components/auth/Signin.vue`

> **Edited manually** — the file already exists; paste the block below to replace its contents with the "Sign in with Google" button and `googleSignIn` handler added in the social-auth-links section below the email/password form.

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
          <div class="social-auth-links text-center mt-3 mb-3">
            <p>- OR -</p>
            <button @click="googleSignIn()" class="btn btn-block btn-danger">
              <i class="fab fa-google mr-2"></i> Sign in with Google
            </button>
          </div>
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
import { apiGoogleOAuthRedirect } from "@/functions/api/google-oauth";

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

const googleSignIn = async () => {
  try {
    LoadingModal();
    const response = await apiGoogleOAuthRedirect();
    window.location.href = response.data.redirect_url;
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
  }
};
</script>
```

---

### `vuejs-app/src/components/auth/Signup.vue`

> **Edited manually** — the file already exists; paste the block below to replace its contents with the "Sign up with Google" button and `googleSignUp` handler added in the social-auth-links section below the registration form.

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
          <div class="social-auth-links text-center mt-3 mb-3">
            <p>- OR -</p>
            <button @click="googleSignUp()" class="btn btn-block btn-danger">
              <i class="fab fa-google mr-2"></i> Sign up with Google
            </button>
          </div>
          <p class="mb-1">
            <router-link :to="{ name: 'auth.signin' }" class="text-center">I already have an account</router-link>
          </p>
          <hr>
          <div v-if="signedUpEmail" class="mt-3">
            <p>Signed up with <strong>{{ signedUpEmail }}</strong></p>
            <p class="mb-3">
              Didn't receive the verification email?
            </p>
            <button @click="sendVerificationEmail" class="btn btn-secondary btn-block">Resend Verification
              Email</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from "vue";
import { apiSignUp, apiSendVerificationEmail } from "@/functions/api/auth";
import { LoadingModal, MessageModal, CloseModal } from "@/functions/swal";
import { apiGoogleOAuthRedirect } from "@/functions/api/google-oauth";

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
  resetSignedUpEmail();
  try {
    LoadingModal('Signing Up...');
    await apiSignUp(user);
    signedUpEmail.value = user.email;
    resetAllState();
    return MessageModal({
      icon: "success",
      title: "Success",
      text: "Your account has been created successfully."
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

const signedUpEmail = ref("");
async function sendVerificationEmail() {
  try {
    LoadingModal('Requesting verification email...');
    const response = await apiSendVerificationEmail(signedUpEmail.value);
    const { data } = response;
    return MessageModal({
      icon: "success",
      title: "Success",
      text: data.message
    });
  } catch (error) {
    const { response } = error;
    if (!response) {
      return MessageModal({ icon: "error", title: "Error", text: error.message });
    }
    const { data } = response;
    return MessageModal({ icon: "error", title: "Error", text: data.message });
  }
}
function resetSignedUpEmail() {
  signedUpEmail.value = "";
}

const googleSignUp = async () => {
  try {
    LoadingModal();
    const response = await apiGoogleOAuthRedirect();
    window.location.href = response.data.redirect_url;
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
  }
};
</script>
```

---

### `vuejs-app/src/router/index.js`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `GoogleOAuth` imported and its route registered at `/google/oauth/callback`.

```js
import ResetPassword from '@/components/auth/ResetPassword.vue';
import SetNewPassword from '@/components/auth/SetNewPassword.vue';
import Signin from '@/components/auth/Signin.vue';
import Signout from '@/components/auth/Signout.vue';
import Signup from '@/components/auth/Signup.vue';
import VerifyEmail from '@/components/auth/VerifyEmail.vue';
import GoogleOAuth from '@/components/google-oauth/GoogleOAuth.vue';
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
      path: '/google/oauth/callback',
      name: 'auth.google.oauth.callback',
      component: GoogleOAuth,
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

## How Each File Works

### `.env` — Google OAuth callback URL env var

`VITE_APP_GOOGLE_OAUTH_CALLBACK_URL` is the frontend URL that the Laravel backend will redirect the browser to after Google completes the OAuth authorization. Its value — `http://localhost:5173/google/oauth/callback` — matches the `/google/oauth/callback` route registered in the Vue Router. This variable is read at build time by Vite via `import.meta.env.VITE_APP_GOOGLE_OAUTH_CALLBACK_URL` in `google-oauth.js` and passed to the backend's `/google/oauth/redirect` endpoint as the `callback_url` query parameter. The backend base64-encodes this value into the OAuth `state` parameter so it survives the round-trip through Google and back.

---

### `google-oauth.js` — API helper functions for the Google OAuth flow

The module exports two functions that correspond to the first and third steps of the three-step OAuth flow introduced in Session 4.

**`apiGoogleOAuthRedirect`** — calls `GET /api/google/oauth/redirect` with `callback_url` set to `VITE_APP_GOOGLE_OAUTH_CALLBACK_URL`. The backend returns `{ redirect_url: "..." }` containing the full Google authorization URL. The caller is responsible for redirecting `window.location.href` to that URL.

**`apiGoogleOAuthExchangeToken`** — calls `POST /api/google/oauth/exchange/token` with the short-lived token passed in the `Authorization: Bearer` header. The token is read from the `?token=` query parameter that the Laravel callback route appended to the frontend callback URL. The backend deletes the short-lived token and returns a permanent Sanctum token together with the authenticated user resource.

---

### `GoogleOAuth.vue` — OAuth callback landing page

This component has an empty `<template>` — it renders nothing visible and exists solely to run logic in `onMounted`. When Google redirects the browser to `/google/oauth/callback`, Vue Router mounts this component and `onMounted` fires immediately.

The handler first checks for a `?error=google_oauth_failed` query parameter; if present, it shows an error modal and redirects the user back to the sign-in page. Otherwise it reads the `?token=` parameter and calls `apiGoogleOAuthExchangeToken` to swap the short-lived token for a permanent one. On success, `userStore.setState` and `userStore.setSanctumToken` are called to persist the authenticated user and token in the Pinia store, then the router replaces the current history entry with `/dashboard`. Any unexpected exception (network failure, expired token, etc.) is caught and also redirects to sign-in with an error modal, ensuring the user is never left stranded on a blank page.

---

### `Signin.vue` — Sign-in page with Google button

The existing email/password sign-in form is unchanged. A new `social-auth-links` `<div>` is added below the form with a red "Sign in with Google" button that calls `googleSignIn()` on click. The `googleSignIn` function shows a loading modal, calls `apiGoogleOAuthRedirect()`, and on success assigns the returned `redirect_url` to `window.location.href`, handing off browser navigation to Google's authorization page. The `apiGoogleOAuthRedirect` import is added to the `<script setup>` imports alongside the existing `apiSignIn` import. Error handling follows the same pattern as the rest of the component — a `MessageModal` call with the error message.

---

### `Signup.vue` — Sign-up page with Google button

The existing registration form is unchanged. A new `social-auth-links` `<div>` is added below the form with a red "Sign up with Google" button that calls `googleSignUp()` on click. The `googleSignUp` function is identical in logic to `googleSignIn` — it calls `apiGoogleOAuthRedirect()` and redirects to the returned URL. Both sign-in and sign-up use the same backend redirect endpoint because Google OAuth creates or retrieves the user account server-side; from the frontend's perspective both flows are identical after the button is clicked. The `apiGoogleOAuthRedirect` import is added alongside the existing `apiSignUp` and `apiSendVerificationEmail` imports.

---

### `router/index.js` — Google OAuth callback route

A new route entry is added for path `/google/oauth/callback` with the name `auth.google.oauth.callback` and the `GoogleOAuth` component. `meta: { guarded: false }` marks it as publicly accessible — the route must be reachable without a Sanctum token because the user arrives at it before authentication is complete. `GoogleOAuth` is imported at the top of the file alongside the other component imports. The route is positioned before the `/dashboard` guarded route in the `routes` array and before the catch-all redirect to prevent the catch-all from swallowing the callback URL.

---

## Common Commands

```bash
# Start all services (Laravel + Vue.js + MySQL)
docker compose up

# Rebuild images and start all services (use after any Dockerfile or env file changes)
docker compose down && docker compose up --build

# Tail Vue.js container logs to verify the Vite dev server started correctly
docker logs vuejs-container --tail 50

# Open a shell in the Vue.js container to inspect the running environment
docker exec -it vuejs-container sh

# Verify the VITE_APP_GOOGLE_OAUTH_CALLBACK_URL env var is available inside the container
docker exec vuejs-container printenv VITE_APP_GOOGLE_OAUTH_CALLBACK_URL
```
