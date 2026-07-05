# ChatSystem — Frontend user status display and toggle with disabled account error handling

## Table of Contents

- [What Changed in Session 5.3-frontend](#what-changed-in-session-53-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/src/functions/api/user.js](#vuejs-appsrcfunctionsapiuserjs)
  - [vuejs-app/src/components/pages/User.vue](#vuejs-appsrccomponentspagesuservue)
  - [vuejs-app/src/components/google-oauth/GoogleOAuth.vue](#vuejs-appsrccomponentsgoogle-oauthgoogleoauthvue)
- [How Each File Works](#how-each-file-works)
  - [User API helpers — new toggle status endpoint integration](#user-api-helpers--new-toggle-status-endpoint-integration)
  - [User page — status column display and toggle functionality](#user-page--status-column-display-and-toggle-functionality)
  - [Google OAuth component — disabled account error handling](#google-oauth-component--disabled-account-error-handling)
- [Common Commands](#common-commands)

---

## What Changed in Session 5.3-frontend

Session 5.2 implemented backend user account status management with account disabling and authentication blocking. Session 5.3-frontend completes the feature by exposing the user status state in the frontend, adding a new `apiToggleUserStatus` helper function that calls the backend toggle endpoint, updating the User management page to display a Status column with color-coded badges showing ENABLED in green and DISABLED in red, adding a toggle status button in the user table actions that switches between enable/disable based on the current account state with appropriate icons and styling, implementing the `toggleUserStatus` function to call the API and update the user list reactively, extending the GoogleOAuth component to handle the `account_disabled` error case when disabled users attempt OAuth authentication and redirecting them to the signin page with a clear error message.

| Area | Session 5.2 | Session 5.3-frontend |
|---|---|---|
| User API functions | No toggle endpoint wrapper | `apiToggleUserStatus(id)` PATCH request to `/manage/users/toggle-status/{id}` |
| Status visibility in table | Status field not exposed in responses | Status displayed as color-coded badge column (green=ENABLED, red=DISABLED) |
| User action buttons | Delete and edit buttons only | Added toggle status button with dynamic icon (ban for disable, check for enable) |
| Status toggle workflow | N/A | Admin clicks toggle button → API call → user list updates with new status |
| OAuth disabled account handling | No frontend handling for `account_disabled` error | GoogleOAuth catches error and shows error modal, redirects to signin |
| Account state persistence | No frontend persistence of status | Status field included in all user responses, reflected in table |

`vuejs-app/src/functions/api/user.js` existed previously and was edited manually to add the `apiToggleUserStatus` function. `vuejs-app/src/components/pages/User.vue` existed previously and was edited manually to add the Status column definition with badge styling, add status property destructuring in the action button cell, add the toggle status button with conditional styling, and implement the `toggleUserStatus` function. `vuejs-app/src/components/google-oauth/GoogleOAuth.vue` existed previously and was edited manually to add error handling for the `account_disabled` error case.

---

## File Contents

The labels below each heading tell you what action to take:
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/functions/api/user.js`

> **Edited manually** — add the `apiToggleUserStatus` function to wrap the backend toggle-status endpoint.

```js
import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export function apiGetUsers(params = {}) {
  return axios.get(APP_API_URL + '/manage/users', { params });
}

export function apiReadUser(id) {
  return axios.get(APP_API_URL + `/manage/users/read/${id}`);
}

export function apiCreateUser(data) {
  return axios.post(APP_API_URL + `/manage/users/create`, data);
}

export function apiUpdateUser(id, data) {
  return axios.put(APP_API_URL + `/manage/users/update/${id}`, data);
}

export function apiToggleUserStatus(id) {
  return axios.patch(APP_API_URL + `/manage/users/toggle-status/${id}`);
}

export function apiDeleteUser(id) {
  return axios.delete(APP_API_URL + `/manage/users/delete/${id}`);
}
```

---

### `vuejs-app/src/components/pages/User.vue`

> **Edited manually** — update the User page to import `apiToggleUserStatus`, add Status column to the table, add toggle status button to actions, and implement the `toggleUserStatus` function.

```vue
<template>
  <div class="content-wrapper" style="min-height: 1416px">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Users</h1>
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
        <CustomTablePaginated :title="'Users'" :data="users" :columns="columns" v-model:currentPage="currentPage"
          v-model:lastPage="lastPage" v-model:total="total" v-model:pageSize="pageSize" v-model:keyword="keyword"
          @search-change="handleSearchChange" />
      </div>
    </section>
  </div>
  <div class="modal fade" ref="userModal" aria-modal="true" role="dialog">
    <form @submit.prevent="saveUser">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">User</h4>
            <button type="button" class="close" @click="hideModal" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="userName">Name</label>
              <input type="text" class="form-control" v-model="user.name" :class="{ 'is-invalid': !!userError.name }" />
              <div class="invalid-feedback">{{ userError.name }}</div>
            </div>
            <div class="form-group">
              <label for="userEmail">Email</label>
              <input type="email" class="form-control" v-model="user.email"
                :class="{ 'is-invalid': !!userError.email }" />
              <div class="invalid-feedback">{{ userError.email }}</div>
            </div>
            <div class="form-group">
              <label for="userPassword">Password</label>
              <input type="password" class="form-control" v-model="user.password"
                :class="{ 'is-invalid': !!userError.password }" />
              <div class="invalid-feedback">{{ userError.password }}</div>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-default" @click="hideModal">
              Close
            </button>
            <button type="submit" class="btn btn-primary">Save changes</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import $ from "jquery";
import Swal from "sweetalert2";
import { apiGetUsers, apiCreateUser, apiUpdateUser, apiReadUser, apiDeleteUser, apiToggleUserStatus } from "@/functions/api/user";
import { CloseModal, LoadingModal, MessageModal } from "@/functions/swal";
import { onMounted, ref, h, reactive, watch } from "vue";
import CustomTablePaginated from "@/components/includes/controls/CustomTablePaginated.vue";

const userModal = ref(null);
const users = ref([]);

// Pagination state
const currentPage = ref(1);
const pageSize = ref(25);
const total = ref(0);
const lastPage = ref(1);
const keyword = ref("");  // Track search keyword

const user = reactive({
  id: null,
  name: "",
  email: "",
  password: "",
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

const columns = [
  {
    header: "ID",
    accessorKey: "id",
  },
  {
    header: "Name",

    accessorKey: "name",
  },
  {
    header: "Email",
    accessorKey: "email",
  },
  {
    header: "Status",
    accessorKey: "status",
    cell: ({
      row: {
        original: { status },
      },
    }) => h(
      "span",
      {
        class: status === "ENABLED" ? "badge badge-success" : "badge badge-danger",
      },
      status
    ),
  },
  {
    accessorKey: "action",
    header: () => [
      "Actions",
      h(
        "button",
        {
          onClick: () => showModal(),
          class: "btn btn-sm btn-success ml-3",
        },
        "Create"
      ),
    ],
    cell: ({
      row: {
        original: { id, status },
      },
    }) => [
        // delete btn
        h(
          "button",
          {
            onClick: () => removeUser(id),
            class: "btn btn-sm btn-outline-danger mx-1",
          },
          h("i", { class: "fa fa-trash" })
        ),
        // view btn
        h(
          "button",
          {
            onClick: () => viewUser(id),
            class: "btn btn-sm btn-outline-secondary mx-1",
          },
          h("i", { class: "fa fa-pen" })
        ),
        // toggle status btn
        h(
          "button",
          {
            onClick: () => toggleUserStatus(id),
            class: status === "ENABLED" ? "btn btn-sm btn-danger mx-1" : "btn btn-sm btn-success mx-1",
            title: status === "ENABLED" ? "Disable User" : "Enable User",
          },
          h("i", { class: status === "ENABLED" ? "fa fa-ban" : "fa fa-check" })
        ),
      ],
    enableSorting: false,
  },
];

onMounted(async () => {
  $(userModal.value).on("hide.bs.modal", function () {
    resetAllState();
  });
  try {
    LoadingModal();
    await generateUsers(keyword.value, currentPage.value, pageSize.value);
    return CloseModal();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
  }
});

async function generateUsers(searchKeyword = "", page = 1, per_page = 25) {
  const response = await apiGetUsers({
    keyword: searchKeyword,
    page: page,
    per_page: per_page,
  });

  // Update all pagination state from API response
  users.value = response.data.users;
  currentPage.value = response.data.meta.current_page;
  pageSize.value = response.data.meta.per_page;
  total.value = response.data.meta.total;
  lastPage.value = response.data.meta.last_page;
}

// Watch for pagination changes to fetch data
watch(currentPage, async (newPage, oldPage) => {
  if (newPage !== oldPage) {
    try {
      LoadingModal();
      await generateUsers(keyword.value, newPage, pageSize.value);
      CloseModal();
    } catch (error) {
      MessageModal({ icon: "error", title: "Error", text: error.message || error.response?.data?.message });
    }
  }
});

watch(pageSize, async (newSize, oldSize) => {
  if (newSize !== oldSize) {
    try {
      LoadingModal();
      currentPage.value = 1; // Reset to first page when changing page size
      await generateUsers(keyword.value, 1, newSize);
      CloseModal();
    } catch (error) {
      MessageModal({ icon: "error", title: "Error", text: error.message || error.response?.data?.message });
    }
  }
});

async function handleSearchChange(searchKeyword) {
  try {
    LoadingModal();
    await generateUsers(searchKeyword, 1, pageSize.value);
    CloseModal();
  } catch (error) {
    MessageModal({ icon: "error", title: "Error", text: error.message || error.response?.data?.message });
  }
}


async function saveUser() {
  try {
    LoadingModal();
    let response = null;
    if (user.id) {
      response = await apiUpdateUser(user.id, user);
      onUserUpdate(response.data.user);
    } else {
      response = await apiCreateUser(user);
      onUserCreate(response.data.user);
    }

    // Implement save user logic here
    hideModal();
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

async function viewUser(id) {
  try {
    LoadingModal();
    const response = await apiReadUser(id);
    Object.assign(user, response.data.user);
    showModal();
    return CloseModal();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
  }
}

async function removeUser(id) {
  Swal.fire({
    icon: "warning",
    title: "Delete User",
    text: "Are you sure you want to delete this user? This action cannot be undone.",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal();
        const response = await apiDeleteUser(id);
        onUserDelete(id);
        return MessageModal({ icon: "success", title: "Success", text: response.data.message });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
      }
    }
  });
}

async function toggleUserStatus(id) {
  try {
    LoadingModal();
    const response = await apiToggleUserStatus(id);
    onUserUpdate(response.data.user);
    return MessageModal({ icon: "success", title: "Success", text: response.data.message });
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
  }
}


function showModal() {
  $(userModal.value).modal("show");
}

function hideModal() {
  $(userModal.value).modal("hide");
}

function onUserCreate(user) {
  users.value = [user, ...users.value];
}
function onUserUpdate(user) {
  users.value = users.value.map((u) => (u.id === user.id ? user : u));
}
function onUserDelete(id) {
  users.value = users.value.filter((u) => u.id !== id);
}
</script>
```

---

### `vuejs-app/src/components/google-oauth/GoogleOAuth.vue`

> **Edited manually** — add error handling for the `account_disabled` error case in the GoogleOAuth callback handler.

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

    if (error === 'account_disabled') {
      return MessageModal({ icon: "error", title: "Error", text: "Your account has been disabled. Please contact support." }, () => {
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

## How Each File Works

### User API helpers — new toggle status endpoint integration

The `apiToggleUserStatus(id)` function wraps a PATCH request to `/manage/users/toggle-status/{id}`, matching the backend route registered in SESSION-5.2. Unlike update or create endpoints, this function requires only the user ID parameter and sends no request body, as the backend toggles the status without additional input. The function returns the updated user object in the response, allowing the frontend to immediately reflect the new status without a page reload.

---

### User page — status column display and toggle functionality

The User.vue component adds a new Status column to the table that renders a Bootstrap badge showing "ENABLED" in green (success class) or "DISABLED" in red (danger class), providing at-a-glance visibility into account states. The Status column uses TanStack Table's cell renderer to conditionally apply badge styling. The action buttons cell now destructures both `id` and `status` from the original row data, enabling the toggle button to determine which action to display. The new toggle status button appears alongside delete and edit buttons, with conditional styling: red background with a ban icon when the account is ENABLED (indicating a disable action), green background with a check icon when ENABLED is false (indicating an enable action). The button's title attribute provides a tooltip showing the action intent. The `toggleUserStatus` function calls `apiToggleUserStatus`, wraps the response in a success message modal, and calls `onUserUpdate` to reactively update the user in the table with the new status.

---

### Google OAuth component — disabled account error handling

The GoogleOAuth component's onMounted hook processes the callback from the Google OAuth redirect. After checking for the `google_oauth_failed` error, it now also checks for the `account_disabled` error parameter that the backend sends when a disabled user attempts OAuth login. If this error is detected, a MessageModal displays the error "Your account has been disabled. Please contact support." and routes back to the signin page when the user dismisses the modal. This matches the user experience of regular signin failures, ensuring disabled users receive consistent error messaging across authentication methods.

---

## Common Commands

```bash
# No commands needed for this session — all changes are frontend code edits only
# If developing locally without Docker:
npm run dev

# If developing in Docker:
docker exec vuejs npm run dev
```
