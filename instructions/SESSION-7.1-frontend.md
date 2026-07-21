# ChatSystem — Frontend chat UI components, Pinia store integration, and API client functions

## Table of Contents

- [What Changed in Session 7.1](#what-changed-in-session-71)
- [File Contents](#file-contents)
  - [vuejs-app/src/stores/recentChats.js](#vuejs-appstoresrecentchatsjs)
  - [vuejs-app/src/functions/api/chat.js](#vuejs-appfunctionsapichatjs)
  - [vuejs-app/src/router/index.js](#vuejs-approuterindexjs)
  - [vuejs-app/src/components/pages/ChatBox.vue](#vuejs-appcomponentspageschatboxvue)
  - [vuejs-app/src/components/pages/ChatCreate.vue](#vuejs-appcomponentspagescchatcreatevue)
  - [vuejs-app/src/components/pages/ChatDetail.vue](#vuejs-appcomponentspageschatdetailvue)
  - [vuejs-app/src/components/includes/LeftSidebar.vue](#vuejs-appcomponentsincludesleftsidebarvue)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 7.1

Session 7.0 implemented the backend chat management API endpoints with comprehensive request validation and authorization. Session 7.1 completes the full-stack chat system by creating a comprehensive frontend implementation, adding new Vue components for chat interactions, integrating a Pinia store for centralized chat state management, expanding the API client service with six new functions wrapping backend endpoints, registering three new router paths for chat flows, and updating the sidebar to use the store and sync chat state reactively. A new Pinia store module `recentChatsStore` manages all chat data in reactive state, providing getters for chat retrieval by ID and actions for syncing individual or multiple chats, adding, removing, and sorting chats by latest message date. Six new API client functions (`apiCreatePersonalChat`, `apiCreateGroupChat`, `apiReadChat`, `apiDeleteChat`, `apiUpdateGroupChat`, `apiLeaveGroupChat`) encapsulate HTTP calls to the corresponding backend endpoints, automatically handling FormData serialization for multipart file uploads. Three new components enable users to create group chats, view chat details and metadata, and display the main chat conversation interface. The router configuration adds three new routes mapped to these components with proper authentication guards. The LeftSidebar component is refactored to use the Pinia store for displaying recent chats, eliminating the local `recentChats` ref and replacing it with a computed property backed by the store. All components integrate seamlessly with the store and API layer, ensuring consistent state across the application.

| Area | Session 7.0 | Session 7.1 |
|---|---|---|
| Chat state management | Backend only | Pinia store with reactive getters/actions |
| Chat creation API | Backend routes only | Frontend `apiCreatePersonalChat()` and `apiCreateGroupChat()` |
| Chat reading API | Backend route only | Frontend `apiReadChat()` function |
| Chat deletion API | Backend route only | Frontend `apiDeleteChat()` function |
| Chat update API | Backend route only | Frontend `apiUpdateGroupChat()` function |
| Group exit API | Backend route only | Frontend `apiLeaveGroupChat()` function |
| Create chat UI | Not implemented | `ChatCreate.vue` component with image upload |
| Chat details UI | Not implemented | `ChatDetail.vue` component with metadata editing |
| Chat conversation UI | Not implemented | `ChatBox.vue` component (skeleton) |
| Router paths | Basic pages only | Three new chat-related routes |
| Sidebar integration | Hardcoded lists | Pinia store with reactive recentChats computed |
| Route watchers | None | Route change listener to clear search keyword |

`vuejs-app/src/stores/recentChats.js` was created manually to define the Pinia store with centralized chat state management. `vuejs-app/src/functions/api/chat.js` was edited manually to add six new API client functions wrapping backend endpoints. `vuejs-app/src/router/index.js` was edited manually to register three new routes for chat creation, details, and box views, plus quote style formatting changes. `vuejs-app/src/components/pages/ChatBox.vue` was created manually as a skeleton component for the chat conversation interface. `vuejs-app/src/components/pages/ChatCreate.vue` was created manually to provide a form for creating group chats with avatar upload and canvas-based image cropping. `vuejs-app/src/components/pages/ChatDetail.vue` was created manually to display and edit chat metadata (name, description, avatar) with group-specific operations. `vuejs-app/src/components/includes/LeftSidebar.vue` was edited manually to integrate the Pinia store, use computed properties for recent chats, add a route watcher to clear search, and call `syncMultiChats()` instead of direct array assignment.

---

## File Contents

The labels below tell you what action to take:
- **Created manually** — file does not exist and no CLI command creates it; paste the block to replace its contents.
- **Edited manually** — file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/stores/recentChats.js`

> **Created manually** — define Pinia store for centralized reactive chat state management with getters and actions for CRUD operations.

```js
import { defineStore } from "pinia";

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
    sortChats() {
      // replace old chats with new ones and sort them by last message date

      this.chats.sort((a, b) => {
        const lastMessageA =
          a.messages.length > 0
            ? new Date(a.messages[a.messages.length - 1].updated_at)
            : new Date(a.created_at);
        const lastMessageB =
          b.messages.length > 0
            ? new Date(b.messages[b.messages.length - 1].updated_at)
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
      this.sortChats();
    },
    removeChat(chatId) {
      // Remove chat from store
      this.chats = this.chats.filter((c) => Number(c.id) !== Number(chatId));
    },
    clearAllChats() {
      // Clear all chats (useful for logout or sync with empty database)
      this.chats = [];
    },
    replaceAllChats(chats) {
      // Replace entire chat list (for database sync)
      this.chats = chats;
      this.sortChats();
    },
  },
});
```

---

### `vuejs-app/src/functions/api/chat.js`

> **Edited manually** — add six new API client functions wrapping backend chat endpoints: create personal/group chats, read, delete, update, and leave groups.

```js
import axios from "axios";

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export async function apiGetChats(params = {}) {
  return await axios.get(APP_API_URL + "/chats", { params });
}

export async function apiGetChatUsers(params = {}) {
  return await axios.get(APP_API_URL + "/chats/users", { params });
}

export async function apiCreatePersonalChat(userId) {
  return await axios.post(APP_API_URL + `/chats/personal/create`, {
    user_id: userId,
  });
}

export async function apiCreateGroupChat(data) {
  const formData = new FormData();
  Object.keys(data).forEach((key) => {
    if (!data[key]) return;
    formData.append(key, data[key]);
  });
  return await axios.post(APP_API_URL + "/chats/group/create", formData);
}

export async function apiReadChat(chatId) {
  return await axios.get(APP_API_URL + `/chats/read/${chatId}`);
}

export async function apiDeleteChat(chatId) {
  return await axios.delete(APP_API_URL + `/chats/delete/${chatId}`);
}

export async function apiUpdateGroupChat(chatId, data) {
  const formData = new FormData();
  Object.keys(data).forEach((key) => {
    if (!data[key]) return;
    formData.append(key, data[key]);
  });
  return await axios.put(
    APP_API_URL + `/chats/group/update/${chatId}`,
    formData,
  );
}

export async function apiLeaveGroupChat(chatId) {
  return await axios.delete(APP_API_URL + `/chats/group/leave/${chatId}`);
}
```

---

### `vuejs-app/src/router/index.js`

> **Edited manually** — register three new chat-related routes (create, details, box) and update import/quote formatting for consistency.

```js
import Profile from "@/components/auth/Profile.vue";
import ResetPassword from "@/components/auth/ResetPassword.vue";
import SetNewPassword from "@/components/auth/SetNewPassword.vue";
import Signin from "@/components/auth/Signin.vue";
import Signout from "@/components/auth/Signout.vue";
import Signup from "@/components/auth/Signup.vue";
import VerifyEmail from "@/components/auth/VerifyEmail.vue";
import GoogleOAuth from "@/components/google-oauth/GoogleOAuth.vue";
import Dashboard from "@/components/pages/Dashboard.vue";
import User from "@/components/pages/User.vue";
import Backup from "@/components/pages/Backup.vue";
import { createRouter, createWebHistory } from "vue-router";

import Navbar from "@/components/includes/Navbar.vue";
import LeftSidebar from "@/components/includes/LeftSidebar.vue";
import RightSidebar from "@/components/includes/RightSidebar.vue";
import Footer from "@/components/includes/Footer.vue";
import ChatCreate from "@/components/pages/ChatCreate.vue";
import ChatDetail from "@/components/pages/ChatDetail.vue";
import ChatBox from "@/components/pages/ChatBox.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "auth.signin",
      component: Signin,
      meta: { guarded: false },
    },
    {
      path: "/signout",
      name: "auth.signout",
      component: Signout,
      // This route has no guarded meta because it use for both authenticated and unauthenticated users.
      // The authentication state will be handled in the Signout component.
    },
    {
      path: "/signup",
      name: "auth.signup",
      component: Signup,
      meta: { guarded: false },
    },
    {
      path: "/verify/email",
      name: "auth.verify.email",
      component: VerifyEmail,
      meta: { guarded: false },
    },
    {
      path: "/reset-password",
      name: "auth.reset-password",
      component: ResetPassword,
      meta: { guarded: false },
    },
    {
      path: "/set-new-password",
      name: "auth.set-new-password",
      component: SetNewPassword,
      meta: { guarded: false },
    },
    {
      path: "/google/oauth/callback",
      name: "auth.google.oauth.callback",
      component: GoogleOAuth,
      meta: { guarded: false },
    },
    {
      path: "/dashboard",
      name: "dashboard",
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
      path: "/profile",
      name: "profile",
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
      path: "/users",
      name: "users",
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
      path: "/backups",
      name: "backups",
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
      path: "/chat/create",
      name: "chat.create",
      components: {
        default: ChatCreate,
        navbar: Navbar,
        left_sidebar: LeftSidebar,
        right_sidebar: RightSidebar,
        footer: Footer,
      },
      meta: { guarded: true },
    },
    {
      path: "/chat/:chatId/details",
      name: "chat.details",
      components: {
        default: ChatDetail,
        navbar: Navbar,
        left_sidebar: LeftSidebar,
        right_sidebar: RightSidebar,
        footer: Footer,
      },
      props: { default: true },
      meta: { guarded: true },
    },
    {
      path: "/chat/:chatId",
      name: "chat.box",
      components: {
        default: ChatBox,
        navbar: Navbar,
        left_sidebar: LeftSidebar,
        right_sidebar: RightSidebar,
        footer: Footer,
      },
      props: { default: true },
      meta: { guarded: true },
    },
    {
      path: "/:pathMatch(.*)*",
      redirect: "/dashboard",
    },
  ],
});

export default router;
```

---

### `vuejs-app/src/components/pages/ChatBox.vue`

> **Created manually** — skeleton component for rendering the main chat conversation interface with message display area and toolbar.

```vue
<template>
  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <div class="card card-primary card-outline direct-chat direct-chat-primary">
          <div class="card-header d-flex align-items-center">
            <h3 class="card-title">
              <img class="direct-chat-img elevation-3" :src="emptyImage" />
            </h3>
            <h3 class="card-title mx-3"></h3>
            <div class="card-tools ml-auto">
              <RouterLink :to="{ name: 'chat.details', params: { chatId: props.chatId } }" type="button"
                class="btn btn-tool">
                <i class="fas fa-list text-primary"></i>
              </RouterLink>
            </div>
          </div>
          <div class="card-body">

          </div>
          <div class="card-footer">

          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import emptyImage from "@/assets/images/emptyImage.png";

const props = defineProps({
  chatId: {
    required: true,
  },
});
</script>
```

---

### `vuejs-app/src/components/pages/ChatCreate.vue`

> **Created manually** — form component for creating group chats with name, description, and avatar upload featuring client-side image cropping to 454×454 pixels.

```vue
<template>
  <div class="content-wrapper" style="min-height: 1175px;">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Create Group Chat</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Create Group Chat</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
    <div class="content">
      <div class="container-fluid">
        <!-- Profile Image -->
        <div class="card card-primary card-outline">
          <div class="card-body box-profile">
            <form @submit.prevent="createGroup">
              <div class="form-group text-center">
                <img class="profile-user-img img-fluid img-circle" :src="chat.avatar || emptyImage"
                  :class="{ 'is-invalid': !!chatError.avatar }" alt="User profile picture" />
                <div class="invalid-feedback">{{ chatError.avatar }}</div>
                <input @change="onChangeImage" :accept="allowedExtensions.map((ext) => '.' + ext).join(', ')"
                  type="file" class="d-none" id="file-input" />
                <div class="mt-1">
                  <label :for="'file-input'">
                    <a type="button" class="m-1 btn btn-primary btn-sm"><i class="fas fa-upload"></i></a>
                  </label>
                  <a type="button" @click="onDeleteImage" class="m-1 btn btn-danger btn-sm"><i
                      class="fas fa-trash"></i></a>
                </div>
              </div>
              <div class="form-group">
                <label for="userEmail">Name</label>
                <input type="text" class="form-control" v-model="chat.name"
                  :class="{ 'is-invalid': !!chatError.name }" />
                <div class="invalid-feedback">{{ chatError.name }}</div>
              </div>
              <div class="form-group">
                <label for="userEmail">Description</label>
                <textarea class="form-control" v-model="chat.description"
                  :class="{ 'is-invalid': !!chatError.description }"></textarea>
                <div class="invalid-feedback">{{ chatError.description }}</div>
              </div>
              <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Create Group</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref } from "vue";
import emptyImage from "@/assets/images/emptyImage.png";
import { MessageModal, LoadingModal, CloseModal } from "@/functions/swal";
import { apiCreateGroupChat } from "@/functions/api/chat";
import { useRouter } from "vue-router";
import { useRecentChatsStore } from "@/stores/recentChats";
const recentChatsStore = useRecentChatsStore();
const router = useRouter();

const selectedImage = ref(null);
const chat = reactive({
  id: null,
  name: "",
  description: "",
  avatar: null,
});
const chatError = reactive({
  name: "",
  description: "",
  avatar: "",
});

const defaultChat = JSON.parse(JSON.stringify(chat));
const defaultChatError = JSON.parse(JSON.stringify(chatError));

function resetAllState() {
  Object.assign(chat, defaultChat);
  Object.assign(chatError, defaultChatError);
}

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

          selectedImage.value = new File([blob], "profile.png", { type: "image/png" });
          chat.avatar = canvas.toDataURL("image/png");
        }, "image/png");
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(files[0]);
    event.target.value = null;
  }
}
function onDeleteImage() {
  selectedImage.value = null;
  chat.avatar = null;
}

async function createGroup() {
  try {
    LoadingModal('Creating Group...');
    const payload = {
      name: chat.name,
      description: chat.description,
      avatar: selectedImage.value,
    };
    const response = await apiCreateGroupChat(payload);
    const { data } = response;
    resetAllState();
    return MessageModal({ icon: "success", title: "Success", text: data.message }, () => {
      recentChatsStore.syncChat(data.chat);
      router.push({ name: "chat.box", params: { chatId: data.chat.id } });
    });
  } catch (error) {
    const { response } = error;
    if (!response) {
      return MessageModal({ icon: "error", title: "Error", text: error.message });
    }
    const { status, data } = response;
    if (status === 422) {
      Object.keys(chatError).forEach((key) => {
        chatError[key] = data.errors[key]
          ? data.errors[key][0]
          : "";
      });
      return CloseModal();
    }
    return MessageModal({ icon: "error", title: "Error", text: data.message });
  }
}
</script>
```

---

### `vuejs-app/src/components/pages/ChatDetail.vue`

> **Created manually** — component for viewing and editing chat metadata (name, description, avatar) with conditional group-specific operations (update, leave) and delete functionality for all chat types.

```vue
<template>
  <div class="content-wrapper" style="min-height: 1175px;">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Update Group Chat</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Update Group Chat</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
    <div class="content">
      <div class="container-fluid">
        <!-- Profile Image -->
        <div class="card card-primary card-outline">
          <div class="card-body box-profile">
            <form @submit.prevent="updateGroup">
              <div class="form-group text-center">
                <img class="profile-user-img img-fluid img-circle" :src="chat.avatar || emptyImage"
                  :class="{ 'is-invalid': !!chatError.avatar }" alt="User profile picture" />
                <div class="invalid-feedback">{{ chatError.avatar }}</div>
                <input @change="onChangeImage" :accept="allowedExtensions.map((ext) => '.' + ext).join(', ')"
                  type="file" class="d-none" id="file-input" />
                <div class="mt-1" v-if="chatType === 'group'">
                  <label :for="'file-input'">
                    <a type="button" class="m-1 btn btn-primary btn-sm"><i class="fas fa-upload"></i></a>
                  </label>
                  <a type="button" @click="onDeleteImage" class="m-1 btn btn-danger btn-sm"><i
                      class="fas fa-trash"></i></a>
                </div>
              </div>
              <div class="form-group">
                <label>Name</label>
                <input :disabled="chatType === 'personal'" type="text" class="form-control" v-model="chat.name"
                  :class="{ 'is-invalid': !!chatError.name }" />
                <div class="invalid-feedback">{{ chatError.name }}</div>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea :disabled="chatType === 'personal'" class="form-control" v-model="chat.description"
                  :class="{ 'is-invalid': !!chatError.description }"></textarea>
                <div class="invalid-feedback">{{ chatError.description }}</div>
              </div>
              <template v-if="chatType === 'group'">
                <div class="form-group">
                  <button type="submit" class="btn btn-primary btn-block">Update Chat</button>
                </div>
                <div class="form-group">
                  <button type="button" @click="leaveGroupChat" class="btn btn-warning btn-block">Leave Chat</button>
                </div>
                <div class="form-group">
                  <button type="button" @click="deleteChat" class="btn btn-danger btn-block">Delete Chat</button>
                </div>
              </template>
              <template v-else>
                <div class="form-group">
                  <button type="button" @click="deleteChat" class="btn btn-danger btn-block">Delete Chat</button>
                </div>
              </template>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref, watch } from "vue";
import emptyImage from "@/assets/images/emptyImage.png";
import { MessageModal, LoadingModal, CloseModal } from "@/functions/swal";
import { apiUpdateGroupChat, apiReadChat, apiDeleteChat, apiLeaveGroupChat } from "@/functions/api/chat";
import { useRouter } from "vue-router";
import { useRecentChatsStore } from "@/stores/recentChats";
import Swal from "sweetalert2";
const recentChatsStore = useRecentChatsStore();
const router = useRouter();

const props = defineProps({
  chatId: {
    required: true,
  },
});

watch(
  () => props.chatId,
  async (newChatId) => {
    await readChat();
  },
  { immediate: true }
);

const chatType = ref('personal'); // Default to 'personal', will be updated after reading chat

const selectedImage = ref(null);
const originalAvatar = ref(null);
const avatarDeleted = ref(false);
const chat = reactive({
  id: null,
  name: "",
  description: "",
  avatar: null,
});
const chatError = reactive({
  name: "",
  description: "",
  avatar: "",
});

const defaultChat = JSON.parse(JSON.stringify(chat));
const defaultChatError = JSON.parse(JSON.stringify(chatError));

function resetAllState() {
  Object.assign(chat, defaultChat);
  Object.assign(chatError, defaultChatError);
}

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

          selectedImage.value = new File([blob], "profile.png", { type: "image/png" });
          chat.avatar = canvas.toDataURL("image/png");
          avatarDeleted.value = false;
        }, "image/png");
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(files[0]);
    event.target.value = null;
  }
}
function onDeleteImage() {
  selectedImage.value = null;
  chat.avatar = null;
  avatarDeleted.value = true;
}

async function readChat() {
  try {
    LoadingModal("Loading...");
    const response = await apiReadChat(props.chatId);
    const { data } = response;
    Object.assign(chat, data.chat);
    originalAvatar.value = data.chat.avatar;
    avatarDeleted.value = false;
    selectedImage.value = null;
    chatType.value = data.chat.type; // Update chat type based on the fetched data
    CloseModal();
  } catch (error) {
    const { response } = error;
    if (!response) {
      return MessageModal({ icon: "error", title: "Error", text: error.message });
    }
    const { data } = response;
    return MessageModal({ icon: "error", title: "Error", text: data.message });
  }
}

async function updateGroup() {
  try {
    LoadingModal('Updating Group...');

    const payload = {
      name: chat.name,
      description: chat.description,
    };

    // Handle avatar updates based on user actions
    if (selectedImage.value) {
      // New avatar file uploaded - send the file
      payload.avatar = selectedImage.value;
    } else if (avatarDeleted.value) {
      // Avatar was explicitly deleted - send null to delete
      payload.avatar = null;
    }
    // If neither condition is true, don't include avatar (keeps existing)

    const response = await apiUpdateGroupChat(props.chatId, payload);
    const { data } = response;
    resetAllState();
    Object.assign(chat, data.chat);
    originalAvatar.value = data.chat.avatar;
    avatarDeleted.value = false;
    selectedImage.value = null;
    chatType.value = data.chat.type; // Update chat type based on the fetched data
    recentChatsStore.syncChat(data.chat);
    return MessageModal({ icon: "success", title: "Success", text: data.message });
  } catch (error) {
    const { response } = error;
    if (!response) {
      return MessageModal({ icon: "error", title: "Error", text: error.message });
    }
    const { status, data } = response;
    if (status === 422) {
      Object.keys(chatError).forEach((key) => {
        chatError[key] = data.errors[key]
          ? data.errors[key][0]
          : "";
      });
      return CloseModal();
    }
    return MessageModal({ icon: "error", title: "Error", text: data.message });
  }
}

async function deleteChat() {
  Swal.fire({
    icon: "question",
    title: "Delete Chat",
    text: "Are you sure you want to delete this chat?",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal('Deleting chat...');
        const response = await apiDeleteChat(props.chatId);
        const { data } = response;
        recentChatsStore.removeChat(props.chatId);
        return MessageModal({ icon: "success", title: "Success", text: data.message }, () => {
          router.push({ name: "dashboard" });
        });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
      }
    }
  });
}

async function leaveGroupChat() {
  Swal.fire({
    icon: "question",
    title: "Leave Group Chat",
    text: "Are you sure you want to leave this group chat?",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, leave it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal('Leaving group...');
        const response = await apiLeaveGroupChat(props.chatId);
        const { data } = response;
        recentChatsStore.removeChat(props.chatId);
        return MessageModal({ icon: "success", title: "Success", text: data.message }, () => {
          router.push({ name: "dashboard" });
        });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
      }
    }
  });
}
</script>
```

---

### `vuejs-app/src/components/includes/Navbar.vue`

> **Edited manually** — add a new "Create Group Chat" button to the navbar for quick access to the chat creation page.

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
        <RouterLink class="nav-link" :to="{ name: 'chat.create' }" role="button">
          <i class="fas fa-comment-medical text-primary"></i>
        </RouterLink>
      </li>
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

> **Edited manually** — integrate Pinia store for chat state, replace local recentChats ref with computed property, add route watcher to clear search, and use `syncMultiChats()` for pagination updates.

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

### Pinia Store (recentChatsStore) — Centralized reactive chat state management

The `recentChatsStore` Pinia store manages all recent chats in a single centralized reactive state array. The `state()` function initializes an empty `chats` array to hold all chat objects. Two getters provide reactive access: `getChatById` is a factory function that searches the state by numeric chat ID and returns the matching chat or null, enabling efficient lookups; `getAllChats` simply returns the full sorted chats array. The `actions` object contains six methods for manipulating state: `sortChats()` sorts the chats array by latest message timestamp in descending order (most recent first), using message `updated_at` if messages exist, otherwise falling back to the chat's `created_at`; `syncMultiChats(chats)` iterates an array of chat objects, updating existing chats by ID or appending new ones, then calls `sortChats()` to maintain order; `syncChat(chat)` performs the same upsert operation for a single chat; `removeChat(chatId)` filters out the chat by numeric ID; `clearAllChats()` resets the array to empty; `replaceAllChats(chats)` replaces the entire array and re-sorts. All mutations trigger Vue reactivity automatically, so any component using `recentChatsStore.chats` or calling its getters will update whenever state changes.

### API Client Functions — HTTP wrappers for chat endpoints

The six new API client functions in `chat.js` wrap the corresponding backend REST endpoints, abstracting HTTP details from Vue components. `apiCreatePersonalChat(userId)` sends a simple JSON POST with the target user ID; `apiCreateGroupChat(data)` builds a FormData object from the data parameter, skipping falsy values, then sends a multipart POST to support file uploads; `apiReadChat(chatId)` performs a simple GET request; `apiDeleteChat(chatId)` sends a DELETE request; `apiUpdateGroupChat(chatId, data)` converts data to FormData and sends a PUT request to support optional avatar file updates; `apiLeaveGroupChat(chatId)` sends a POST request to trigger member removal. All functions use a centralized `APP_API_URL` from the Vite environment config and return the full axios response object, allowing caller components to access `response.data` and error details via `error.response`.

### ChatBox Component — Chat conversation display skeleton

The `ChatBox.vue` component serves as a skeleton container for rendering the main chat conversation interface. It accepts a `chatId` prop to identify which chat to display and renders a card layout with a header containing a chat avatar placeholder, optional title, and a toolbar button linking to the chat details page. The card body and footer are currently empty placeholders for future implementation of message display and input controls. The component imports the `emptyImage` asset for the avatar placeholder and uses Vue Router's `RouterLink` for navigation.

### ChatCreate Component — Group chat creation form with image processing

The `ChatCreate.vue` component provides a form for creating group chats with image upload and client-side processing. Reactive state tracks `selectedImage` (the processed File object), `chat` object with name, description, and avatar data-URL preview, and `chatError` validation messages. The `onChangeImage()` handler reads the selected file as a data URL, creates an Image element to load it, then uses HTML5 Canvas to center-crop and resize the image to exactly 454×454 pixels before converting the canvas to a blob and creating a new File. If validation fails (non-image, wrong format), a SweetAlert modal displays the error. The `createGroup()` async function collects form data, calls `apiCreateGroupChat()`, and on success syncs the new chat to the store and navigates to the chat box. Validation errors from the API (422 status) are displayed in the form's error divs. The component calls `resetAllState()` after successful creation to clear the form.

### ChatDetail Component — Chat metadata editing and operations

The `ChatDetail.vue` component allows viewing and editing chat metadata (name, description, optional avatar) with conditional group-specific operations. A `chatId` prop triggers a watcher that calls `readChat()` on mount and whenever the prop changes, loading the chat from the API and determining chat type. The component disables name/description fields for personal chats (read-only mode) and shows update/leave buttons only for group chats, while the delete button is always visible. State tracks `selectedImage`, `avatarDeleted` flag, and `originalAvatar`. The `onChangeImage()` and `onDeleteImage()` handlers mirror `ChatCreate` logic. The `updateGroup()` function intelligently handles avatar updates: if a new file is selected, it includes the file; if the user clicked delete, it sends null; otherwise, it omits the avatar field to preserve the existing one. The `deleteChat()` and `leaveGroupChat()` functions display confirmation dialogs and call the corresponding API functions, updating the store and redirecting on success.

### LeftSidebar Refactoring — Store integration and route awareness

The `LeftSidebar.vue` component is refactored to use the Pinia `recentChatsStore` instead of managing a local `recentChats` ref. A new computed property `recentChats` returns `recentChatsStore.chats`, creating a reactive binding that updates whenever the store state changes. The component now imports and injects `useRecentChatsStore` and watches the current route object; whenever the route changes, the keyword search is cleared. In `generateChats()`, when not filtering, the code calls `recentChatsStore.syncMultiChats()` with a combined array of existing store chats and newly fetched chats, allowing pagination to incrementally load more chats into the store while maintaining proper sorting. This eliminates the previous direct array assignment and ensures all chat mutations flow through store actions, maintaining a single source of truth.

### Router Configuration — Three new guarded chat routes

The router imports three new page components (`ChatCreate`, `ChatDetail`, `ChatBox`) and registers three new routes: `/chat/create` named `chat.create` for group creation, `/chat/:chatId/details` named `chat.details` for editing metadata, and `/chat/:chatId` named `chat.box` for the conversation interface. All three are registered with the same layout structure (navbar, sidebar, footer) and have `meta: { guarded: true }` to enforce authentication. The routes that include dynamic `:chatId` parameters define `props: { default: true }` to pass route params as component props.

---

## Common Commands

```bash
# Serve the frontend dev server
npm run dev

# Build for production
npm run build

# Navigate to create group chat form
# Browser: http://localhost:5173/chat/create

# Navigate to edit chat details
# Browser: http://localhost:5173/chat/1/details

# Open chat conversation view
# Browser: http://localhost:5173/chat/1

# Test chat creation with image (in browser console)
# Simulates form submission
const chat = { name: 'Team A', description: 'Awesome team', avatar: imageFile };
await apiCreateGroupChat(chat);

# Test store operations (in browser console)
# Import and use the store
const { useRecentChatsStore } = await import('@/stores/recentChats');
const store = useRecentChatsStore();
console.log(store.chats); // View all chats
console.log(store.getChatById(1)); // Get specific chat
store.removeChat(1); // Remove chat from store
store.clearAllChats(); // Clear all chats

# Test API functions (in browser console)
# Verify endpoint connectivity
const response = await apiReadChat(1);
console.log(response.data);
```
