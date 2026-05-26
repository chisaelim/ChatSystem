# ChatSystem — AdminLTE authenticated layout shell with named router views and profile page

## Table of Contents

- [What Changed in Session 4.2-frontend](#what-changed-in-session-42-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/src/assets/images/emptyImage.png](#vuejs-appsrcassetsimagesemptyimagepng)
  - [vuejs-app/src/assets/images/logoImage.webp](#vuejs-appsrcassetsimageslogoimagewebp)
  - [vuejs-app/src/components/includes/Navbar.vue](#vuejs-appsrccomponentsincludesnavbarvue)
  - [vuejs-app/src/components/includes/LeftSidebar.vue](#vuejs-appsrccomponentsincludesleftsidebarvue)
  - [vuejs-app/src/components/includes/RightSidebar.vue](#vuejs-appsrccomponentsincludesrightsidebarvue)
  - [vuejs-app/src/components/includes/Footer.vue](#vuejs-appsrccomponentsincludesfootervue)
  - [vuejs-app/src/components/auth/Profile.vue](#vuejs-appsrccomponentsauthprofilevue)
  - [vuejs-app/src/components/pages/Dashboard.vue](#vuejs-appsrccomponentspagesdashboardvue)
  - [vuejs-app/src/App.vue](#vuejs-appsrcappvue)
  - [vuejs-app/src/router/index.js](#vuejs-appsrcrouterindexjs)
- [How Each File Works](#how-each-file-works)
  - [Image assets — static images for user avatar and brand logo](#image-assets--static-images-for-user-avatar-and-brand-logo)
  - [Navbar.vue — top navigation bar with sign-out button](#navbarvue--top-navigation-bar-with-sign-out-button)
  - [LeftSidebar.vue — collapsible sidebar with user panel and nav links](#leftsidebarvue--collapsible-sidebar-with-user-panel-and-nav-links)
  - [RightSidebar.vue — control sidebar placeholder](#rightsidebarvue--control-sidebar-placeholder)
  - [Footer.vue — main footer bar](#footervue--main-footer-bar)
  - [Profile.vue — user profile page with avatar and display name](#profilevue--user-profile-page-with-avatar-and-display-name)
  - [Dashboard.vue — dashboard page content area](#dashboardvue--dashboard-page-content-area)
  - [App.vue — named router-view slots for the layout shell](#appvue--named-router-view-slots-for-the-layout-shell)
  - [router/index.js — named component maps for authenticated routes](#routerindexjs--named-component-maps-for-authenticated-routes)
- [Common Commands](#common-commands)

---

## What Changed in Session 4.2-frontend

Session 4.1-frontend completed the authentication flow by wiring Google OAuth into the Vue.js frontend. Session 4.2-frontend introduces the post-login application shell: `vuejs-app/src/App.vue` is updated to declare five `<router-view>` outlets (default, `navbar`, `left_sidebar`, `right_sidebar`, `footer`) so that authenticated pages can mount layout chrome alongside their own content; four new `includes` components — `Navbar.vue`, `LeftSidebar.vue`, `RightSidebar.vue`, and `Footer.vue` — implement the AdminLTE chrome; two binary image assets (`emptyImage.png`, `logoImage.webp`) are added to support the avatar placeholder and brand logo; a new `Profile.vue` page is added under `auth/`; `Dashboard.vue` is updated to import the user store; and `router/index.js` is updated to import all four include components and `Profile`, convert the `dashboard` route from a single `component` key to a named `components` map, and add a `profile` route with its own named map.

| Area | Session 4.1-frontend | Session 4.2-frontend |
|---|---|---|
| App shell | Single `<router-view>` in `App.vue` | Five named `<router-view>` outlets for layout chrome |
| Layout components | None | `Navbar.vue`, `LeftSidebar.vue`, `RightSidebar.vue`, `Footer.vue` |
| Image assets | None | `emptyImage.png` (avatar placeholder), `logoImage.webp` (brand logo) |
| Pages | `Dashboard.vue` renders a plain content wrapper with no store access | `Dashboard.vue` imports user store; `Profile.vue` added with avatar and display name |
| Router | `dashboard` route uses single `component` key | `dashboard` and new `profile` routes use named `components` maps |

`vuejs-app/src/assets/images/emptyImage.png` and `vuejs-app/src/assets/images/logoImage.webp` are new binary files added manually to the assets folder. `vuejs-app/src/components/includes/Navbar.vue`, `vuejs-app/src/components/includes/LeftSidebar.vue`, `vuejs-app/src/components/includes/RightSidebar.vue`, and `vuejs-app/src/components/includes/Footer.vue` are new files created manually as the four AdminLTE chrome components. `vuejs-app/src/components/auth/Profile.vue` is a new file created manually as the profile page. `vuejs-app/src/components/pages/Dashboard.vue` existed from a previous session and was edited manually to add the user store import. `vuejs-app/src/App.vue` existed from a previous session and was edited manually to replace its single `<router-view>` with five named `<router-view>` tags. `vuejs-app/src/router/index.js` existed from a previous session and was edited manually to import `Profile` and the four include components, convert `dashboard` to a named `components` map, and register the `profile` route. `vuejs-app/package-lock.json` existed from a previous session and was modified by running `npm install`.

---

## File Contents

The labels below each heading tell you what action to take:
- **Created manually** — the file does not exist yet; create it at the path shown and paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.
- **Modified by command** — run the command shown; no manual edits needed.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/assets/images/emptyImage.png`

> **Created manually** — this file does not exist yet; copy the binary PNG file from the repository into `vuejs-app/src/assets/images/` — it serves as the placeholder avatar image throughout the UI.

This is a binary image file. Copy it directly from the repository — it cannot be reproduced by pasting text.

---

### `vuejs-app/src/assets/images/logoImage.webp`

> **Created manually** — this file does not exist yet; copy the binary WebP file from the repository into `vuejs-app/src/assets/images/` — it serves as the brand logo displayed in the sidebar header.

This is a binary image file. Copy it directly from the repository — it cannot be reproduced by pasting text.

---

### `vuejs-app/src/components/includes/Navbar.vue`

> **Created manually** — this file does not exist yet; create it at the path shown and paste the block below as the top navigation bar component with a sign-out confirmation dialog.

```vue
<template>
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
          <i class="fas fa-th-large"></i>
        </a>
      </li>
      <li class="nav-item">
        <a @click="signOut" class="nav-link" role="button">
          <i class="fas fa-sign-out-alt text-danger"></i>
        </a>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
const router = useRouter();

async function signOut() {
  await Swal.fire({
    title: 'Are you sure?',
    text: "You will be signed out from the system!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, sign me out!'
  }).then((result) => {
    if (result.isConfirmed) {
      return router.push({ name: 'auth.signout' });
    }
  });
}
</script>
```

---

### `vuejs-app/src/components/includes/LeftSidebar.vue`

> **Created manually** — this file does not exist yet; create it at the path shown and paste the block below as the collapsible sidebar component with user panel, sidebar search, and navigation links.

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
          <img :src="emptyImage" class="img-circle elevation-2" alt="User Image">
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

### `vuejs-app/src/components/includes/RightSidebar.vue`

> **Created manually** — this file does not exist yet; create it at the path shown and paste the block below as the control-sidebar placeholder component.

```vue
<template>
  <aside class="control-sidebar control-sidebar-dark" style="display: none;">
    <!-- Control sidebar content goes here -->
    <div class="p-3">
      <h5>Title</h5>
      <p>Content</p>
    </div>
  </aside>
</template>
```

---

### `vuejs-app/src/components/includes/Footer.vue`

> **Created manually** — this file does not exist yet; create it at the path shown and paste the block below as the main footer bar component.

```vue
<template>
  <footer class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
      Anything you want
    </div>
    <!-- Default to the left -->
    <strong>Copyright © 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong> All rights reserved.
  </footer>
</template>
```

---

### `vuejs-app/src/components/auth/Profile.vue`

> **Created manually** — this file does not exist yet; create it at the path shown and paste the block below as the profile page displaying the authenticated user's avatar placeholder and display name.

```vue
<template>
  <div class="content-wrapper" style="min-height: 1175px;">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Profile</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Profile</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-6">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid img-circle" :src="emptyImage" alt="User profile picture">
                </div>
                <h3 class="profile-username text-center">{{ userStore.name }}</h3>
                <!-- <p class="text-muted text-center">{{ userStore.level }}</p> -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { useUserStore } from "@/stores/user";
import emptyImage from '@/assets/images/emptyImage.png';
const userStore = useUserStore();
</script>
```

---

### `vuejs-app/src/components/pages/Dashboard.vue`

> **Edited manually** — the file already exists; paste the block below to replace its contents with the user store import added to the script setup block.

```vue
<template>
  <div class="content-wrapper" style="min-height: 1175px;">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
    <div class="content">
      <div class="container-fluid">

      </div>
    </div>
  </div>
</template>
<script setup>
import { useUserStore } from "@/stores/user";
const userStore = useUserStore();
</script>
```

---

### `vuejs-app/src/App.vue`

> **Edited manually** — the file already exists; paste the block below to replace its contents, adding the four named `<router-view>` outlets alongside the default outlet so layout chrome components mount per route.

```vue
<script setup></script>

<template>
  <router-view name="navbar"></router-view>
  <router-view name="left_sidebar"></router-view>
  <router-view></router-view>
  <router-view name="right_sidebar"></router-view>
  <router-view name="footer"></router-view>
</template>

<style scoped></style>
```

---

### `vuejs-app/src/router/index.js`

> **Edited manually** — the file already exists; paste the block below to replace its contents with `Profile`, `Navbar`, `LeftSidebar`, `RightSidebar`, and `Footer` imported, the `dashboard` route converted to a named `components` map, and the new `profile` route added.

```js
import Profile from '@/components/auth/Profile.vue';
import ResetPassword from '@/components/auth/ResetPassword.vue';
import SetNewPassword from '@/components/auth/SetNewPassword.vue';
import Signin from '@/components/auth/Signin.vue';
import Signout from '@/components/auth/Signout.vue';
import Signup from '@/components/auth/Signup.vue';
import VerifyEmail from '@/components/auth/VerifyEmail.vue';
import GoogleOAuth from '@/components/google-oauth/GoogleOAuth.vue';
import Dashboard from '@/components/pages/Dashboard.vue';
import { createRouter, createWebHistory } from 'vue-router';


import Navbar from "@/components/includes/Navbar.vue";
import LeftSidebar from "@/components/includes/LeftSidebar.vue";
import RightSidebar from "@/components/includes/RightSidebar.vue";
import Footer from "@/components/includes/Footer.vue";

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
      components: {
        default: Dashboard,
        navbar: Navbar,
        left_sidebar: LeftSidebar,
        right_sidebar: RightSidebar,
        footer: Footer,
      },
      meta: { guarded: true },
    },
    {
      path: '/profile',
      name: 'profile',
      components: {
        default: Profile,
        navbar: Navbar,
        left_sidebar: LeftSidebar,
        rightSidebar: RightSidebar,
        footer: Footer,
      },
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

### Image assets — static images for user avatar and brand logo

`emptyImage.png` is a small PNG used as the default avatar in two places: the user panel in `LeftSidebar.vue` (the `<img>` beside the user's display name) and the profile picture card in `Profile.vue`. Both components import it as an ES module (`import emptyImage from '@/assets/images/emptyImage.png'`) and bind it to `:src`, which lets Vite fingerprint and cache-bust the file at build time.

`logoImage.webp` is the brand mark displayed in the sidebar header beside "Chat System". `LeftSidebar.vue` imports it as `logoImage` and binds it to the `<img>` in the `.brand-link` anchor at the top of the sidebar.

---

### `Navbar.vue` — top navigation bar with sign-out button

`Navbar.vue` renders the AdminLTE `.main-header` navigation bar. It provides three icon buttons: a menu toggle (`data-widget="pushmenu"`) that collapses or expands the left sidebar, a fullscreen toggle, and a control-sidebar toggle. The fourth icon — a red sign-out arrow — is a plain `<a>` element with an `@click` handler bound to `signOut`. The `signOut` function calls `Swal.fire` with a confirmation dialog; if the user confirms, `router.push({ name: 'auth.signout' })` navigates to the Signout route and the Signout component handles the actual token cleanup and store reset. No Pinia store is involved in this component directly.

---

### `LeftSidebar.vue` — collapsible sidebar with user panel and nav links

`LeftSidebar.vue` renders the AdminLTE `.main-sidebar`. The brand link at the top shows `logoImage` and the text "Chat System". The user panel below it shows `emptyImage` and a `<router-link>` to the `profile` route that displays `userStore.name` — so the sidebar always reflects the currently authenticated user's display name. The sidebar search widget is included as static AdminLTE markup with its `data-widget="sidebar-search"` hook for the AdminLTE JavaScript to initialise. The nav section contains a single `<router-link>` to `dashboard`, with `active-class="active"` so Vue Router applies the `.active` class automatically when that route is matched.

---

### `RightSidebar.vue` — control sidebar placeholder

`RightSidebar.vue` renders the AdminLTE `.control-sidebar` element with `display: none` to keep it hidden by default. It contains placeholder title and paragraph text. The AdminLTE `data-widget="control-sidebar"` attribute on Navbar's toggle button will show or hide it via the AdminLTE JavaScript plugin. No reactive state or props are needed.

---

### `Footer.vue` — main footer bar

`Footer.vue` renders the AdminLTE `.main-footer` bar with the standard copyright notice linking to AdminLTE.io and a right-floated "Anything you want" placeholder string. No reactive state, props, or imports are needed.

---

### `Profile.vue` — user profile page with avatar and display name

`Profile.vue` is the content component for the `/profile` route. It renders the standard AdminLTE `.content-wrapper` page shell with a breadcrumb header and a single profile card. The card displays `emptyImage` as the profile picture and `userStore.name` as the username heading. A commented-out paragraph for `userStore.level` is retained as a placeholder for a future role or level field. The component imports `useUserStore` from `@/stores/user` and `emptyImage` from the assets folder; no other logic is needed at this stage.

---

### `Dashboard.vue` — dashboard page content area

`Dashboard.vue` renders the standard AdminLTE `.content-wrapper` with a Dashboard heading and an empty fluid content area. The `useUserStore` import and `const userStore = useUserStore()` are added in this session so the component has the store available for future use (for example, rendering a personalised welcome message); `userStore` is not yet referenced in the template.

---

### `App.vue` — named router-view slots for the layout shell

The root `App.vue` now declares five `<router-view>` elements. Vue Router maps each named outlet to the component registered under the matching key in a route's `components` object:

| Outlet | Name attribute | Receives on authenticated routes |
|---|---|---|
| Default | *(none)* | `Dashboard`, `Profile`, `Signin`, etc. |
| Top bar | `navbar` | `Navbar` |
| Left chrome | `left_sidebar` | `LeftSidebar` |
| Right chrome | `right_sidebar` | `RightSidebar` |
| Bottom bar | `footer` | `Footer` |

Routes that use a single `component` key (all auth routes — sign-in, sign-up, email verify, password reset, Google OAuth callback) only fill the default outlet; the named outlets render nothing for those routes, so the chrome components are invisible on all unauthenticated pages.

---

### `router/index.js` — named component maps for authenticated routes

The `dashboard` route's `component: Dashboard` is replaced with a `components` map whose keys match the named `<router-view>` outlets in `App.vue` (`default`, `navbar`, `left_sidebar`, `right_sidebar`, `footer`). A new `profile` route at `/profile` uses the same include components with `Profile` as the default component; note that its right-sidebar key is spelled `rightSidebar` (camelCase) rather than `right_sidebar` (snake_case), matching the named outlet as specified. All other routes retain a single `component` key and therefore only populate the default outlet. `Profile`, `Navbar`, `LeftSidebar`, `RightSidebar`, and `Footer` are imported at the top of the file alongside the existing imports. Both `dashboard` and `profile` carry `meta: { guarded: true }` so the navigation guard continues to redirect unauthenticated users away from those routes.

---

## Common Commands

```bash
# Start all services (Laravel + Vue.js + MySQL)
docker compose up

# Rebuild images and start all services (use after any Dockerfile or env file changes)
docker compose down && docker compose up --build

# Install npm dependencies inside the Vue.js container
docker exec -it vuejs-container npm install

# Tail Vue.js container logs to verify the Vite dev server started correctly
docker logs vuejs-container --tail 50

# Open a shell in the Vue.js container to inspect the running environment
docker exec -it vuejs-container sh
```
