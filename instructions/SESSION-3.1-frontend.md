# ChatSystem — AdminLTE integration & auth form templates

## Table of Contents

- [What Changed in Session 3.1-frontend](#what-changed-in-session-31-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/package.json](#vuejs-apppackagejson)
  - [vuejs-app/src/main.css](#vuejs-appsrcmaincss)
  - [vuejs-app/index.html](#vuejs-appindexhtml)
  - [vuejs-app/src/main.js](#vuejs-appsrcmainjs)
  - [vuejs-app/src/components/auth/Signin.vue](#vuejs-appsrccomponentsauthSigninvue)
  - [vuejs-app/src/components/auth/Signup.vue](#vuejs-appsrccomponentsauthSignupvue)
  - [vuejs-app/src/components/pages/Dashboard.vue](#vuejs-appsrccomponentspagesDashboardvue)
- [How Each File Works](#how-each-file-works)
  - [AdminLTE dependency stack](#adminlte-dependency-stack)
  - [main.css — CSS import chain](#maincss--css-import-chain)
  - [index.html — AdminLTE structural requirements](#indexhtml--adminlte-structural-requirements)
  - [main.js — JS side-effects](#mainjs--js-side-effects)
  - [Signin.vue & Signup.vue — AdminLTE login forms](#signinvue--signupvue--adminlte-login-forms)
  - [Dashboard.vue — authenticated landing page](#dashboardvue--authenticated-landing-page)
- [Common Commands](#common-commands)

---

## What Changed in Session 3.1-frontend

Session 3.0-frontend established the Vue Router structure with four stub components containing only placeholder headings. Session 3.1-frontend integrates AdminLTE 3 as the UI framework — installing its dependency stack, wiring up its CSS and JS, applying its required HTML structure to `index.html`, and replacing the sign-in and sign-up stubs with fully-marked-up AdminLTE login forms. The dashboard stub is replaced with a minimal authenticated view.

| Area | Session 3.0-frontend | Session 3.1-frontend |
|---|---|---|
| UI framework | None — plain stub headings | AdminLTE 3 installed and wired up |
| `package.json` | Scaffold deps only (`vue`, `pinia`, `vue-router`) | Added `admin-lte`, `bootstrap`, `@fortawesome/fontawesome-free`, `icheck-bootstrap`, `husky` |
| `main.css` | Does not exist | Created — CSS import chain for Google Fonts, Font Awesome, iCheck Bootstrap, AdminLTE |
| `index.html` | Scaffold default — no CSS link, plain body and `#app` | Linked `main.css`; added `sidebar-mini layout-fixed` body classes; `wrapper` class on `#app` |
| `main.js` | Pinia + Router setup only | Added Bootstrap JS and AdminLTE JS imports before app bootstrap |
| `Signin.vue` | `<h1>Signin</h1>` stub | AdminLTE `.login-page` layout with email/password fields and a link to the sign-up page |
| `Signup.vue` | `<h1>Signup</h1>` stub | AdminLTE `.login-page` layout with name/email/password/confirm fields and a link back to sign-in |
| `Dashboard.vue` | `<h1>Dashboard</h1>` stub | Bootstrap success alert with a Font Awesome sign-out icon linking to the signout route |
| `Signout.vue` | `<h1>Signout</h1>` stub | Unchanged |

`package.json` was updated automatically by the `npm install` commands that pulled in the AdminLTE dependency stack; it was not edited by hand. `src/main.css` is a new file and was created manually. `index.html`, `src/main.js`, `src/components/auth/Signin.vue`, `src/components/auth/Signup.vue`, and `src/components/pages/Dashboard.vue` all existed from Session 3.0-frontend and were edited manually to apply AdminLTE markup and imports.

---

## File Contents

The labels below each heading tell you what action to take:
- **Modified by command, then manually edited** — run the install command first; the file content block shows the final state after npm updates it automatically.
- **Created manually** — the file does not exist yet; create it and paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/package.json`

> **Modified by command** — run the install commands inside the Vue.js container to add AdminLTE and its peer dependencies; the block below shows the resulting `package.json`.

```bash
# Inside the Vue.js container
npm install husky --save-dev
npm install admin-lte@^3.2 --save
npm install bootstrap@4.6.2 
npm install @fortawesome/fontawesome-free@5.15.4 
npm install icheck-bootstrap@3.0.1
```

---

### `vuejs-app/src/main.css`

> **Created manually** — create this file at `src/main.css` and paste the block below to set up the AdminLTE stylesheet import chain.

```css
@import url('https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback');
@import '@fortawesome/fontawesome-free/css/all.min.css';
@import 'icheck-bootstrap/icheck-bootstrap.min.css';
@import 'admin-lte/dist/css/adminlte.min.css';
```

---

### `vuejs-app/index.html`

> **Edited manually** — the file already exists from the scaffold; paste the block below to link the CSS file and add the AdminLTE-required classes to the body and app wrapper.

```html
<!DOCTYPE html>
<html lang="">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vite App</title>
    <link rel="stylesheet" href="/src/main.css">
</head>

<body class="sidebar-mini layout-fixed" style="height: auto;">
    <div id="app" class="wrapper">

    </div>
    <script type="module" src="/src/main.js"></script>
</body>

</html>
```

---

### `vuejs-app/src/main.js`

> **Edited manually** — the file already exists from the scaffold; paste the block below to add Bootstrap and AdminLTE JS imports before the Vue application is created.

```js
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
```

---

### `vuejs-app/src/components/auth/Signin.vue`

> **Edited manually** — the file already exists as a stub from Session 3.0-frontend; paste the block below to replace it with the AdminLTE sign-in form.

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
          <form>
            <div class="input-group mb-3">
              <input type="email" class="form-control" placeholder="Email" />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="password" class="form-control" placeholder="Password" autocomplete />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
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

<script setup></script>
```

---

### `vuejs-app/src/components/auth/Signup.vue`

> **Edited manually** — the file already exists as a stub from Session 3.0-frontend; paste the block below to replace it with the AdminLTE sign-up form.

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
          <form>
            <div class="input-group mb-3">
              <input type="text" class="form-control" placeholder="Name" />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-user"></span>
                </div>
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="email" class="form-control" placeholder="Email" />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="password" class="form-control" placeholder="Password" autocomplete />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="password" class="form-control" placeholder="Confirm Password" autocomplete />
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

<script setup></script>
```

---

### `vuejs-app/src/components/pages/Dashboard.vue`

> **Edited manually** — the file already exists as a stub from Session 3.0-frontend; paste the block below to replace it with a minimal authenticated view.

```vue
<template>
  <div class="alert alert-success" role="alert">
    You are logged in successfully. This is your dashboard.
    <router-link :to="{ name: 'auth.signout' }"><i class="fas fa-sign-out-alt text-danger"></i></router-link>
  </div>
</template>
```

---

## How Each File Works

### AdminLTE dependency stack

AdminLTE 3 is a Bootstrap 4-based admin panel template. It does not ship with Bootstrap itself — Bootstrap 4 is a peer dependency that must be installed alongside it. The full dependency chain installed in this session is:

| Package | Role |
|---|---|
| `admin-lte ^3.2.0` | The AdminLTE theme — CSS layout, login page, card and component styles |
| `bootstrap ^4.6.2` | AdminLTE's peer dependency — grid system, utilities, and JS components |
| `@fortawesome/fontawesome-free ^5.15.4` | Icon set used throughout AdminLTE for form icons, nav icons, and action buttons |
| `icheck-bootstrap ^3.0.1` | Styled checkbox and radio inputs compatible with AdminLTE's login forms |
| `husky ^9.1.7` | Git hook runner added as a dev dependency for enforcing pre-commit workflows |

All packages land in `node_modules/` which is bind-mounted into the container; the entrypoint script re-runs `npm install` on every container start so the host and container stay in sync.

---

### main.css — CSS import chain

Vite handles CSS `@import` inside `.css` files and resolves bare package specifiers (like `'admin-lte/dist/css/adminlte.min.css'`) against `node_modules/`. This means a single `main.css` file can pull in the entire AdminLTE stylesheet stack without any Webpack aliases or PostCSS plugins.

The import order matters:
1. **Google Fonts** — loads `Source Sans Pro`, AdminLTE's default typeface, from the CDN.
2. **Font Awesome** — must be available before any AdminLTE component that references `.fas`/`.far`/`.fab` icon classes.
3. **icheck-bootstrap** — overrides Bootstrap's default checkbox/radio styles; must come after Bootstrap (bundled inside `adminlte.min.css`) to apply correctly.
4. **AdminLTE** — the main theme stylesheet that layers on top of everything above.

The file is linked from `index.html` with an absolute path (`/src/main.css`) so Vite serves it as a static asset during development. It is **not** imported inside `main.js` to keep JS bundle and CSS concerns separate.

---

### index.html — AdminLTE structural requirements

AdminLTE's CSS assumes specific classes exist on the `<body>` and the outermost wrapper element. Without them, the layout either breaks visually or JavaScript behaviour (sidebar toggle, transitions) does not initialise correctly.

**`class="sidebar-mini layout-fixed"` on `<body>`** — `sidebar-mini` collapses the sidebar to icons only when the viewport is narrow. `layout-fixed` fixes the top navbar and sidebar in place while the main content area scrolls. Both are required by AdminLTE's JS for the sidebar open/close state machine.

**`style="height: auto;"` on `<body>`** — Prevents AdminLTE's default `height: 100%` rule from causing the body to expand to full viewport height on short pages (e.g. the login page), which would produce a large empty grey area below the login box.

**`class="wrapper"` on `<div id="app">`** — AdminLTE's layout expects a `.wrapper` div immediately inside the body to contain the navbar, sidebar, and content wrapper. Vue mounts into `#app`, so `#app` itself takes on the `.wrapper` role.

---

### main.js — JS side-effects

AdminLTE's JavaScript (and Bootstrap's) is loaded as a **side-effect import** — the import statement is included purely to execute the module's code, not to import any named export. Placing these imports before `createApp` ensures that Bootstrap's jQuery plugins and AdminLTE's layout initialisation code have registered themselves on `window` before Vue begins mounting components.

```
bootstrap.bundle.min.js   → registers Bootstrap 4 jQuery plugins (collapse, dropdown, modal, etc.)
adminlte.min.js           → registers AdminLTE's sidebar, push-menu, and layout JS
```

Both run once at page load and then react to DOM events via delegated listeners, so they do not need to be called again when Vue Router navigates between routes.

---

### Signin.vue & Signup.vue — AdminLTE login forms

Both components use AdminLTE's `.login-page` / `.login-box` two-element centring layout. The page class applies a full-screen background colour; the box class constrains the card width to 360 px and centres it vertically. Inside the box, a Bootstrap `.card.card-outline.card-primary` provides the white card with a coloured top border.

The forms are static HTML at this stage — there are no `v-model` bindings, no submit handlers, and no API calls. The inputs exist to match the visual design and establish the field names that will be bound to reactive state in a later session.

**Cross-navigation links:**

| Component | Link text | Route name |
|---|---|---|
| `Signin.vue` | "Register a new membership" | `auth.signup` |
| `Signup.vue` | "I already have an account" | `auth.signin` |

Both use `:to="{ name: '...' }"` rather than a hardcoded href so they automatically stay correct if route paths change.

---

### Dashboard.vue — authenticated landing page

The dashboard is intentionally minimal at this stage — it confirms to the developer that sign-in was successful and provides a visible way to reach the sign-out route. The Bootstrap `.alert.alert-success` class gives a green-tinted confirmation panel. The sign-out icon is a Font Awesome `fa-sign-out-alt` wrapped in a `<router-link>` pointing to `auth.signout`. No `<script setup>` block is needed because there is no reactive state yet.

---

## Common Commands

```bash
# Shell into the Vue.js container
docker compose exec vuejs-container sh

# Install AdminLTE and its peer dependencies
npm install husky --save-dev

npm install admin-lte@^3.2 --save

npm install bootstrap@4.6.2 

npm install @fortawesome/fontawesome-free@5.15.4 

npm install icheck-bootstrap@3.0.1

# Install husky (dev dependency)
npm install --save-dev husky

# Start the Vite dev server
npm run dev -- --host=0.0.0.0 --port=5173

# Start all services
docker compose up --build
```
