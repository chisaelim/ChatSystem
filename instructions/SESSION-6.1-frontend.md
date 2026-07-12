# ChatSystem — Frontend backup management page with list, create, download, and delete functionality

## Table of Contents

- [What Changed in Session 6.1-frontend](#what-changed-in-session-61-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/src/functions/api/backup.js](#vuejs-appsrcfunctionsapibackupjs)
  - [vuejs-app/src/components/pages/Backup.vue](#vuejs-appsrccomponentspagesbackupvue)
  - [vuejs-app/src/components/includes/LeftSidebar.vue](#vuejs-appsrccomponentsinclludesleftsidebarvue)
  - [vuejs-app/src/router/index.js](#vuejs-appsrcrouterindexjs)
- [How Each File Works](#how-each-file-works)
  - [Backup API functions — HTTP client wrapper for backup endpoints](#backup-api-functions--http-client-wrapper-for-backup-endpoints)
  - [Backup page — backup management UI and workflow](#backup-page--backup-management-ui-and-workflow)
  - [Sidebar navigation — backup menu item for admins](#sidebar-navigation--backup-menu-item-for-admins)
  - [Router configuration — backup route registration](#router-configuration--backup-route-registration)
- [Common Commands](#common-commands)

---

## What Changed in Session 6.1-frontend

Session 6.0 introduced comprehensive backup management on the backend, implementing the Spatie Laravel Backup package with a BackupController exposing four API endpoints for backup operations, scheduled daily backups, and queued backup jobs. Session 6.1-frontend completes the feature by implementing the frontend user interface for backup management, creating a new backup API helper module that wraps the four backend endpoints (list, create, download, delete) with axios HTTP calls, building a Backup.vue page component that displays backups in a table with filename, size with running total, and creation date columns, implementing action buttons for downloading and deleting individual backups and a Create button in the table header for initiating new backups, using SweetAlert modals for user confirmations before creating or deleting backups, handling file downloads by creating blob URLs and triggering browser download dialogs, updating the LeftSidebar component to show a Backups menu item that appears only for admin users with a database icon, adding the backups route to the Vue Router configuration with the standard layout components (navbar, sidebar, footer), and ensuring the backup page enforces admin-only access via the guarded meta flag.

| Area | Session 6.0 | Session 6.1-frontend |
|---|---|---|
| Backup API wrapper functions | No frontend functions | Four functions: `apiGetBackups()`, `apiCreateBackup()`, `apiDownloadBackup(filename)`, `apiDeleteBackup(filename)` |
| Backup management UI | No frontend UI | Backup.vue table page with columns for filename, size, date, actions |
| Backup operations on UI | No user-facing UI | Create, download, delete buttons with confirmation modals |
| Sidebar navigation | Users menu only | Backups menu item added (admin-only visibility) |
| Router configuration | Users route only | Backups route added with guarded meta protection |
| File download handling | N/A | Blob-based file download with dynamic filename |
| Total backup size display | No frontend display | Total size shown in table header |
| Admin restriction | Already enforced via middleware | Enforced at UI level (menu visibility) and route level (guarded meta) |

`vuejs-app/src/functions/api/backup.js` was created manually as a new file with four export functions. `vuejs-app/src/components/pages/Backup.vue` was created manually as a new Vue component. `vuejs-app/src/components/includes/LeftSidebar.vue` existed previously and was edited manually to add the Backups navigation link. `vuejs-app/src/router/index.js` existed previously and was edited manually to import and register the Backup component and route.

---

## File Contents

The labels below each heading tell you what action to take:
- **Created manually** — the file does not exist and no CLI command creates it; paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/functions/api/backup.js`

> **Created manually** — add four export functions to wrap backup API endpoints from the backend.

```js
import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export function apiGetBackups() {
  return axios.get(APP_API_URL + '/manage/backups');
}

export function apiCreateBackup() {
  return axios.post(APP_API_URL + '/manage/backups/create');
}

export function apiDownloadBackup(filename) {
  return axios.get(APP_API_URL + `/manage/backups/download/${filename}`, {
    responseType: 'blob'
  });
}

export function apiDeleteBackup(filename) {
  return axios.delete(APP_API_URL + `/manage/backups/delete/${filename}`);
}
```

---

### `vuejs-app/src/components/pages/Backup.vue`

> **Created manually** — add a new Backup page component with table, action buttons, and confirmation modals for backup management.

```vue
<template>
  <div class="content-wrapper" style="min-height: 1416px">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Backups</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link :to="{ name: 'dashboard' }">Home</router-link>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <CustomTable :title="'Backups'" :data="backups" :columns="columns" />
      </div>
    </section>
  </div>
</template>

<script setup>
import Swal from "sweetalert2";
import { apiGetBackups, apiCreateBackup, apiDownloadBackup, apiDeleteBackup } from "@/functions/api/backup";
import { CloseModal, LoadingModal, MessageModal } from "@/functions/swal";
import { onMounted, ref, h } from "vue";
import CustomTable from "@/components/includes/controls/CustomTable.vue";

const total_size_human = ref(0);
const backups = ref([]);
const columns = [
  {
    header: "Filename",
    accessorKey: "filename",
  },
  {
    header: () => "Size (Total: " + total_size_human.value + ")",
    accessorKey: "size_human",
  },
  {
    header: "Created At",
    accessorKey: "date_human",
  },
  {
    accessorKey: "action",
    header: () => [
      "Actions",
      h(
        "button",
        {
          onClick: () => createBackup(),
          class: "btn btn-sm btn-success ml-3",
        },
        "Create"
      ),
    ],
    cell: ({
      row: {
        original: { filename },
      },
    }) => [
        // download btn
        h(
          "button",
          {
            onClick: () => downloadBackup(filename),
            class: "btn btn-sm btn-outline-primary mx-1",
            title: "Download Backup",
          },
          h("i", { class: "fa fa-download" })
        ),
        // delete btn
        h(
          "button",
          {
            onClick: () => removeBackup(filename),
            class: "btn btn-sm btn-outline-danger mx-1",
            title: "Delete Backup",
          },
          h("i", { class: "fa fa-trash" })
        ),
      ],
    enableSorting: false,
  },
];

onMounted(async () => {
  try {
    LoadingModal();
    await generateBackups();
    return CloseModal();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
  }
});

async function generateBackups() {
  const response = await apiGetBackups();
  backups.value = response.data.backups;
  total_size_human.value = response.data.total_size_human;
}

async function createBackup() {
  Swal.fire({
    icon: "info",
    title: "Create Backup",
    text: "Are you sure you want to create a new backup? This may take some time.",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    confirmButtonText: "Yes, create it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal();
        const response = await apiCreateBackup();
        return MessageModal({ icon: "success", title: "Success", text: response.data.message });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
      }
    }
  });
}

async function downloadBackup(filename) {
  try {
    LoadingModal();
    const response = await apiDownloadBackup(filename);
    CloseModal();

    // Create blob and download
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    link.parentNode.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.message || error.response?.data?.message });
  }
}

async function removeBackup(filename) {
  Swal.fire({
    icon: "warning",
    title: "Delete Backup",
    text: "Are you sure you want to delete this backup? This action cannot be undone.",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal();
        const response = await apiDeleteBackup(filename);
        onBackupDelete(filename);
        return MessageModal({ icon: "success", title: "Success", text: response.data.message });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
      }
    }
  });
}

function onBackupDelete(filename) {
  backups.value = backups.value.filter((b) => b.filename !== filename);
  total_size_human.value = formatBytes(backups.value.reduce((acc, b) => acc + b.size, 0));
}

function formatBytes(bytes, precision = 2) {
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  bytes = Math.max(bytes, 0);
  const pow = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024));
  const powIndex = Math.min(pow, units.length - 1);
  bytes /= Math.pow(1024, powIndex);
  return bytes.toFixed(precision) + ' ' + units[powIndex];
}
</script>
```

---

### `vuejs-app/src/components/includes/LeftSidebar.vue`

> **Edited manually** — add a navigation link to the Backups page that appears only for admin users.

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
          <li class="nav-header" v-if="userStore.isAdmin">MANAGEMENT</li>
          <li class="nav-item" v-if="userStore.isAdmin">
            <router-link :to="{ name: 'users' }" active-class="active" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>
                Users
              </p>
            </router-link>
          </li>
          <li class="nav-item" v-if="userStore.isAdmin">
            <router-link :to="{ name: 'backups' }" active-class="active" class="nav-link">
              <i class="nav-icon fas fa-database"></i>
              <p>
                Backups
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

### `vuejs-app/src/router/index.js`

> **Edited manually** — import the Backup component and register the backups route with layout components and admin-only guarding.

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
import User from '@/components/pages/User.vue';
import Backup from '@/components/pages/Backup.vue';
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
        right_sidebar: RightSidebar,
        footer: Footer,
      },
      meta: { guarded: true },
    },
    {
      path: '/users',
      name: 'users',
      components: {
        default: User,
        navbar: Navbar,
        left_sidebar: LeftSidebar,
        right_sidebar: RightSidebar,
        footer: Footer,
      },
      meta: { guarded: true },
    },
    {
      path: '/backups',
      name: 'backups',
      components: {
        default: Backup,
        navbar: Navbar,
        left_sidebar: LeftSidebar,
        right_sidebar: RightSidebar,
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

### Backup API functions — HTTP client wrapper for backup endpoints

The `backup.js` module exports four functions that wrap the backend API endpoints published in SESSION-6.0 under `/manage/backups`, providing a clean client interface for the Backup.vue component. The `apiGetBackups()` function makes a GET request to `/manage/backups` and returns a promise resolving to the backup list with metadata (filename, size, date) and totals. The `apiCreateBackup()` function POSTs to `/manage/backups/create` with no body, queuing a backup job on the backend and returning a 202 status. The `apiDownloadBackup(filename)` function GETs the specific backup file with `responseType: 'blob'` to treat the response as binary data suitable for file downloads, passing the filename as a URL parameter. The `apiDeleteBackup(filename)` function DELETEs the specific backup file, also using the filename as a URL parameter. All functions use the same `APP_API_URL` environment variable established in other API modules, ensuring consistent endpoint targeting.

---

### Backup page — backup management UI and workflow

The `Backup.vue` component renders a backup management interface with a table displaying all backups. The table has four columns: Filename (stores the backup ZIP name), Size (shows human-readable format like "5.2 MB"), Created At (timestamp of backup creation), and Actions (buttons). The Actions column header also contains a "Create" button to initiate new backups. Each row in the Actions cell has two icon buttons: download (blue, download icon) and delete (red, trash icon). The `onMounted` hook calls `generateBackups()` to fetch the list on page load, wrapped in `LoadingModal()` for UI feedback. The `generateBackups()` function calls `apiGetBackups()`, populates the `backups.value` array, and updates `total_size_human.value` displayed in the Size column header. The `createBackup()` function shows a confirmation modal; if confirmed, it calls `apiCreateBackup()` which returns a success message. The `downloadBackup(filename)` function retrieves the backup file as a blob, creates a temporary object URL, constructs a DOM link element, programmatically clicks it to trigger the browser's download dialog, and cleans up the URL object. The `removeBackup(filename)` function shows a warning confirmation, and if confirmed, calls `apiDeleteBackup()` then `onBackupDelete()` to remove the backup from the array and recalculate the total size using the `formatBytes()` helper.

---

### Sidebar navigation — backup menu item for admins

The LeftSidebar component displays the application's main navigation menu. The Backups menu item is conditionally rendered using `v-if="userStore.isAdmin"`, ensuring only admin users see the link. The link uses `router-link` with `name: 'backups'` to target the backup route, and the `active-class="active"` directive applies Bootstrap active styling when the current route matches. The Backups item includes a database icon (`fas fa-database`) and the label "Backups", appearing alongside the Users management item under the "MANAGEMENT" header that also displays only for admins. This pattern ensures non-admin users see no backup-related UI elements, reinforcing the admin-only nature of the feature at the presentation layer.

---

### Router configuration — backup route registration

The router imports the Backup component at the top of the file alongside other page components. The `/backups` route uses named routing (`name: 'backups'`) to support `router-link` components and programmatic navigation. The route uses the `components` object to render multiple layout slots: `default` renders the Backup component as the main content area, while `navbar`, `left_sidebar`, `right_sidebar`, and `footer` render their respective layout components shared with other admin pages like the Users page. The `meta: { guarded: true }` flag indicates this route requires authentication; the application's router guard checks this flag and redirects unauthenticated users to the signin page. Backend middleware on the API endpoints (e.g., the `admin` middleware on backup routes in SESSION-6.0) provides additional authorization enforcement, ensuring that even if a non-admin token somehow reaches the API, the request is rejected server-side.

---

## Common Commands

```bash
# Load backups page (navigate in browser or via router)
# URL: http://localhost:5173/backups

# Create a new backup manually (triggers queue job on backend)
# Click "Create" button in Actions column header

# Download a backup file
# Click download icon button in Actions column

# Delete a backup file
# Click trash icon button in Actions column

# View backup creation timestamp
# Check "Created At" column

# Check total backup storage used
# See "Size (Total: X.X GB)" in column header

# Monitor backup process
# Wait for LoadingModal to close after Create backup

# If developing with npm dev server:
npm run dev

# If developing in Docker:
docker exec vuejs npm run dev
```
