# ChatSystem — Email verification frontend

## Table of Contents

- [What Changed in Session 3.4-frontend](#what-changed-in-session-34-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/.env](#vuejs-appenv)
  - [vuejs-app/src/components/auth/VerifyEmail.vue](#vuejs-appsrccomponentsauthverifyemailvue)
  - [vuejs-app/src/router/index.js](#vuejs-appsrcrouterindexjs)
  - [vuejs-app/src/functions/api/auth.js](#vuejs-appsrcfunctionsapiauthjs)
  - [vuejs-app/src/components/auth/Signup.vue](#vuejs-appsrccomponentsauthSignupvue)
- [How Each File Works](#how-each-file-works)
  - [.env — frontend environment variables](#env--frontend-environment-variables)
  - [VerifyEmail.vue — email verification landing page](#verifyemailvue--email-verification-landing-page)
  - [router/index.js — verify-email route registration](#routerindexjs--verify-email-route-registration)
  - [auth.js — callback_url and resend API helper](#authjs--callback_url-and-resend-api-helper)
  - [Signup.vue — post-sign-up verification prompt](#signupvue--post-sign-up-verification-prompt)
- [Common Commands](#common-commands)

---

## What Changed in Session 3.4-frontend

Session 3.3 added a complete email verification layer to the Laravel backend — custom notification, signed-URL generation, `MustVerifyEmail` trait, two new API endpoints — leaving the Vue.js frontend with no mechanism to receive the verification callback, pass a `callback_url` to the backend, or let a user request a resend. Session 3.4-frontend wires the frontend into that backend: `vuejs-app/.env` gains `VITE_APP_URL` and `VITE_APP_VERIFY_EMAIL_URL` so the verification callback path is declared once and referenced everywhere; `vuejs-app/src/functions/api/auth.js` reads the new variable and injects `callback_url` into every sign-up POST, and exports a new `apiSendVerificationEmail` function; `vuejs-app/src/components/auth/VerifyEmail.vue` is a new single-file component that reads the `forwarded-url` query parameter on mount, calls the Laravel signed URL directly via Axios, and renders a contextual success or error alert; `vuejs-app/src/router/index.js` registers `/verify/email` as an unguarded route pointing at that component; and `vuejs-app/src/components/auth/Signup.vue` drops the post-sign-up redirect to sign-in, instead capturing the signed-up email address and revealing a "Resend Verification Email" button that calls the new API helper.

| Area | Session 3.3 | Session 3.4-frontend |
|---|---|---|
| `vuejs-app/.env` | Only `VITE_APP_API_URL` | Adds `VITE_APP_URL` (frontend base URL) and `VITE_APP_VERIFY_EMAIL_URL` (verification callback path) |
| Sign-up API body (`auth.js`) | Posts `name`, `email`, `password`, `password_confirmation` only | Spreads the user object and appends `callback_url: APP_VERIFY_EMAIL_URL` |
| Resend verification API | Does not exist | New `apiSendVerificationEmail(email)` export POSTs `{ email, callback_url }` to `/send/verification-email` |
| Sign-up page behaviour (`Signup.vue`) | Redirects to `auth.signin` on success | Stays on page; shows signed-up email and a "Resend Verification Email" button |
| Email verification page | Does not exist | New `VerifyEmail.vue` reads `forwarded-url` query param, calls the signed URL, shows alert |
| Router (`index.js`) | No `/verify/email` route | Adds `/verify/email` → `VerifyEmail`, `name: 'auth.verify.email'`, `guarded: false` |

`vuejs-app/.env` existed from a previous session and was edited manually to add the two new `VITE_` environment variables. `vuejs-app/src/components/auth/VerifyEmail.vue` is a new file that did not exist before this session and was created manually. `vuejs-app/src/router/index.js` existed from a previous session and was edited manually to import `VerifyEmail` and register the `/verify/email` route. `vuejs-app/src/functions/api/auth.js` existed from a previous session and was edited manually to read the new env variable, add `callback_url` to `apiSignUp`, and export `apiSendVerificationEmail`. `vuejs-app/src/components/auth/Signup.vue` existed from a previous session and was edited manually to remove the post-success redirect, add the `signedUpEmail` ref, and add the resend-verification block and handler.

---

## File Contents

The labels below each heading tell you what action to take:
- **Created manually** — the file does not exist yet; create it and paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/.env`

> **Edited manually** — the file already exists; paste the block below to replace its contents with the two new frontend environment variables prepended above the existing API URL.

```
VITE_APP_URL=http://localhost:5173
VITE_APP_VERIFY_EMAIL_URL=http://localhost:5173/verify/email


VITE_APP_API_URL=http://localhost:8000/api
```

---

### `vuejs-app/src/components/auth/VerifyEmail.vue`

> **Created manually** — create this file at `src/components/auth/VerifyEmail.vue` and paste the block below to define the page that receives the verification link from the email and calls the Laravel signed URL.

```vue
<template>
  <div class="login-page">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <router-link to="/" class="h1"><b>Admin</b>LTE</router-link>
        </div>
        <div class="card-body">
          <div v-if="status" :class="{
            'alert alert-success': status === 'success',
            'alert alert-danger': status === 'error'
          }" role="alert">
            {{ message }}
          </div>
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
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { LoadingModal, CloseModal } from "@/functions/swal";
const route = useRoute();

const status = ref(null);
const message = ref("");

onMounted(async () => {
  try {
    LoadingModal('Verifying email...');
    const response = await axios.get(new URL(route.query['forwarded-url']));
    status.value = "success";
    message.value = response.data.message;
  } catch (error) {
    status.value = "error";
    message.value = error.response?.data?.message || error.message || "An error occurred during email verification.";
  } finally {
    return CloseModal();
  }
});
</script>
```

---

### `vuejs-app/src/router/index.js`

> **Edited manually** — the file already exists from a previous session; paste the block below to add the `VerifyEmail` import and register the `/verify/email` route.

```js
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

### `vuejs-app/src/functions/api/auth.js`

> **Edited manually** — the file already exists from a previous session; paste the block below to read `VITE_APP_VERIFY_EMAIL_URL`, add `callback_url` to `apiSignUp`, and export the new `apiSendVerificationEmail` function.

```js
import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;
const APP_VERIFY_EMAIL_URL = import.meta.env.VITE_APP_VERIFY_EMAIL_URL;

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
```

---

### `vuejs-app/src/components/auth/Signup.vue`

> **Edited manually** — the file already exists from a previous session; paste the block below to remove the post-success redirect and add the resend-verification prompt and handler.

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
import { useRouter } from "vue-router";
import { reactive, ref } from "vue";
import { apiSignUp, apiSendVerificationEmail } from "@/functions/api/auth";
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
</script>
```

---

## How Each File Works

### `.env` — frontend environment variables

Two variables are added to the Vite env file.

`VITE_APP_URL` declares the frontend's own origin (`http://localhost:5173`). It is not consumed by any component in this session but documents the base URL of the Vue dev server alongside the other `VITE_` declarations.

`VITE_APP_VERIFY_EMAIL_URL` is the full path of the new `VerifyEmail` page (`http://localhost:5173/verify/email`). This value is imported in `auth.js` as `APP_VERIFY_EMAIL_URL` and passed as `callback_url` in both `apiSignUp` and `apiSendVerificationEmail`. The Laravel backend embeds this URL in the verification email's action link, appending the signed backend URL as a `forwarded-url` query parameter, so that when the user clicks the link their browser lands on the `VerifyEmail` Vue page with the full signed URL available for extraction.

---

### `VerifyEmail.vue` — email verification landing page

The component's template shows a standard AdminLTE card with two footer links (sign-in and sign-up) and a conditional alert block. The alert is hidden while `status` is `null`; once set, it renders `alert-success` or `alert-danger` using Vue's object class binding, and displays the `message` string.

In `<script setup>`, `onMounted` `LoadingModal` is called immediately to show a spinner, then `axios.get(new URL(route.query['forwarded-url']))` fires the GET request directly to the Laravel backend (hitting the `GET /verify/email/{id}/{hash}?signature=...` route). On success, `status` and `message` are set from the response. On any Axios error, `status` is set to `'error'` and `message` is extracted with a chain of optional fallbacks (`response.data.message`, `error.message`, generic string) to handle both network errors and backend 4xx responses. `CloseModal()` runs unconditionally in the `finally` block to dismiss the loader.

---

### `router/index.js` — verify-email route registration

The file gains one import (`VerifyEmail` from `@/components/auth/VerifyEmail.vue`) and one route object:

```js
{
  path: '/verify/email',
  name: 'auth.verify.email',
  component: VerifyEmail,
  meta: { guarded: false },
}
```

`guarded: false` ensures the existing navigation guard (added in a previous session) does not redirect unauthenticated users away from this page — they must be able to reach it immediately after clicking the email link without being signed in.

---

### `auth.js` — `callback_url` and resend API helper

`VITE_APP_VERIFY_EMAIL_URL` is imported from `import.meta.env` at the module level. Two changes follow.

`apiSignUp` — the plain `user` argument is replaced by an object spread (`...user`) that appends `callback_url: APP_VERIFY_EMAIL_URL`. This satisfies the `callback_url` validation rule added to `SignupRequest` in session 3.3 and ensures the verification email that Laravel dispatches on sign-up uses the correct frontend callback path.

`apiSendVerificationEmail(email)` — a new named export that POSTs `{ email, callback_url: APP_VERIFY_EMAIL_URL }` to `/send/verification-email`, matching the `SendVerificationEmailRequest` validation rules from session 3.3. The function is consumed by `Signup.vue`'s `sendVerificationEmail` handler.

---

### `Signup.vue` — post-sign-up verification prompt

Three interrelated changes are made to the component.

**Template** — a `<hr>` and a conditional `<div v-if="signedUpEmail">` block are appended below the sign-in link. When `signedUpEmail` is non-empty the block renders the email address and a "Resend Verification Email" `<button>` that calls `sendVerificationEmail` on click.

**`signUp` function** — `resetSignedUpEmail()` is called at the top of `signUp` so any email from a previous attempt is cleared before the next try. After a successful `apiSignUp` call, `signedUpEmail.value = user.email` captures the address before `resetAllState()` wipes the reactive `user` object. The `MessageModal` callback that previously called `router.replace({ name: 'auth.signin' })` is removed, so the user remains on the sign-up page and can see the verification prompt and resend button.

**`sendVerificationEmail` function** — calls `apiSendVerificationEmail(signedUpEmail.value)`, shows a loading modal while the request is in flight, then presents a success modal with the backend's response message on success or an error modal on failure. `resetSignedUpEmail` is a one-liner (`signedUpEmail.value = ""`) that hides the resend block.

---

## Common Commands

```bash
# Start all services
docker compose up --build

# Restart the Vue.js dev server to reload updated .env variables
docker compose restart vuejs-container

# Shell into the Laravel container (if backend changes are also needed)
docker compose exec laravel-container bash
```
