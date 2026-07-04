# ChatSystem — Profile management UI, auth API bindings, and token-aware request flow

## Table of Contents

- [What Changed in Session 4.4-frontend](#what-changed-in-session-44-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/src/stores/user.js](#vuejs-appsrcstoresuserjs)
  - [vuejs-app/src/main.js](#vuejs-appsrcmainjs)
  - [vuejs-app/src/functions/api/auth.js](#vuejs-appsrcfunctionsapiauthjs)
  - [vuejs-app/src/components/includes/LeftSidebar.vue](#vuejs-appsrccomponentsincludesleftsidebarvue)
  - [vuejs-app/src/components/auth/Profile.vue](#vuejs-appsrccomponentsauthprofilevue)
- [How Each File Works](#how-each-file-works)
  - [package-lock.json — dependency lock refresh](#package-lockjson--dependency-lock-refresh)
  - [user.js — extended persisted user state](#userjs--extended-persisted-user-state)
  - [main.js and auth.js — centralized bearer token injection and new profile/password API helpers](#mainjs-and-authjs--centralized-bearer-token-injection-and-new-profilepassword-api-helpers)
  - [LeftSidebar.vue — thumbnail-aware avatar fallback](#leftsidebarvue--thumbnail-aware-avatar-fallback)
  - [Profile.vue — image upload workflow and password settings form](#profilevue--image-upload-workflow-and-password-settings-form)
- [Common Commands](#common-commands)

---

## What Changed in Session 4.4-frontend

Session 4.3 introduced backend APIs for password creation/change and profile image management. Session 4.4-frontend connects those backend capabilities to the Vue application by extending the persisted user store with image and password-state fields, centralizing token injection with an Axios request interceptor, adding frontend API helpers for password and profile-image endpoints, updating the sidebar avatar source to prefer the thumbnail URL, and expanding the profile screen with full image upload/delete/reset controls plus password settings UX.

| Area | Session 4.3 | Session 4.4-frontend |
|---|---|---|
| Backend/Frontend boundary | Backend endpoints and resource fields were added | Frontend now consumes those fields and endpoints end-to-end |
| User state model | Basic identity values were persisted in Pinia | Adds `profile_image`, `profile_thumbnail`, and `password_null` |
| Auth request handling | Verification helper accepted manual token input | Axios interceptor now injects token automatically; verify call is simplified |
| Profile page | Static profile card with placeholder avatar | Interactive image pipeline (crop/resize/upload/delete/reset) and password settings form |
| Sidebar avatar | Always showed static placeholder image | Uses `profile_thumbnail` with fallback placeholder |

`vuejs-app/package-lock.json` existed previously and was modified by command after dependency installation. `vuejs-app/src/stores/user.js` existed previously and was edited manually to persist profile-image and password-state fields. `vuejs-app/src/main.js` existed previously and was edited manually to initialize the store with Pinia and register an Axios request interceptor before guarded-route verification. `vuejs-app/src/functions/api/auth.js` existed previously and was edited manually to add password/profile-image API helpers and simplify `apiVerify` token handling. `vuejs-app/src/components/includes/LeftSidebar.vue` existed previously and was edited manually to render `userStore.profile_thumbnail` with a placeholder fallback. `vuejs-app/src/components/auth/Profile.vue` existed previously and was edited manually to add password form logic, modal feedback, image preprocessing, and profile-image save/delete workflows.

---

## File Contents

The labels below each heading tell you what action to take:
- **Modified by command** — the file already exists; run the command shown to let tooling update it.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/stores/user.js`

> **Edited manually** — replace the store module with the block below to persist profile image URLs and password-state metadata in user state.

```js
import { defineStore } from 'pinia';

export const useUserStore = defineStore('user',
  {
    state: () => ({
      id: null,
      name: null,
      email: null,
      profile_image: null,
      profile_thumbnail: null,
      password_null: true,
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
        this.profile_image = user.profile_image;
        this.profile_thumbnail = user.profile_thumbnail;
        this.password_null = user.password_null;
      },
      resetState() {
        this.id = null;
        this.name = null;
        this.email = null;
        this.profile_image = null;
        this.profile_thumbnail = null;
        this.password_null = true;
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

### `vuejs-app/src/main.js`

> **Edited manually** — replace the bootstrap file with the block below to register the Axios request interceptor and simplify guarded-route verification calls.

```js
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import App from './App.vue'
import router from './router'
import axios from 'axios';
import { useUserStore } from '@/stores/user';
import { apiVerify } from '@/functions/api/auth';

const app = createApp(App)

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);
app.use(pinia);
app.use(router);
app.mount('#app');


const userStore = useUserStore();
// Set up Axios interceptor to add Authorization header dynamically
// Only when the token is available and not already set in the request
axios.interceptors.request.use((config) => {
  const token = userStore.getSanctumToken();
  if (token && !config.headers.Authorization) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

router.beforeEach(async (to, from) => {
  const { guarded } = to.meta;
  if (guarded === undefined) { // if the route is not guarded, we don't need to verify the token
    return;
  }

  try {
    const response = await apiVerify();
    const { data } = response;
    userStore.setState(data.user);
  } catch (error) {
    userStore.reset();
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

### `vuejs-app/src/functions/api/auth.js`

> **Edited manually** — replace the auth API helper module with the block below to support create/change password and update/delete profile image endpoints.

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
export async function apiSignOut(token) { // !!!!! can not be overwrite by axios interceptor since we need to remove the token before the request is sent
  return await axios.post(APP_API_URL + '/signout', null, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  });
}
export async function apiVerify() { // can be overwrite by axios interceptor
  return await axios.get(APP_API_URL + '/verify');
}
export async function apiSendVerificationEmail(email) {
  return await axios.post(APP_API_URL + '/send/verification-email', { email, callback_url: APP_VERIFY_EMAIL_URL });
}
export async function apiSendResetPasswordEmail(email) {
  return await axios.post(APP_API_URL + '/send/reset-password-email', { email, callback_url: APP_RESET_PASSWORD_URL });
}
export async function apiCreatePassword(new_password, new_password_confirmation) {
  return await axios.put(APP_API_URL + '/create/password', { new_password, new_password_confirmation });
}
export async function apiChangePassword(current_password, new_password, new_password_confirmation) {
  return await axios.put(APP_API_URL + '/change/password', { current_password, new_password, new_password_confirmation });
}
export async function apiUpdateProfileImage(image) {
  const formData = new FormData();
  formData.append('profile_image', image);
  formData.append('_method', 'PUT');
  return await axios.post(APP_API_URL + '/update/profile-image', formData);
}
export async function apiDeleteProfileImage() {
  return await axios.delete(APP_API_URL + '/delete/profile-image');
}
```

---

### `vuejs-app/src/components/includes/LeftSidebar.vue`

> **Edited manually** — replace the sidebar component with the block below so the user panel prefers the profile thumbnail URL and falls back to the placeholder image.

```vue
<template>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <router-link to="/" class="brand-link">
      <img :src="logoImage" alt="Chat System Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Chat System</span>
    </router-link>

    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img :src="userStore.profile_thumbnail || emptyImage" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <router-link :to="{ name: 'profile' }" class="d-block">{{ userStore.name }}</router-link>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
        <div class="sidebar-search-results">
          <div class="list-group"><a href="#" class="list-group-item">
              <div class="search-title"><strong class="text-light"></strong>N<strong
                  class="text-light"></strong>o<strong class="text-light"></strong> <strong
                  class="text-light"></strong>e<strong class="text-light"></strong>l<strong
                  class="text-light"></strong>e<strong class="text-light"></strong>m<strong
                  class="text-light"></strong>e<strong class="text-light"></strong>n<strong
                  class="text-light"></strong>t<strong class="text-light"></strong> <strong
                  class="text-light"></strong>f<strong class="text-light"></strong>o<strong
                  class="text-light"></strong>u<strong class="text-light"></strong>n<strong
                  class="text-light"></strong>d<strong class="text-light"></strong>!<strong class="text-light"></strong>
              </div>
              <div class="search-path"></div>
            </a></div>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <router-link :to="{ name: 'dashboard' }" active-class="active" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </router-link>
          </li>
        </ul>
      </nav>
    </div>
  </aside>
</template>
<script setup>
import emptyImage from '@/assets/images/emptyImage.png';
import logoImage from '@/assets/images/logoImage.webp';
import { useUserStore } from '@/stores/user';

const userStore = useUserStore();
</script>
```

---

### `vuejs-app/src/components/auth/Profile.vue`

> **Edited manually** — replace the profile component with the block below to add image upload/delete/reset actions, password update form handling, and backend API integration.

```vue
<template>
  <div class="content-wrapper" style="min-height: 1416px">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Profile</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link :to="{ name: 'dashboard' }">Home</router-link>
              </li>
              <li class="breadcrumb-item active">Profile</li>
            </ol>
          </div>
        </div>
      </div>
      <!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-4">
            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid img-circle" :src="tempImage" alt="User profile picture" />
                  <input @change="onChangeImage" type="file" class="d-none"
                    :accept="allowedExtensions.map((ext) => '.' + ext).join(', ')" id="file-input" />
                  <div class="mt-1">
                    <label :for="'file-input'">
                      <a type="button" class="m-1 btn btn-primary btn-sm"><i class="fas fa-upload"></i></a>
                    </label>
                    <a type="button" @click="onDeleteImage()" class="m-1 btn btn-danger btn-sm"><i
                        class="fas fa-trash"></i></a>
                    <a type="button" @click="onResetImage()" class="m-1 btn btn-secondary btn-sm"><i
                        class="fas fa-undo-alt"></i></a>
                    <a v-if="imageChanged" type="button" @click="saveProfileImage()"
                      class="m-1 btn btn-success btn-sm"><i class="fas fa-check"></i></a>
                  </div>
                </div>

                <h3 class="profile-username text-center">{{ userStore.name }}</h3>

                <p class="text-muted text-center">{{ userStore.email }}</p>
              </div>
            </div>
          </div>
          <div class="col-md-8">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item">
                    <a class="nav-link active" href="#password_settings" data-toggle="tab">Password Settings</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="password_settings">
                    <form @submit.prevent="savePassword" class="form-horizontal">
                      <div v-if="!userStore.password_null" class="form-group row">
                        <label class="col-sm-3 col-form-label">Current Password</label>
                        <div class="col-sm-9">
                          <input v-model="user.current_password" type="password" class="form-control"
                            placeholder="Current Password" :class="!!userError.current_password ? 'is-invalid' : ''" />
                          <div class="invalid-feedback">
                            {{ userError.current_password }}
                          </div>
                        </div>
                      </div>
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">New Password</label>
                        <div class="col-sm-9">
                          <input v-model="user.new_password" type="password" class="form-control"
                            placeholder="New Password" :class="!!userError.new_password ? 'is-invalid' : ''" />
                          <div class="invalid-feedback">
                            {{ userError.new_password }}
                          </div>
                        </div>
                      </div>
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Confirm Password</label>
                        <div class="col-sm-9">
                          <input v-model="user.new_password_confirmation" type="password" class="form-control"
                            placeholder="Confirm Password" />
                        </div>
                      </div>

                      <div class="form-group row">
                        <div class="offset-sm-2 col-sm-10">
                          <button type="reset" class="mx-3 btn btn-danger">Cancel</button>
                          <button type="submit" class="mx-3 btn btn-outline-primary">
                            Save
                          </button>
                        </div>
                      </div>
                    </form>
                  </div>
                  <!-- /.tab-pane -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import emptyImage from "@/assets/images/emptyImage.png";
import { CloseModal, LoadingModal, MessageModal } from "@/functions/swal";
import { useRouter } from "vue-router";
import { computed, reactive, ref, watch } from "vue";
import {
  apiChangePassword,
  apiCreatePassword,
  apiDeleteProfileImage,
  apiUpdateProfileImage,
} from "@/functions/api/auth";
import { useUserStore } from "@/stores/user";
const router = useRouter();
const userStore = useUserStore();

const user = reactive({
  current_password: "",
  new_password: "",
  new_password_confirmation: "",
});

const userError = reactive({
  current_password: "",
  new_password: "",
});

const defaultUser = JSON.parse(JSON.stringify(user));
const defaultUserError = JSON.parse(JSON.stringify(userError));

function resetAllState() {
  Object.assign(user, defaultUser);
  Object.assign(userError, defaultUserError);
}

async function savePassword() {
  try {
    LoadingModal('Saving password...');
    const response = userStore.password_null
      ? await apiCreatePassword(
        user.new_password,
        user.new_password_confirmation
      )
      : await apiChangePassword(
        user.current_password,
        user.new_password,
        user.new_password_confirmation
      );
    resetAllState();
    await MessageModal({ icon: "success", title: "Success", text: response.data.message, }, () => router.push({ name: "auth.signin" }));
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

const tempImage = ref(null);
const selectedImageFile = ref(null);
const profileImage = computed(() => userStore.profile_image);
watch(
  () => profileImage.value,
  (nv) => (tempImage.value = nv ?? emptyImage),
  { immediate: true }
);

const imageChanged = computed(
  () => tempImage.value !== (profileImage.value ?? emptyImage)
);
const allowedExtensions = ["jpg", "jpeg", "png"];

function onChangeImage(event) {
  const files = event.target.files;
  if (files && files.length > 0) {
    const extFile = files[0].name.split(".").pop()?.toLowerCase();
    if (!allowedExtensions.includes(extFile)) {
      return MessageModal({ icon: "error", title: "Error", text: "Only jpg/jpeg and png files are allowed!" });
    }
    const reader = new FileReader();
    reader.onloadend = function () {
      const img = new Image();
      img.onload = function () {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");

        // Set canvas size to 454x454
        canvas.width = 454;
        canvas.height = 454;

        // Calculate crop dimensions (center crop)
        const size = Math.min(img.width, img.height);
        const x = (img.width - size) / 2;
        const y = (img.height - size) / 2;

        // Draw image cropped and resized to 454x454
        ctx.drawImage(img, x, y, size, size, 0, 0, 454, 454);

        canvas.toBlob((blob) => {
          if (!blob) {
            return MessageModal({ icon: "error", title: "Error", text: "Failed to process image. Please try again." });
          }

          selectedImageFile.value = new File([blob], "profile.png", { type: "image/png" });
          tempImage.value = canvas.toDataURL("image/png");
        }, "image/png");
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(files[0]);
    event.target.value = null;
  }
}
function onDeleteImage() {
  selectedImageFile.value = null;
  tempImage.value = emptyImage;
}
function onResetImage() {
  selectedImageFile.value = null;
  tempImage.value = userStore.profile_image ? userStore.profile_image : emptyImage;
}

async function saveProfileImage() {
  try {
    LoadingModal('Saving profile image...');
    const isDeleting = tempImage.value === emptyImage;
    const response = isDeleting
      ? await apiDeleteProfileImage()
      : await apiUpdateProfileImage(selectedImageFile.value);
    userStore.profile_image = isDeleting ? null : response.data.profile_image;
    userStore.profile_thumbnail = isDeleting ? null : response.data.profile_thumbnail;
    selectedImageFile.value = null;
    await MessageModal({ icon: "success", title: "Success", text: response.data.message });
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}
</script>
```

---

## How Each File Works

### `user.js` — extended persisted user state

The user store now persists backend-provided profile URLs and `password_null`, so components can immediately decide whether to show current-password input and which profile image/thumbnail to render without extra ad-hoc state.

---

### `main.js` and `auth.js` — centralized bearer token injection and new profile/password API helpers

`main.js` now creates `userStore` from the same Pinia instance and installs a global Axios request interceptor that injects `Authorization` when a token exists and a header is not already set. `auth.js` aligns with this by removing manual token plumbing from `apiVerify`, while still keeping explicit header control in `apiSignOut`. It also adds helper methods for create/change password and update/delete profile image endpoints.

---

### `LeftSidebar.vue` — thumbnail-aware avatar fallback

The sidebar user panel now renders `userStore.profile_thumbnail` first, falling back to the static placeholder image when no thumbnail exists. This keeps the compact sidebar avatar in sync with current profile-image settings.

---

### `Profile.vue` — image upload workflow and password settings form

The profile screen now combines two account workflows:
- Password settings: handles both create-password and change-password flows depending on `userStore.password_null`, maps backend 422 validation errors into field-level messages, and signs the user out by redirecting to sign-in after password changes.
- Profile image management: allows selecting a local image, validates extension, crops/resizes client-side to `454x454`, previews locally, and then either uploads via `apiUpdateProfileImage` or deletes via `apiDeleteProfileImage`, updating store image fields from API responses.

---

## Common Commands

```bash
# Start all services (Laravel + Vue.js + MySQL)
docker compose up

# Rebuild images and start all services (use after any Dockerfile or env file changes)
docker compose down && docker compose up --build

# Install npm dependencies inside the Vue.js container
docker exec -it vuejs-container npm install

# Open a shell in the Vue.js container to inspect the running environment
docker exec -it vuejs-container sh
```
