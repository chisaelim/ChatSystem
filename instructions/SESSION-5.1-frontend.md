# ChatSystem — Frontend user management UI with server-side pagination and table components

## Table of Contents

- [What Changed in Session 5.1-frontend](#what-changed-in-session-51-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/package.json](#vuejs-apppackagejson)
  - [vuejs-app/src/components/includes/controls/CustomTable.vue](#vuejs-appsrccomponentsincludescontrolscustomtablevue)
  - [vuejs-app/src/components/includes/controls/CustomTablePaginated.vue](#vuejs-appsrccomponentsincludescontrolscustomtablepaginatedvue)
  - [vuejs-app/src/functions/api/user.js](#vuejs-appsrcfunctionsapiuserjs)
  - [vuejs-app/src/components/pages/User.vue](#vuejs-appsrccomponentspagesuservue)
  - [vuejs-app/src/stores/user.js](#vuejs-appsrcstoresuserjs)
  - [vuejs-app/src/components/includes/LeftSidebar.vue](#vuejs-appsrccomponentsincludesleftsidebarvue)
  - [vuejs-app/src/router/index.js](#vuejs-appsrcrouterindexjs)
- [How Each File Works](#how-each-file-works)
  - [Table components — reusable client-side and server-side pagination tables](#table-components--reusable-client-side-and-server-side-pagination-tables)
  - [User API helpers — backend integration for user management endpoints](#user-api-helpers--backend-integration-for-user-management-endpoints)
  - [User page — full CRUD interface with modal form and reactive pagination](#user-page--full-crud-interface-with-modal-form-and-reactive-pagination)
  - [User store and sidebar — role-based UI visibility](#user-store-and-sidebar--role-based-ui-visibility)
  - [Router — users route registration](#router--users-route-registration)
- [Common Commands](#common-commands)

---

## What Changed in Session 5.1-frontend

Session 5.0 implemented backend APIs for administrative user management with role-based access control. Session 5.1-frontend builds the corresponding frontend interface by installing `@tanstack/vue-table` for table rendering, creating reusable table components supporting both client-side and server-side pagination, implementing API helper functions that call the backend user management endpoints, building a complete user management page with search, pagination, and modal-based create/update/delete workflows, extending the user store with a `level` field and `isAdmin` getter, conditionally displaying the Users menu item in the sidebar for admin users only, and registering the `/users` route with layout components.

| Area | Session 5.0 | Session 5.1-frontend |
|---|---|---|
| User management UI | No frontend interface existed | Full user management page with table, search, pagination, and CRUD modals |
| Table components | No reusable table components | `CustomTable` for client-side pagination and `CustomTablePaginated` for server-side pagination with search |
| User API integration | Backend endpoints without frontend callers | `user.js` API helpers wrapping all five user management endpoints |
| Admin role visibility | Level field existed only in backend | Frontend store tracks `level`, computes `isAdmin`, and conditionally shows admin menu items |
| Navigation | No users route | `/users` route registered with full layout (navbar, sidebars, footer) |
| Dependencies | No table rendering library | `@tanstack/vue-table` installed for flexible table rendering with TanStack Table API |

`vuejs-app/package.json` existed previously and was modified by command after installing the table library. `vuejs-app/src/components/includes/controls/CustomTable.vue` was created manually to provide a reusable table with client-side pagination, sorting, and filtering. `vuejs-app/src/components/includes/controls/CustomTablePaginated.vue` was created manually to provide a reusable table with server-side pagination and search. `vuejs-app/src/functions/api/user.js` was created manually to wrap backend user management API calls. `vuejs-app/src/components/pages/User.vue` was created manually to implement the full user management interface. `vuejs-app/src/stores/user.js` existed previously and was edited manually to persist the `level` field and compute `isAdmin`. `vuejs-app/src/components/includes/LeftSidebar.vue` existed previously and was edited manually to conditionally show the Users menu item for admins. `vuejs-app/src/router/index.js` existed previously and was edited manually to add the users route.

---

## File Contents

The labels below each heading tell you what action to take:
- **Modified by command** — the file already exists; run the command shown to let tooling update it.
- **Created manually** — the file does not exist and no CLI command creates it; paste the block to create it.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/package.json`

> **Modified by command** — run the npm install command to add the `@tanstack/vue-table` dependency.

```bash
# run inside vuejs container
npm install @tanstack/vue-table
```

---

### `vuejs-app/src/components/includes/controls/CustomTable.vue`

> **Created manually** — create this reusable table component supporting client-side pagination, sorting, and filtering with TanStack Table.

```vue
<template>
  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between">
        <h3 class="card-title my-auto">{{ props.title }}</h3>
        <div class="d-flex justify-content-end">
          <div class="card-tools">
            <div class="input-group input-group">
              <input v-model="filter" type="text" class="form-control float-right" :placeholder="'Search'" />
              <div class="input-group-append">
                <button class="btn btn-default">
                  <i class="fas fa-search"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body table-responsive p-0">
      <table class="text-nowrap table-head-fixed table-valign-middle table table-head-fixed table-bordered table-hover">
        <thead class="text-center">
          <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <th v-for="header in headerGroup.headers" :key="header.id"
              :class="{ 'can-sort': header.column.getCanSort() }"
              @click="header.column.getToggleSortingHandler()?.($event)">
              <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
              {{ { asc: " ↓", desc: " ↑" }[header.column.getIsSorted()] }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in table.getRowModel().rows" :key="row.id">
            <td v-for="cell in row.getVisibleCells()" :key="cell.id">
              <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="card-footer clearfix" v-if="!props.maxPageSize">
      <div class="row">
        <div class="col-md text-nowrap mb-2">
          <div class="d-flex justify-content-between">
            <div class="col-auto my-auto">
              <span>Page {{ table.getState().pagination.pageIndex + 1 }} of
                {{ table.getPageCount() }} -
                {{ table.getFilteredRowModel().rows.length }}
                {{
                  table.getFilteredRowModel().rows.length !== 1 ? "results" : "result"
                }}</span>
            </div>
            <div class="col-auto">
              <div class="input-group input-group">
                <div class="input-group-prepend">
                  <button class="btn btn-default">Show</button>
                </div>
                <select v-model="pageSize" class="form-control">
                  <option v-for="size in [10, 25, 50, 100, 250]" :key="size" :value="size">
                    {{ size }}
                  </option>

                  <option :value="table.getFilteredRowModel().rows.length">Max</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-auto">
          <div class="d-flex justify-content-center">
            <div class="dataTables_paginate paging_simple_numbers">
              <ul class="pagination">
                <li class="paginate_button page-item" :class="{ disabled: !table.getCanPreviousPage() }">
                  <a @click="table.setPageIndex(0)" role="button" tabindex="0" class="page-link"><i
                      class="fas fa-angle-double-left"></i></a>
                </li>
                <li class="paginate_button page-item" :class="{ disabled: !table.getCanPreviousPage() }">
                  <a @click="table.previousPage()" role="button" tabindex="0" class="page-link"><i
                      class="fas fa-angle-left"></i></a>
                </li>

                <li v-if="currentPage > sidePage" class="paginate_button page-item">
                  <a role="button" tabindex="0" class="page-link">...</a>
                </li>
                <template v-if="table.getPageCount() > 0" v-for="index in table.getPageCount()" :key="index">
                  <li v-if="
                    index > currentPage - sidePage && index < currentPage + 2 + sidePage
                  " class="paginate_button page-item" :class="{ active: index - 1 === currentPage }">
                    <a @click="table.setPageIndex(index - 1)" role="button" tabindex="0" class="page-link">{{ index
                    }}</a>
                  </li>
                </template>
                <li v-if="currentPage + 1 < table.getPageCount() - sidePage" class="paginate_button page-item">
                  <a role="button" tabindex="0" class="page-link">...</a>
                </li>

                <li class="paginate_button page-item" :class="{ disabled: !table.getCanNextPage() }">
                  <a @click="table.nextPage()" role="button" tabindex="0" class="page-link"><i
                      class="fas fa-angle-right"></i></a>
                </li>
                <li class="paginate_button page-item" :class="{ disabled: !table.getCanNextPage() }">
                  <a @click="table.setPageIndex(table.getPageCount() - 1)" role="button" tabindex="0"
                    class="page-link"><i class="fas fa-angle-double-right"></i></a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
.can-sort {
  cursor: pointer;
  user-select: none;
}
</style>
<script setup>
import { computed, onBeforeUpdate, ref, watch } from "vue";
import {
  useVueTable,
  FlexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  getFilteredRowModel,
} from "@tanstack/vue-table";
const props = defineProps({
  title: String,
  data: Array,
  columns: Array,
  maxPageSize: {
    type: Boolean,
    default: false,
  },
  pageSize: {
    type: Number,
    default: 25,
    validator: (value) => [10, 25, 50, 100, 250].includes(value),
  },
});
const sidePage = ref(3);

const sorting = ref([]);
const filter = ref("");
const currentPage = ref(0);
const pageSize = ref(
  props.maxPageSize && props.data.length ? props.data.length : props.pageSize
);
const columns = ref(props.columns);
const table = computed(() =>
  useVueTable({
    data: props.data,
    columns: columns.value,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    state: {
      get sorting() {
        return sorting.value;
      },
      get globalFilter() {
        return replaceUnicode(filter.value);
      },
    },
    initialState: {
      pagination: {
        pageIndex: currentPage.value,
        pageSize: pageSize.value,
      },
    },
    onSortingChange: (updaterOrValue) => {
      sorting.value =
        typeof updaterOrValue === "function"
          ? updaterOrValue(sorting.value)
          : updaterOrValue;
    },
  })
);

const showedPage = ref(null);
onBeforeUpdate(() => {
  if (filter.value !== "") {
    if (!showedPage.value) {
      showedPage.value = table.value.getState().pagination.pageIndex;
    }
    // currentPage.value = 0;
    if (table.value.getPageCount() <= currentPage.value) {
      currentPage.value = 0;
    } else {
      currentPage.value = table.value.getState().pagination.pageIndex;
    }
  } else {
    if (showedPage.value && showedPage.value !== currentPage.value) {
      currentPage.value = showedPage.value;
      showedPage.value = null;
    } else {
      currentPage.value = table.value.getState().pagination.pageIndex;
    }
  }
  columns.value = [...props.columns];
});

watch([() => props.data, pageSize], (nv, ov) => {
  currentPage.value = 0;
});

function replaceUnicode(text) {
  const salabpi = ["ង", "ញ", "ប", "ម", "យ", "រ", "វ"];
  const treysab = ["ស", "ហ", "អ"];
  const chars = salabpi.concat(treysab);
  const vowels = ["ិ", "ី", "ឹ", "ឺ", "ើ"];
  text = text
    .replaceAll("្" + "ដ", "្ត")
    .replaceAll("ា" + "ំ", "ាំ")
    .replaceAll("េ" + "ី", "ើ")
    .replaceAll("េ" + "ា", "ោ")
    .replaceAll("េ" + "ះ", "េះ")
    .replaceAll("ោ" + "ះ", "ោះ")
    .replaceAll("េ" + "ុ" + "ី", "ុ" + "ើ");
  for (const char of chars) {
    for (const vowel of vowels) {
      let replacementSign = "";
      if (salabpi.includes(char)) {
        replacementSign = "៉";
      } else if (treysab.includes(char)) {
        replacementSign = "៊";
      } else {
        continue;
      }
      const word = char + "ុ" + vowel;
      const replacement = char + replacementSign + vowel;
      text = text.replaceAll(word, replacement);
    }
  }
  return text;
}
</script>
```

---

### `vuejs-app/src/components/includes/controls/CustomTablePaginated.vue`

> **Created manually** — create this reusable table component for server-side pagination with two-way binding for pagination state and search keyword.

```vue
<template>
  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between">
        <h3 class="card-title my-auto">{{ title }}</h3>
        <div class="d-flex justify-content-end">
          <div class="card-tools">
            <div class="input-group input-group">
              <input v-model="keyword" type="text" class="form-control float-right" placeholder="Search"
                @keyup.enter="handleSearch" />
              <div class="input-group-append">
                <button class="btn btn-default" @click="handleSearch" type="button">
                  <i class="fas fa-search"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body table-responsive p-0">
      <table class="text-nowrap table-head-fixed table-valign-middle table table-head-fixed table-bordered table-hover">
        <thead class="text-center">
          <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <th v-for="header in headerGroup.headers" :key="header.id">
              <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in table.getRowModel().rows" :key="row.id">
            <td v-for="cell in row.getVisibleCells()" :key="cell.id">
              <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="card-footer clearfix">
      <div class="row">
        <div class="col-md text-nowrap mb-2">
          <div class="d-flex justify-content-between">
            <div class="col-auto my-auto">
              <span>Page {{ currentPage }} of {{ lastPage }} - {{ total }} {{ total !== 1 ? "results" : "result"
              }}</span>
            </div>
            <div class="col-auto">
              <div class="input-group input-group">
                <div class="input-group-prepend">
                  <button class="btn btn-default">Show</button>
                </div>
                <select v-model="pageSize" class="form-control">
                  <option v-for="size in [10, 25, 50, 100, 250]" :key="size" :value="size">
                    {{ size }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-auto">
          <div class="d-flex justify-content-center">
            <div class="dataTables_paginate paging_simple_numbers">
              <ul class="pagination">
                <!-- First page -->
                <li class="paginate_button page-item" :class="{ disabled: currentPage === 1 }">
                  <a @click.prevent="currentPage > 1 && changePage(1)" role="button" tabindex="0" class="page-link"
                    :style="{ cursor: currentPage === 1 ? 'not-allowed' : 'pointer' }">
                    <i class="fas fa-angle-double-left"></i>
                  </a>
                </li>
                <!-- Previous page -->
                <li class="paginate_button page-item" :class="{ disabled: currentPage === 1 }">
                  <a @click.prevent="currentPage > 1 && changePage(currentPage - 1)" role="button" tabindex="0"
                    class="page-link" :style="{ cursor: currentPage === 1 ? 'not-allowed' : 'pointer' }">
                    <i class="fas fa-angle-left"></i>
                  </a>
                </li>

                <!-- Ellipsis before -->
                <li v-if="currentPage > sidePage" class="paginate_button page-item">
                  <a class="page-link" style="cursor: default;">...</a>
                </li>

                <!-- Page numbers -->
                <template v-for="pageNum in lastPage" :key="pageNum">
                  <li v-if="pageNum >= currentPage - sidePage && pageNum <= currentPage + sidePage"
                    class="paginate_button page-item" :class="{ active: pageNum === currentPage }">
                    <a @click.prevent="changePage(pageNum)" role="button" tabindex="0" class="page-link"
                      style="cursor: pointer;">{{ pageNum }}</a>
                  </li>
                </template>

                <!-- Ellipsis after -->
                <li v-if="currentPage < lastPage - sidePage" class="paginate_button page-item">
                  <a class="page-link" style="cursor: default;">...</a>
                </li>

                <!-- Next page -->
                <li class="paginate_button page-item" :class="{ disabled: currentPage === lastPage }">
                  <a @click.prevent="currentPage < lastPage && changePage(currentPage + 1)" role="button" tabindex="0"
                    class="page-link" :style="{ cursor: currentPage === lastPage ? 'not-allowed' : 'pointer' }">
                    <i class="fas fa-angle-right"></i>
                  </a>
                </li>
                <!-- Last page -->
                <li class="paginate_button page-item" :class="{ disabled: currentPage === lastPage }">
                  <a @click.prevent="currentPage < lastPage && changePage(lastPage)" role="button" tabindex="0"
                    class="page-link" :style="{ cursor: currentPage === lastPage ? 'not-allowed' : 'pointer' }">
                    <i class="fas fa-angle-double-right"></i>
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, ref, toRefs } from "vue";
import {
  useVueTable,
  FlexRender,
  getCoreRowModel,
} from "@tanstack/vue-table";

const emit = defineEmits(['searchChange']);

const props = defineProps({
  title: String,
  data: Array,
  columns: Array,
});

// Two-way binding models
const pageSize = defineModel('pageSize', { type: Number, default: 25, validator: (value) => [10, 25, 50, 100, 250].includes(value) });
const currentPage = defineModel('currentPage', { type: Number, default: 1 });
const lastPage = defineModel('lastPage', { type: Number, default: 1 });
const total = defineModel('total', { type: Number, default: 0 });
const keyword = defineModel('keyword', { type: String, default: "" });

// Destructure props for cleaner template access
const { title, data, columns } = toRefs(props);

// Local state
const sidePage = ref(3);

// Table for rendering (no client-side pagination)
const table = computed(() =>
  useVueTable({
    data: data.value,
    columns: columns.value,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
  })
);

// Event handlers
function handleSearch() {
  emit('searchChange', keyword.value);
}

function changePage(page) {
  if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
    currentPage.value = page;
  }
}
</script>
```

---

### `vuejs-app/src/functions/api/user.js`

> **Created manually** — create this API helper module to wrap all backend user management endpoints.

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

export function apiDeleteUser(id) {
  return axios.delete(APP_API_URL + `/manage/users/delete/${id}`);
}
```

---

### `vuejs-app/src/components/pages/User.vue`

> **Created manually** — create the user management page with table, search, pagination, and modal-based CRUD forms.

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
import { apiGetUsers, apiCreateUser, apiUpdateUser, apiReadUser, apiDeleteUser } from "@/functions/api/user";
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
        original: { id },
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

### `vuejs-app/src/stores/user.js`

> **Edited manually** — update the user store to persist the `level` field and compute the `isAdmin` getter for role-based UI logic.

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
      level: null,
    }),
    getters: {
      isAuthenticated: (state) => !!state.id,
      isAdmin: (state) => state.level === 'ADMIN',
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
        this.level = user.level;
      },
      resetState() {
        this.id = null;
        this.name = null;
        this.email = null;
        this.profile_image = null;
        this.profile_thumbnail = null;
        this.password_null = true;
        this.level = null;
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

### `vuejs-app/src/components/includes/LeftSidebar.vue`

> **Edited manually** — update the sidebar to conditionally display the Users menu item only for admin users.

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

> **Edited manually** — register the `/users` route with full layout components and guard it with authentication.

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
      path: '/:pathMatch(.*)*',
      redirect: '/dashboard',
    }
  ],
})

export default router
```

---

## How Each File Works

### Table components — reusable client-side and server-side pagination tables

`CustomTable.vue` renders a fully-featured data table with client-side pagination, sorting, and filtering powered by TanStack Table's Vue adapter. It accepts `data`, `columns`, and optional `pageSize` props, tracks sorting and filter state internally, and renders pagination controls with ellipsis for large page counts. The `replaceUnicode` function normalizes Khmer Unicode input for consistent search behavior. `CustomTablePaginated.vue` provides a similar interface but delegates pagination to the server by using `defineModel` for two-way binding of `currentPage`, `lastPage`, `total`, `pageSize`, and `keyword`, emitting a `searchChange` event when the user submits a search, and rendering the data array directly without client-side pagination logic — this allows the parent component to control data fetching and pagination state.

---

### User API helpers — backend integration for user management endpoints

`user.js` exports five async functions wrapping Axios calls to the backend user management API: `apiGetUsers` accepts optional query parameters for keyword search, page number, and page size; `apiReadUser` fetches a single user by ID; `apiCreateUser` posts new user data; `apiUpdateUser` sends a PUT request with user updates; and `apiDeleteUser` issues a DELETE request. All functions rely on the global Axios interceptor configured in `main.js` to inject the bearer token automatically.

---

### User page — full CRUD interface with modal form and reactive pagination

`User.vue` orchestrates the user management workflow by rendering `CustomTablePaginated` with column definitions that include inline action buttons created via Vue's `h` function, watching `currentPage` and `pageSize` changes to trigger server-side data fetches, handling search via `handleSearchChange`, displaying a Bootstrap modal for create/update forms with reactive validation error display, and managing local CRUD operations (`onUserCreate`, `onUserUpdate`, `onUserDelete`) to optimistically update the table without full page refresh. The component uses jQuery to control modal visibility and SweetAlert2 for delete confirmation and loading/success/error modals.

---

### User store and sidebar — role-based UI visibility

The user store now persists the `level` field received from backend API responses and computes an `isAdmin` getter that returns `true` when `level === 'ADMIN'`. The sidebar component consumes this getter to conditionally render a "MANAGEMENT" header and "Users" menu item with `v-if="userStore.isAdmin"`, ensuring non-admin users never see administrative navigation links even if they attempt direct URL navigation (which is blocked by backend middleware).

---

### Router — users route registration

The router index now imports `User.vue` and registers it at `/users` with the same layout structure as other authenticated pages (`navbar`, `left_sidebar`, `right_sidebar`, `footer`), guarded by `meta: { guarded: true }` to enforce authentication via the global route guard in `main.js`.

---

## Common Commands

```bash
# Install the TanStack Table library
docker exec -it vuejs-container npm install @tanstack/vue-table

# Start all services (Laravel + Vue.js + MySQL)
docker compose up

# Rebuild images and start all services (use after any Dockerfile or env file changes)
docker compose down && docker compose up --build

# Install npm dependencies inside the Vue.js container
docker exec -it vuejs-container npm install

# Open a shell in the Vue.js container to inspect the running environment
docker exec -it vuejs-container sh
```
