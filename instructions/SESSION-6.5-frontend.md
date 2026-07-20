# ChatSystem — Frontend sidebar search filtering, pagination, and infinite scroll

## Table of Contents

- [What Changed in Session 6.5](#what-changed-in-session-65)
- [File Contents](#file-contents)
  - [vuejs-app/src/components/includes/LeftSidebar.vue](#vuejs-appsccomponentsincludesleftsidebarvue)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 6.5

Session 6.4-frontend implemented the frontend UI components for displaying chats and available users in the sidebar with datetime utilities and API service module. Session 6.5 enhances the sidebar with real-time search filtering, pagination support, and infinite scroll loading capabilities, transforming the chat and user lists into responsive, dynamically-loaded collections that automatically fetch additional pages of data as the user scrolls toward the bottom of the sidebar. The search functionality integrates with the existing search input field, allowing users to filter both chats and users by keyword with debounced API requests. Pagination state is tracked separately for chats and users to support independent page management. An infinite scroll event listener using jQuery detects when the sidebar has scrolled near the bottom and automatically loads the next page of results without user interaction. The component now renders filtered results when the user enters a search keyword, and displays recent chats and available users when the search field is empty, providing a seamless interface for browsing or searching conversations and contacts.

| Area | Session 6.4-frontend | Session 6.5 |
|---|---|---|
| Search filtering | Static chat/user lists only | Live keyword filtering with API search queries |
| Pagination | Single page of results | Multi-page support with page tracking per data type |
| Data loading | Load on mount only | Load initial data on mount, then infinite scroll |
| Infinite scroll | Not implemented | jQuery scroll event listener triggers page loading |
| Search input binding | No data binding | Reactive v-model binding with watch observer |
| Result display | Always show same lists | Conditional display of filtered vs recent data |
| Loading feedback | No indicator | Spinner icon shows during pagination load |
| Pagination parameters | Not used | page and per_page sent to API endpoints |

`vuejs-app/src/components/includes/LeftSidebar.vue` existed previously and was edited manually to add search keyword binding, pagination state management, infinite scroll event listener setup on component mount, watch observer on keyword for live search filtering, updated generateChats() and generateUsers() functions to accept search parameters and page numbers, and conditional rendering logic for filtered vs recent results with loading indicator.

---

## File Contents

The label below tells you what action to take:
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/components/includes/LeftSidebar.vue`

> **Edited manually** — enhance the sidebar with search keyword binding, pagination state tracking, infinite scroll event listener, and conditional rendering of filtered vs recent chat/user lists.

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

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group">
          <input v-model="keyword" class="form-control form-control-sidebar" type="text" placeholder="Search"
            aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>
      <nav class="mt-2">

        <ChatList :chats="chats"></ChatList>

        <UserList :users="users"></UserList>

        <li v-if="isLoadingMore" class="nav-item text-center text-light p-2">
          <i class="fas fa-spinner fa-spin"></i> Loading...
        </li>
      </nav>
    </div>
  </aside>
</template>
<script setup>
import emptyImage from "@/assets/images/emptyImage.png";
import logoImage from "@/assets/images/logoImage.webp";
import { useUserStore } from "@/stores/user";
import { ref, onMounted, watch, computed } from "vue";
import { apiGetChats, apiGetChatUsers } from "@/functions/api/chat";
import ChatList from "@/components/includes/controls/ChatList.vue";
import UserList from "@/components/includes/controls/UserList.vue";
import $ from "jquery";

const userStore = useUserStore();
const chats = ref([]);
const users = ref([]);

// Pagination state chat
const chatCurrentPage = ref(1);
const chatLastPage = ref(1);

// Pagination state users
const userCurrentPage = ref(1);
const userLastPage = ref(1);

const pageSize = ref(50);
const keyword = ref("");
const isLoadingMore = ref(false);

onMounted(() => {
  generateChats();

  // jQuery infinite scroll on sidebar
  $(".sidebar").on("scroll", async function () {
    if (isLoadingMore.value) {
      return; // Prevent multiple simultaneous fetches
    }

    const $this = $(this);
    const scrollTop = $this.scrollTop();
    const innerHeight = $this.innerHeight();
    const scrollHeight = $this[0].scrollHeight;

    if (scrollTop + innerHeight < scrollHeight - 50) {
      return; // Not near the bottom yet
    }

    isLoadingMore.value = true;

    // load more users
    if (userCurrentPage.value < userLastPage.value) {
      await generateUsers(keyword.value, userCurrentPage.value + 1);
    }

    // load more chats
    if (chatCurrentPage.value < chatLastPage.value) {
      await generateChats(keyword.value, chatCurrentPage.value + 1);
    }

    isLoadingMore.value = false;
  });
});

watch(keyword, async (newKeyword) => {
  if (isLoadingMore.value) {
    return;
  }

  users.value = [];
  chats.value = [];

  isLoadingMore.value = true;

  await Promise.all([
    generateChats(newKeyword, 1,),
    generateUsers(newKeyword, 1),
  ]);

  isLoadingMore.value = false;
});

async function generateChats(
  searchKeyword = "",
  page = 1,
  per_page = pageSize.value,
) {
  const response = await apiGetChats({
    keyword: searchKeyword,
    page: page,
    per_page: per_page,
  });

  chats.value = [...chats.value, ...response.data.chats];

  chatCurrentPage.value = response.data.meta.current_page;
  chatLastPage.value = response.data.meta.last_page;
}

async function generateUsers(
  searchKeyword = "",
  page = 1,
  per_page = pageSize.value,
) {
  const response = await apiGetChatUsers({
    keyword: searchKeyword,
    page: page,
    per_page: per_page,
  });

  users.value = [...users.value, ...response.data.users];

  userCurrentPage.value = response.data.meta.current_page;
  userLastPage.value = response.data.meta.last_page;
}
</script>
```

