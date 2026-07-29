# ChatSystem — Frontend WebSocket integration with Laravel Echo and real-time event listeners for instant chat and message updates

## Table of Contents

- [What Changed in Session 8.4](#what-changed-in-session-84)
- [File Contents](#file-contents)
  - [vuejs-app/package.json](#vuejs-apppackagejson)
  - [vuejs-app/.env](#vuejs-appenv)
  - [vuejs-app/src/functions/echo.js](#vuejs-apprcfunctionsechojs)
  - [vuejs-app/src/main.js](#vuejs-apprcmainjs)
  - [vuejs-app/src/stores/recentChats.js](#vuejs-appsrcstoresrecentchatsjs)
  - [vuejs-app/src/components/includes/LeftSidebar.vue](#vuejs-apprccomponentsincludesleftsidebarvue)
- [How Each File Works](#how-each-file-works)
  - [Laravel Echo Configuration](#laravel-echo-configuration)
  - [Custom Sanctum Authorization](#custom-sanctum-authorization)
  - [Store Event Subscriptions](#store-event-subscriptions)
  - [Real-Time Event Listeners](#real-time-event-listeners)
- [Common Commands](#common-commands)

---

## What Changed in Session 8.4

Session 8.2 implemented the frontend send and delete message functionality that completes the frontend CRUD operations for text messages, featuring a two-way bound message input field with character limit validation, a send message handler that posts new messages and syncs them to the store before clearing the input and auto-scrolling to bottom, and a delete message handler with SweetAlert confirmation dialog. Session 8.4 implements the frontend WebSocket integration to consume real-time event broadcasts from the Laravel Reverb backend, enabling instant chat and message updates across all connected clients without polling or manual refresh. The session installs `laravel-echo@^2.4.0` and `pusher-js@^8.6.0` npm packages to provide the JavaScript WebSocket client, adds Reverb environment variables to `vuejs-app/.env` including `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, and `VITE_REVERB_SCHEME` that expose the server connection credentials to the frontend build, creates `vuejs-app/src/functions/echo.js` to configure the Laravel Echo instance with Reverb broadcaster settings and a custom Sanctum-aware authorizer that reads the authentication token from localStorage and posts it to `/api/broadcasting/auth` for channel authorization, updates `vuejs-app/src/main.js` to import and initialize the echo.js configuration on application startup, updates `vuejs-app/src/stores/recentChats.js` to add `subscribeToChatEvents()` method that listens to `ChatEvent.{userId}` private channel for `.ChatCreated`, `.ChatUpdated`, and `.ChatDeleted` events and syncs them to the store, adds `subscribeToChatMessageEvents(chatId)` method that subscribes to `ChatMessageEvent.{chatId}` private channel for `.ChatMessageCreated`, `.ChatMessageUpdated`, and `.ChatMessageDeleted` events with memoization to prevent duplicate subscriptions, adds `unsubscribeFromChatMessageEvents(chatId)` method to clean up subscriptions when chats are removed, and updates `vuejs-app/src/components/includes/LeftSidebar.vue` to call `recentChatsStore.subscribeToChatEvents()` in the `onMounted()` hook to initialize WebSocket listeners as soon as the sidebar loads.

| Area | Session 8.2 | Session 8.4 |
|---|---|---|
| WebSocket packages | Not installed | laravel-echo ^2.4.0 and pusher-js ^8.6.0 installed |
| Reverb environment variables | Not present | VITE_REVERB_APP_KEY, HOST, PORT, SCHEME added |
| Echo configuration | Not implemented | echo.js created with Reverb broadcaster and Sanctum authorizer |
| Echo initialization | Not performed | Imported and initialized in main.js |
| Chat event subscription | Not implemented | subscribeToChatEvents() listening to ChatEvent channel |
| Message event subscription | Not implemented | subscribeToChatMessageEvents() listening to ChatMessageEvent channel |
| Real-time chat updates | Not supported | ChatCreated, ChatUpdated, ChatDeleted handled in store |
| Real-time message updates | Not supported | ChatMessageCreated, ChatMessageUpdated, ChatMessageDeleted handled in store |
| Subscription cleanup | Not implemented | unsubscribeFromChatMessageEvents() removes channel subscriptions |
| Event dispatcher initialization | Not performed | Called in LeftSidebar onMounted() to start listening |
| Sanctum authentication | Not used for WebSocket | Custom authorizer reads token from localStorage and posts to /api/broadcasting/auth |
| Infinite scroll pagination | Still used for initial load | WebSocket events instantly append/update/remove data |

`vuejs-app/package.json` was modified by command when running `npm install laravel-echo@^2.4.0 pusher-js@^8.6.0 --save-dev` to add the JavaScript WebSocket client packages as dev dependencies. `vuejs-app/.env` was edited manually to add four Reverb environment variables: `VITE_REVERB_APP_KEY=1ygtul9tn5uvyaxwjqp7` (the app key matching the backend config), `VITE_REVERB_HOST="localhost"` (the WebSocket server hostname in development), `VITE_REVERB_PORT=8080` (the port Reverb listens on), and `VITE_REVERB_SCHEME="http"` (the WebSocket protocol scheme, http for ws:// and https for wss://). `vuejs-app/src/functions/echo.js` was created manually to configure and initialize the global `window.Echo` object that the entire application uses for WebSocket communication, importing `laravel-echo` and `pusher-js`, setting `window.Pusher = Pusher` for compatibility, creating an Echo instance with `broadcaster: 'reverb'` to use the Reverb driver, pulling connection credentials from Vite environment variables, and implementing a custom `authorizer` function that intercepts channel subscription requests, extracts the Sanctum authentication token from localStorage using `localStorage.getItem('SANCTUM-TOKEN')`, and posts it to the backend's `/api/broadcasting/auth` endpoint with the `socket_id` and `channel_name` to get authorization signature before completing the subscription. `vuejs-app/src/main.js` was edited manually to add `import "@/functions/echo.js";` after the Bootstrap and AdminLTE imports so that the Echo WebSocket client initializes as soon as the Vue application boots, ensuring all Vue components can immediately access the global `window.Echo` object and subscribe to channels. `vuejs-app/src/stores/recentChats.js` was edited manually to add three new methods for WebSocket event handling: `subscribeToChatEvents()` that calls `window.Echo.private('ChatEvent.{userId}')` with the user ID from the userStore, then chains `.listen('.ChatCreated', ...)`, `.listen('.ChatUpdated', ...)`, and `.listen('.ChatDeleted', ...)` callbacks to sync chat changes to the store using existing `syncChat()` and `removeChat()` methods; `subscribeToChatMessageEvents(chatId)` that checks a module-level `subscribedChatMessageIds` Set to prevent duplicate subscriptions, calls `window.Echo.private('ChatMessageEvent.{chatId}')`, listens for `.ChatMessageCreated`, `.ChatMessageUpdated`, and `.ChatMessageDeleted` events, syncs them using `syncChatMessage()` and `removeChatMessage()` methods, and marks the chatId as subscribed in the Set; and `unsubscribeFromChatMessageEvents(chatId)` that calls `window.Echo.leave('ChatMessageEvent.{chatId}')` to clean up the subscription and removes the chatId from the Set when a chat is deleted. `vuejs-app/src/components/includes/LeftSidebar.vue` was edited manually to call `recentChatsStore.subscribeToChatEvents()` at the end of the `onMounted()` hook so that WebSocket listeners for chat events are initialized as soon as the sidebar component mounts, ensuring the user receives real-time notifications for chat creation, updates, and deletions from other users and other browser tabs.

---

## File Contents

The labels below tell you what action to take:
- **Modified by command** — package manager command updated the file; no content block provided.
- **Created manually** — file does not exist and no CLI command creates it; paste the block to create it.
- **Edited manually** — file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/package.json`

> **Modified by command** — install Laravel Echo and Pusher.js packages for WebSocket client functionality.

```bash
npm install laravel-echo@^2.4.0 pusher-js@^8.6.0 --save-dev
```

---

### `vuejs-app/.env`

> **Edited manually** — add Reverb environment variables for WebSocket server connection and authentication.

```env
VITE_APP_URL=http://localhost:5173
VITE_APP_VERIFY_EMAIL_URL=http://localhost:5173/verify/email
VITE_APP_RESET_PASSWORD_URL=http://localhost:5173/set-new-password
VITE_APP_GOOGLE_OAUTH_CALLBACK_URL=http://localhost:5173/google/oauth/callback


VITE_APP_API_URL=http://localhost:8000/api

VITE_REVERB_APP_KEY=1ygtul9tn5uvyaxwjqp7
VITE_REVERB_HOST="localhost"
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME="http"
```

---

### `vuejs-app/src/functions/echo.js`

> **Created manually** — initialize Laravel Echo WebSocket client with Reverb broadcaster and custom Sanctum-aware channel authorizer.

```js
import axios from "axios";

import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

window.Echo = new Echo({
  broadcaster: "reverb",
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
  enabledTransports: ["ws", "wss"],
  authorizer: (channel) => {
    return {
      authorize: (socketId, callback) => {
        axios
          .post(
            import.meta.env.VITE_APP_API_URL + "/broadcasting/auth",
            {
              socket_id: socketId,
              channel_name: channel.name,
            },
            {
              headers: {
                Authorization:
                  "Bearer " + (localStorage.getItem("SANCTUM-TOKEN") || ""),
              },
            },
          )
          .then((response) => callback(null, response.data))
          .catch((error) => callback(error));
      },
    };
  },
});
```

---

### `vuejs-app/src/main.js`

> **Edited manually** — import and initialize the Echo WebSocket client configuration on application startup.

```js
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "admin-lte/dist/js/adminlte.min.js";
import "@/functions/echo.js";

import { createApp } from "vue";
import { createPinia } from "pinia";
import piniaPluginPersistedstate from "pinia-plugin-persistedstate";
import App from "./App.vue";
import router from "./router";
import axios from "axios";
import { useUserStore } from "@/stores/user";
import { apiVerify } from "@/functions/api/auth";

const app = createApp(App);

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);
app.use(pinia);
app.use(router);
app.mount("#app");

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
  if (guarded === undefined) {
    // if the route is not guarded, we don't need to verify the token
    return;
  }

  try {
    const response = await apiVerify();
    const { data } = response;
    userStore.setState(data.user);
  } catch (error) {
    if (error.response && error.response.status === 401) {
      userStore.reset();
    }
  }

  if (guarded && !userStore.isAuthenticated) {
    // if the route is guarded and the user is not authenticated, redirect to signin page
    return { name: "auth.signin" };
  }
  if (!guarded && userStore.isAuthenticated) {
    // if the route is not guarded and the user is authenticated, redirect to dashboard page
    return { name: "dashboard" };
  }
});
```

---

### `vuejs-app/src/stores/recentChats.js`

> **Edited manually** — add WebSocket event listener subscriptions for real-time chat and message updates from Reverb broadcasts.

```js
import { defineStore } from "pinia";
import { useUserStore } from "@/stores/user";

// Track subscribed chat channels (module-level, not in state)
const subscribedChatMessageIds = new Set();

export const useRecentChatsStore = defineStore("recentChats", {
  state: () => ({
    chats: [],
  }),
  getters: {
    // Reactive getter - automatically updates components when store changes
    getChatById: (state) => (chatId) => {
      return state.chats.find((chat) => chat.id === Number(chatId)) || null;
    },
    // Get all chats sorted
    getAllChats: (state) => state.chats,
  },
  actions: {
    sortChatMessages(chat) {
      chat.messages.sort((a, b) => {
        return new Date(a.created_at) - new Date(b.created_at);
      });
    },
    sortChats() {
      // Sort messages within each chat first
      this.chats.forEach((chat) => {
        this.sortChatMessages(chat);
      });

      // Then sort chats by the date of the last message
      this.chats.sort((a, b) => {
        const lastMessageA =
          a.messages.length > 0
            ? new Date(a.messages[a.messages.length - 1].created_at)
            : new Date(a.created_at);
        const lastMessageB =
          b.messages.length > 0
            ? new Date(b.messages[b.messages.length - 1].created_at)
            : new Date(b.created_at);
        return lastMessageB - lastMessageA;
      });
    },
    syncMultiChats(chats) {
      chats.forEach((chat) => {
        const index = this.chats.findIndex(
          (c) => Number(c.id) === Number(chat.id),
        );
        if (index !== -1) {
          this.chats[index] = chat;
        } else {
          this.chats.push(chat);
        }
        this.subscribeToChatMessageEvents(chat.id); // Subscribe to chat message events for each chat
      });
      this.sortChats();
    },
    syncChat(chat) {
      // Update existing chat or add if not found
      const index = this.chats.findIndex(
        (c) => Number(c.id) === Number(chat.id),
      );
      if (index !== -1) {
        this.chats[index] = chat;
      } else {
        this.chats.push(chat);
      }
      this.subscribeToChatMessageEvents(chat.id); // Subscribe to chat message events for each chat
      this.sortChats();
    },
    removeChat(chatId) {
      // Remove chat from store
      this.chats = this.chats.filter((c) => Number(c.id) !== Number(chatId));
      this.unsubscribeFromChatMessageEvents(chatId);
    },
    syncMultiChatMessages(chatId, messages) {
      const chat = this.getChatById(chatId);
      if (chat) {
        messages.forEach((message) => {
          const index = chat.messages.findIndex(
            (m) => Number(m.id) === Number(message.id),
          );
          if (index !== -1) {
            chat.messages[index] = message;
          } else {
            chat.messages.push(message);
          }
        });
        this.sortChats();
      }
    },
    syncChatMessage(chatId, message) {
      const chat = this.getChatById(chatId);
      if (chat) {
        const index = chat.messages.findIndex(
          (m) => Number(m.id) === Number(message.id),
        );
        if (index !== -1) {
          chat.messages[index] = message;
        } else {
          chat.messages.push(message);
        }
        this.sortChats();
      }
    },
    removeChatMessage(chatId, messageId) {
      const chat = this.getChatById(chatId);
      if (chat) {
        chat.messages = chat.messages.filter(
          (m) => Number(m.id) !== Number(messageId),
        );
        this.sortChats();
      }
    },
    subscribeToChatEvents() {
      const userStore = useUserStore();
      window.Echo.private(`ChatEvent.${userStore.id}`)
        .listen(".ChatCreated", async ({ chat }) => {
          console.log("ChatCreated event received:", chat);
          this.syncChat(chat);
        })
        .listen(".ChatUpdated", async ({ chat }) => {
          this.syncChat(chat);
        })
        .listen(".ChatDeleted", ({ chat_id }) => {
          this.removeChat(chat_id);
        });
    },
    subscribeToChatMessageEvents(chatId) {
      // Check if already subscribed
      if (subscribedChatMessageIds.has(chatId)) {
        return;
      }

      window.Echo.private(`ChatMessageEvent.${chatId}`)
        .listen(".ChatMessageCreated", async ({ message }) => {
          console.log("ChatMessageCreated event received:", chatId, message);
          this.syncChatMessage(chatId, message);
        })
        .listen(".ChatMessageUpdated", async ({ message }) => {
          this.syncChatMessage(chatId, message);
        })
        .listen(".ChatMessageDeleted", async ({ message_id }) => {
          this.removeChatMessage(chatId, message_id);
        });

      // Mark as subscribed
      subscribedChatMessageIds.add(chatId);
    },

    unsubscribeFromChatMessageEvents(chatId) {
      if (subscribedChatMessageIds.has(chatId)) {
        window.Echo.leave(`ChatMessageEvent.${chatId}`);
        subscribedChatMessageIds.delete(chatId);
      }
    },
  },
});
```

---

### `vuejs-app/src/components/includes/LeftSidebar.vue`

> **Edited manually** — initialize chat event WebSocket subscriptions when the sidebar component mounts.

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
import { useRecentChatsStore } from "@/stores/recentChats";
import { ref, onMounted, watch, computed } from "vue";
import { apiGetChats, apiGetChatUsers } from "@/functions/api/chat";
import ChatList from "@/components/includes/controls/ChatList.vue";
import UserList from "@/components/includes/controls/UserList.vue";
import $ from "jquery";
import { useRoute } from "vue-router";

const route = useRoute();
watch(route, (newRoute) => {
  keyword.value = "";
});

const userStore = useUserStore();
const recentChatsStore = useRecentChatsStore();

const chats = computed(() => recentChatsStore.chats);
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
  recentChatsStore.subscribeToChatEvents();
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

  recentChatsStore.syncMultiChats([...chats.value, ...response.data.chats]);

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

---

## How Each File Works

### Laravel Echo Configuration

The `vuejs-app/src/functions/echo.js` file is the single point of configuration for all WebSocket communication in the frontend. It imports the `laravel-echo` package which provides a fluent API for subscribing to channels and listening for events, and imports `pusher-js` which provides the Pusher protocol client that Reverb implements. Setting `window.Pusher = Pusher` makes the Pusher library globally available as required by Laravel Echo. The `new Echo({...})` instantiation creates a global `window.Echo` singleton that every Vue component can access for WebSocket operations. The configuration uses `broadcaster: 'reverb'` to specify the Reverb driver, pulls the app key from `VITE_REVERB_APP_KEY` environment variable to authenticate with the Reverb server, sets `wsHost` and `wsPort` to connect to `localhost:8080` in development, and uses `wssPort` for secure connections in production. The `forceTLS` setting controls whether to force secure WebSocket connections based on the `VITE_REVERB_SCHEME` environment variable. The `enabledTransports: ['ws', 'wss']` explicitly enables both insecure and secure WebSocket transports.

### Custom Sanctum Authorization

The `authorizer` option in the Echo configuration defines how channel subscriptions are authorized for private channels. When a Vue component calls `window.Echo.private('ChannelName')`, Echo first needs to get authorization before the WebSocket connection can complete the subscription. The custom authorizer intercepts this by defining an `authorize` function that receives the socket ID and a callback. Inside this function, the code reads the Sanctum authentication token from browser localStorage using `localStorage.getItem('SANCTUM-TOKEN')` (which was set during login), then uses axios to POST to `/api/broadcasting/auth` with the `socket_id` and `channel_name`, including the Sanctum token in the `Authorization: Bearer` header to identify the authenticated user. The Laravel backend receives this request, checks the authorization callback in `routes/channels.php`, and if authorized returns a signature that the client uses in the `callback(null, response.data)` to complete the subscription. If authorization fails, the callback is invoked with an error `callback(error)` and the subscription is rejected.

### Store Event Subscriptions

The `recentChatsStore` uses Pinia state management to hold all chats and messages. The new `subscribeToChatEvents()` action sets up listeners on the user-specific `ChatEvent.{userId}` private channel. This channel receives three events: `.ChatCreated` when a new chat is created (broadcasted to all other members), `.ChatUpdated` when a group chat's details are modified, and `.ChatDeleted` when a chat is removed. Each event listener receives the payload from the `broadcastWith()` method in the backend event class and syncs it to the store using existing mutations. The `subscribeToChatMessageEvents(chatId)` action sets up listeners on the chat-specific `ChatMessageEvent.{chatId}` private channel. This channel receives three events for message operations. To prevent duplicate subscriptions when the same chat is loaded multiple times (e.g., from different browser tabs), the method checks a module-level `subscribedChatMessageIds` Set and returns early if already subscribed. The `unsubscribeFromChatMessageEvents(chatId)` action explicitly unsubscribes from a chat's message channel using `window.Echo.leave()` and removes the chat ID from the subscription tracker. This cleanup is important to avoid memory leaks and prevent receiving events for chats that no longer exist in the sidebar.

### Real-Time Event Listeners

When a user sends a message, the backend's `ChatController::createChatMessage()` calls `broadcast(new ChatMessageCreated(...))` which sends the event to all members' WebSocket connections subscribed to `ChatMessageEvent.{chatId}`. The frontend's message event listener receives this with the full `ChatMessageResource` payload including the creator details, then calls `this.syncChatMessage(chatId, message)` which updates the store's chat data structure by finding the message in the chat's message array, updating it if it exists or appending it if it's new, then calling `this.sortChats()` to re-order both messages within each chat and chats by most recent activity. Similarly, when a user deletes a message, the `.ChatMessageDeleted` event listener receives just the `message_id`, then calls `this.removeChatMessage(chatId, messageId)` to filter the message out of the store. For chat events, when a new chat is created, the `.ChatCreated` listener receives the full `ChatResource` with all messages and members preloaded, calls `this.syncChat(chat)` to add it to the store, and automatically subscribes to that chat's message events via the `subscribeToChatMessageEvents(chat.id)` call inside `syncChat()`. This ensures that as soon as a new chat appears in the sidebar, the frontend is already subscribed to receive real-time messages for that chat. The event listeners use `async` callbacks even though most logic is synchronous, allowing for future expansion to perform additional async operations like toast notifications or analytics before syncing to the store.

---

## Common Commands

```bash
# Install Laravel Echo and Pusher.js packages
npm install laravel-echo@^2.4.0 pusher-js@^8.6.0 --save-dev

# Rebuild frontend assets to include new WebSocket packages
npm run build

# Start frontend development server to test WebSocket integration
npm run dev

# View browser console logs to debug WebSocket events
# Open DevTools (F12) → Console tab and look for:
# "ChatCreated event received:" and "ChatMessageCreated event received:"
```
