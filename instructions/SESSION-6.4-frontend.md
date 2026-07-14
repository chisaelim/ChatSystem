# ChatSystem — Frontend chat UI with list components, datetime formatting, and API integration

## Table of Contents

- [What Changed in Session 6.4-frontend](#what-changed-in-session-64-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/package.json](#vuejs-apppackagejson)
  - [vuejs-app/src/functions/datetime.js](#vuejs-appsrcfunctionsdatetimejs)
  - [vuejs-app/src/functions/api/chat.js](#vuejs-appsrcfunctionsapichatjs)
  - [vuejs-app/src/components/includes/controls/ChatList.vue](#vuejs-appsrccomponentsincludescontrolschatlistvue)
  - [vuejs-app/src/components/includes/controls/UserList.vue](#vuejs-appsrccomponentsincludescontrolsuserlistvue)
  - [vuejs-app/src/components/includes/LeftSidebar.vue](#vuejs-appsrccomponentsincludesleftsidebarvue)
  - [vuejs-app/src/main.css](#vuejs-appsrcmaincss)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 6.4-frontend

Session 6.3 implemented the complete REST API layer for chat operations with form requests, resource transformers, and two API endpoints. Session 6.4-frontend implements the frontend UI components for displaying chats and available users in the sidebar, introducing a datetime utility class (`datetime.js`) that converts between UTC and local timezones, formats message timestamps in human-readable relative formats (e.g., "5 min ago", "Yesterday"), an API service module (`chat.js`) that wraps the two backend chat endpoints with axios, two reusable Vue 3 list components (`ChatList.vue` and `UserList.vue`) that display paginated lists of chats and users with avatars, names, timestamps, and last message previews, a refactored `LeftSidebar.vue` that replaces a placeholder search component with the new list controls and integrates API calls on component mount, and custom CSS styling for chat items with positioned elements for avatars, names, timestamps, last message content, and animated activity icons. The datetime utility uses moment.js for timezone conversion and intelligent relative time formatting that shows "now" for recent messages, minute/hour offsets for same-day messages, day names for recent dates, and formatted dates for older messages. The chat list component displays the last message preview with conditional "You:" prefix styling and message read status indicators; the user list component shows available users to start new conversations with.

| Area | Session 6.3 | Session 6.4-frontend |
|---|---|---|
| Chat display UI | No UI | ChatList component displays user's chats with avatars, names, timestamps, last messages |
| Available users UI | No UI | UserList component displays users not in existing chats |
| Datetime formatting | No utilities | Utility class with UTC/local conversion and relative timestamp formatting |
| Sidebar search integration | No chat integration | Integrated ChatList and UserList components into LeftSidebar |
| Chat API service | Direct endpoint calls in backend | Service module wrapping chat endpoints with axios |
| Last message previews | N/A | Displayed with conditional "You:" prefix and read/unread styling |
| Timezone handling | N/A | Automatic UTC to local conversion for all timestamps |
| Activity indicators | N/A | Animated icons showing message type capabilities (plane, comment, microphone) |
| Styling for chat items | N/A | Positioned layout with ellipsis text truncation for names and messages |
| Moment.js library | Not installed | Installed as ^2.30.1 for timezone and date utilities |

`vuejs-app/package.json` and `vuejs-app/package-lock.json` were modified by running `npm install moment ^2.30.1`. `vuejs-app/src/functions/datetime.js` was created manually as a new utility module providing three exported functions for timezone conversion and timestamp formatting. `vuejs-app/src/functions/api/chat.js` was created manually as a new API service module that wraps the two chat endpoints. `vuejs-app/src/components/includes/controls/ChatList.vue` was created manually as a new Vue 3 single-file component displaying a list of chats. `vuejs-app/src/components/includes/controls/UserList.vue` was created manually as a new Vue 3 single-file component displaying a list of available users. `vuejs-app/src/components/includes/LeftSidebar.vue` existed previously and was edited manually to integrate the two list components and API calls. `vuejs-app/src/main.css` existed previously and was edited manually to add CSS classes for chat item styling and animations.

---

## File Contents

The labels below each heading tell you what action to take:
- **Modified by command** — a package manager command installed or updated dependencies; paste the install command only.
- **Created manually** — the file does not exist and no CLI command creates it; paste the block.
- **Edited manually** — the file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom. The package.json must be updated first by installing moment. The datetime utility class must be created before ChatList uses it. The API service module must be created before LeftSidebar uses it. The ChatList and UserList components must be created before LeftSidebar imports them. LeftSidebar is updated last to integrate all components.

---

### `vuejs-app/package.json`

> **Modified by command** — install the moment package for timezone and date manipulation utilities.

```bash
npm install moment
```

---

### `vuejs-app/src/functions/datetime.js`

> **Created manually** — export three utility functions for UTC/local timezone conversion and human-readable timestamp formatting.

```javascript
import moment from 'moment';

/**
 * Convert UTC datetime from backend to local timezone
 * Backend always sends UTC, so we parse as UTC and convert to local
 */
export function utcToLocal(utcDateTime) {
  if (!utcDateTime) return null;
  // Parse as UTC and convert to local timezone
  return moment.utc(utcDateTime).local();
}

/**
 * Convert local datetime to UTC for sending to backend
 * Takes local time and converts to UTC string
 */
export function localToUtc(localDateTime) {
  if (!localDateTime) return null;
  // Parse as local and convert to UTC
  return moment(localDateTime).utc().format('YYYY-MM-DD HH:mm:ss');
}

/**
 * Format chat timestamp for display
 * Shows relative time (5 min ago), or date for older messages
 * Expects UTC datetime from backend
 */
export function formatChatTime(dateTime) {
  if (!dateTime) return '';

  const now = moment();
  const msgTime = moment(dateTime);
  const diffMinutes = now.diff(msgTime, 'minutes');
  const diffHours = now.diff(msgTime, 'hours');
  const diffDays = now.diff(msgTime, 'days');

  // Less than 1 minute
  if (diffMinutes < 1) return 'now';

  // Less than 1 hour
  if (diffHours < 1) return `${diffMinutes} min ago`;

  // Today
  if (diffDays === 0) return msgTime.format('HH:mm');

  // Yesterday
  if (diffDays === 1) return 'Yesterday';

  // Within last week
  if (diffDays < 7) return msgTime.format('ddd');

  // Older messages
  return msgTime.format('DD/MM/YY');
}
```

---

### `vuejs-app/src/functions/api/chat.js`

> **Created manually** — export two async functions that wrap the chat API endpoints with environment-based URL configuration.

```javascript
import axios from "axios";

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export async function apiGetChats(params = {}) {
  return await axios.get(APP_API_URL + "/chats", { params });
}
export async function apiGetChatUsers(params = {}) {
  return await axios.get(APP_API_URL + "/chats/users", { params });
}
```

---

### `vuejs-app/src/components/includes/controls/ChatList.vue`

> **Created manually** — render a list of chats with avatars, names, last message previews, timestamps, and activity indicators using Vue 3 composition API.

```vue
<template>
  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
    <li class="nav-item" v-for="chat in chats" :key="chat.id">
      <a role="button" class="nav-link" active-class="active">
        <img class="nav-icon img-circle elevation-3 my-1" :src="chat.avatar || emptyImage" />
        <p class="chat-name">{{ chat.name }}</p>
        <p class="chat-datetime">
          {{ lastMessage(chat) ? formatChatTime(lastMessage(chat).created_at) : "" }}
        </p>
        <br />
        <p class="chat-message mt-1">
          <span v-if="isOwnMessage(lastMessage(chat))" :class="'text-bold'">You:
          </span>
          <span v-if="!lastMessage(chat)" class="text-bold">Start a new conversation</span>
          <span v-else :class="isSeen(lastMessage(chat)) || isOwnMessage(lastMessage(chat)) ? '' : 'text-bold'">{{
            lastMessage(chat).content
            }}</span>
        </p>
        <p class="chat-activity-icon">
          <i class="far fa-paper-plane"></i>
          <i class="far fa-comment-dots"></i>
          <i class="fas fa-microphone"></i>
        </p>
      </a>
    </li>
  </ul>
</template>


<script setup>
import emptyImage from "@/assets/images/emptyImage.png";
import { formatChatTime } from "@/functions/datetime";
import { useUserStore } from '@/stores/user';

const userStore = useUserStore();
const props = defineProps({
  chats: {
    type: Array,
    required: true,
  },
});

function lastMessage(chat) {
  return chat.messages[chat.messages.length - 1] || null;
}

function isOwnMessage(message) {
  if (!message) return false;
  return !(message.creator.id === userStore.id);
}

function isSeen(message) {
  if (!message) return false;
  return message.seen_at !== null;
}
</script>
```

---

### `vuejs-app/src/components/includes/controls/UserList.vue`

> **Created manually** — render a list of available users with avatars and names for starting new conversations using Vue 3 composition API.

```vue
<template>
  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
    <li class="nav-item" v-for="user in users" :key="user.id">
      <a role="button" class="nav-link">
        <img class="nav-icon img-circle elevation-3 my-1" :src="user.profile_image || emptyImage" />
        <p class="chat-name">{{ user.name }}</p>
        <br />
        <p class="chat-message mt-1">
          <span class="text-bold text-muted">Start a new conversation</span>
        </p>
      </a>
    </li>
  </ul>
</template>

<script setup>
import emptyImage from "@/assets/images/emptyImage.png";
const props = defineProps({
  users: {
    type: Array,
    required: true,
  },
});
</script>
```

---

### `vuejs-app/src/components/includes/LeftSidebar.vue`

> **Edited manually** — refactor the sidebar by replacing the placeholder search component with ChatList and UserList, fetching data from the chat API endpoints on component mount.

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
          <input class="form-control form-control-sidebar" type="text" placeholder="Search" aria-label="Search">
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
      </nav>
    </div>
  </aside>
</template>
<script setup>
import emptyImage from '@/assets/images/emptyImage.png';
import logoImage from '@/assets/images/logoImage.webp';
import { useUserStore } from '@/stores/user';
import { ref, onMounted } from 'vue';
import { apiGetChats, apiGetChatUsers } from '@/functions/api/chat';
import ChatList from '@/components/includes/controls/ChatList.vue';
import UserList from '@/components/includes/controls/UserList.vue';
const userStore = useUserStore();
const chats = ref([]);
const users = ref([]);

onMounted(() => {
  generateChats();
  generateUsers();
});
async function generateChats() {
  const response = await apiGetChats();
  chats.value = response.data.chats;
}
async function generateUsers() {
  const response = await apiGetChatUsers();
  users.value = response.data.users;
}
</script>
```

---

### `vuejs-app/src/main.css`

> **Edited manually** — add custom CSS classes for chat item styling including positioned text, ellipsis truncation, and animated activity icons.

```css
@import url('https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback');
@import '@fortawesome/fontawesome-free/css/all.min.css';
@import 'icheck-bootstrap/icheck-bootstrap.min.css';
@import 'admin-lte/dist/css/adminlte.min.css';

.nav-item p.chat-name {
    position: absolute;
    left: 55px;
    top: 13px;
    font-weight: bold;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 125px;
    /* or any specific width/max-width */
    display: inline-block;
    /* or inline-block */
}

.nav-item p.chat-datetime {
    font-size: 0.75rem;
    position: absolute;
    right: 5px;
    top: 2px;
}

.nav-item p.chat-message {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 185px;
    /* or any specific width/max-width */
    display: inline-block;
    /* or inline-block */
}

.nav-item p.chat-activity-icon {
    font-size: 1rem;
    position: absolute;
    right: 10px;
    bottom: 10px;
    animation: blinker 1.5s linear infinite;
}

@keyframes blinker {
    0% {
        opacity: 1;
        /* Fully visible */
    }

    50% {
        opacity: 0;
        /* Hidden */
    }

    100% {
        opacity: 1;
        /* Fully visible, optional if infinite is used */
    }
}
```

---

## How Each File Works

### Datetime utility class — timezone conversion and timestamp formatting

The `datetime.js` module exports three functions for handling timestamps across the application. `utcToLocal(utcDateTime)` takes a UTC timestamp from the backend (always in UTC format) and converts it to a moment.js object in the browser's local timezone, handling the conversion with `moment.utc(utcDateTime).local()` and returning null if the input is empty. `localToUtc(localDateTime)` performs the reverse operation, taking a local moment.js object and converting it to a UTC-formatted string suitable for sending back to the backend. `formatChatTime(dateTime)` generates human-readable relative timestamps suitable for chat UI display: it calculates the time difference from now in minutes, hours, and days, then returns 'now' for messages less than one minute old, a minute offset like "5 min ago" for same-hour messages, the time in HH:mm format for same-day messages, 'Yesterday' for one-day-old messages, day names (Mon, Tue, etc.) for messages within the past week, and formatted dates (DD/MM/YY) for older messages. All functions expect UTC input from the backend and handle null/undefined inputs safely.

### Chat API service module — endpoint wrapping with axios

The `chat.js` module provides two async functions that wrap the backend API endpoints with axios HTTP client. Both functions retrieve the `APP_API_URL` from Vite environment variables (`import.meta.env.VITE_APP_API_URL`), ensuring the correct API base URL is used across different deployment environments. `apiGetChats()` makes a GET request to `/chats` and returns the full axios response object (containing data, status, headers, etc.). `apiGetChatUsers()` makes a GET request to `/chats/users` and returns the full axios response object. These functions are intentionally simple wrappers designed to be called from Vue components; error handling is typically done at the component level using try/catch or promise .catch() handlers.

### ChatList component — rendering chats with previews and activity indicators

The `ChatList.vue` component displays a list of user's chats in a navigation menu format. It receives a `chats` array prop containing chat objects from the API, each with id, name, avatar, and messages array (up to 25 recent messages). For each chat, it renders a list item with: an avatar image (with fallback to empty image), the chat name (positioned absolutely, truncated with ellipsis), a timestamp of the last message (positioned top-right, formatted using `formatChatTime()`), and a message preview showing the last message content. The message preview includes conditional styling: if the last message was sent by the current user, it prefixes with "You:" and applies different text styling based on whether the message is seen (gray if read, bold if unread); if there are no messages, it shows "Start a new conversation". Three activity icons (paper-plane, comment-dots, microphone) are displayed in the bottom-right corner of each chat item and are animated with a blinking effect using CSS animations. The component uses three helper functions: `lastMessage(chat)` retrieves the last element from the chat's messages array, `isOwnMessage(message)` checks if the message creator is not the current user (inverted logic due to the API response structure), and `isSeen(message)` checks if the message has a non-null `seen_at` timestamp.

### UserList component — rendering available users for new chats

The `UserList.vue` component displays a list of available users for starting new conversations. It receives a `users` array prop containing user objects from the API, each with id, name, profile_image, and other profile fields. For each user, it renders a list item with: an avatar image (the user's profile_image or fallback to empty image), the user's name, and static placeholder text "Start a new conversation" to indicate this is an action to initiate a chat. The component structure mirrors ChatList for consistency in the sidebar navigation, using the same layout patterns and styling classes. This component is intentionally simple to support a future interactive click handler that would trigger chat creation logic.

### LeftSidebar component — integration of chat lists and API calls

The `LeftSidebar.vue` component orchestrates the chat system UI integration. In the template, it preserves the existing navigation menu (Dashboard, Users, Backups admin links) and replaces the placeholder search widget with: a simplified search form (input field removed of the search-results div which was showing "no element found"), and two new nav containers that import and render the `ChatList` and `UserList` components with `:chats="chats"` and `:users="users"` prop bindings. In the script setup, it declares two reactive refs (`chats` and `users`) initialized as empty arrays, uses the `onMounted()` lifecycle hook to call two async functions (`generateChats()` and `generateUsers()`) when the component first renders, and each async function calls the corresponding API service function and assigns the response data to the reactive ref. The component imports the user store to access profile data and stores the two list components as local imports.

### Main CSS styling — layout and animations for chat items

The `main.css` file adds four custom CSS classes for styling chat list items. `.nav-item p.chat-name` positions the chat/user name absolutely at coordinates (55px from left, 13px from top), applies bold font weight, truncates overflowing text with ellipsis, and constrains width to 125px to prevent overlap with the timestamp. `.nav-item p.chat-datetime` positions the message timestamp at (right: 5px, top: 2px) with reduced font size (0.75rem) for subtle display. `.nav-item p.chat-message` truncates the last message content with ellipsis, constrains width to 185px, and uses white-space: nowrap to prevent text wrapping. `.nav-item p.chat-activity-icon` positions three activity icons at the bottom-right corner (right: 10px, bottom: 10px) and applies an infinite blinking animation. The `@keyframes blinker` animation transitions opacity from 1 (visible) to 0 (hidden) and back to 1 over 1.5 seconds, creating a smooth pulsing effect that repeats indefinitely. All position: absolute classes work in conjunction with the parent list item's implicit position: relative, stacking elements into a compact chat preview card.

---

## Common Commands

```bash
# Install moment.js dependency
npm install moment@^2.30.1

# Start the development server
npm run dev

# Build for production
npm run build

# Test API endpoints in browser console
curl -H "Authorization: Bearer TOKEN" "http://localhost:8000/api/chats"
curl -H "Authorization: Bearer TOKEN" "http://localhost:8000/api/chats/users"

# View the sidebar with chat and user lists
# Navigate to http://localhost:5173 in your browser
```
