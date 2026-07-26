# ChatSystem — Frontend chat message display with API integration, store synchronization, and infinite scroll pagination

## Table of Contents

- [What Changed in Session 8.1](#what-changed-in-session-81)
- [File Contents](#file-contents)
  - [vuejs-app/src/functions/api/chat.js](#vuejs-appfunctionsapichatjs)
  - [vuejs-app/src/stores/recentChats.js](#vuejs-appstoresrecentchatsjs)
  - [vuejs-app/src/components/pages/ChatBox.vue](#vuejs-appcomponentspageschatboxvue)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 8.1

Session 8.0 implemented the complete backend API layer for chat message CRUD operations with authorization checks and form request validation. Session 8.1 implements the frontend message display interface that consumes the message API endpoints, featuring a new API wrapper module for chat message operations, expanded store actions to manage message synchronization and sorting, and a fully functional ChatBox component that fetches chats via `apiReadChat`, syncs them to the store, displays paginated messages with infinite scroll pagination that loads older messages when scrolling near the top, maintains scroll position while prepending older messages, and formats message timestamps and identifies the authenticated user's own messages for UI differentiation.

| Area | Session 8.0 | Session 8.1 |
|---|---|---|
| Chat message API endpoints | Fully implemented backend | Consumed by frontend |
| API wrapper functions | Not implemented | 
| Message display | Not implemented | ChatBox displays messages from store |
| Infinite scroll pagination | Not implemented | Load older messages on scroll |
| Store message actions | Not implemented | syncMultiChatMessages, syncChatMessage, removeChatMessage |
| Chat sync to store | Not implemented | apiReadChat integration in ChatBox |
| Message formatting | Not implemented | Timestamp formatting and creator display |
| Message ownership detection | Not implemented | `isOwnMessage()` function |
| Scroll position handling | Not implemented | Maintains scroll position while prepending |

`vuejs-app/src/functions/api/chat.js` was created manually as an API wrapper module providing five functions that call the backend message endpoints via axios: `apiGetChatMessages()` retrieves paginated messages for a chat, `apiCreateChatMessage()` sends a new message, `apiUpdateChatMessage()` edits existing message content, `apiDeleteChatMessage()` removes a message, and `apiMarkAllChatMessagesAsSeen()` marks all non-creator messages as seen in a chat. `vuejs-app/src/stores/recentChats.js` was edited manually to add four new store actions: `sortChatMessages()` sorts a chat's message array chronologically, `syncMultiChatMessages()` updates a chat's message list and maintains sort order, `syncChatMessage()` adds or updates a single message in a chat's list, and `removeChatMessage()` deletes a message from a chat's list, all actions trigger `sortChats()` to keep chats ordered by most recent activity. `vuejs-app/src/components/pages/ChatBox.vue` was edited manually to completely refactor the component, replacing the previous placeholder template with a functional message display interface that iterates over `chat.messages`, implements `loadChat()` to fetch chat data via `apiReadChat()` and sync it to the store, implements `loadMessages()` pagination that fetches from the API and merges new messages with existing ones via `syncMultiChatMessages()`, implements infinite scroll listener that triggers `loadMoreMessages()` when scrolling near the top, manages scroll position preservation via `previousScrollHeight` tracking, provides `isOwnMessage()` to identify the user's own messages, `formatMessageDate()` to display timestamps, and lifecycle hooks that initialize the chat display on mount and whenever the chat ID prop changes.

---

## File Contents

The labels below tell you what action to take:
- **Created manually** — file does not exist and no CLI command creates it; paste the block to replace its contents.
- **Edited manually** — file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/functions/api/chat.js`

> **Created manually** — API wrapper module with five functions for chat message operations (get, create, update, delete, mark-seen).

```javascript
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
    // Allow explicit null values for avatar deletion
    if (data[key] === null) {
      formData.append(key, "");
      return;
    }
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

export async function apiGetGroupChatMembers(chatId, params = {}) {
  return await axios.get(APP_API_URL + `/chats/group/${chatId}/members`, {
    params,
  });
}
export async function apiAddGroupChatMember(chatId, userId) {
  return await axios.post(APP_API_URL + `/chats/group/${chatId}/members/add`, {
    user_id: userId,
  });
}
export async function apiRemoveGroupChatMember(chatId, memberId) {
  return await axios.delete(
    APP_API_URL + `/chats/group/${chatId}/members/remove/${memberId}`,
  );
}

export async function apiGetChatMessages(chatId, params = {}) {
  return await axios.get(APP_API_URL + `/chats/${chatId}/messages`, { params });
}

export async function apiCreateChatMessage(chatId, content) {
  return await axios.post(APP_API_URL + `/chats/${chatId}/messages/create`, {
    content,
  });
}

export async function apiUpdateChatMessage(chatId, messageId, content) {
  return await axios.patch(
    APP_API_URL + `/chats/${chatId}/messages/update/${messageId}`,
    {
      content,
    },
  );
}

export async function apiDeleteChatMessage(chatId, messageId) {
  return await axios.delete(
    APP_API_URL + `/chats/${chatId}/messages/delete/${messageId}`,
  );
}

export async function apiMarkAllChatMessagesAsSeen(chatId) {
  return await axios.post(APP_API_URL + `/chats/${chatId}/messages/seen-all`);
}
```

---

### `vuejs-app/src/stores/recentChats.js`

> **Edited manually** — add four new actions for message management (sort, sync multiple, sync single, remove).

```javascript
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
    syncMultiChatMessages(chatId, messages) {
      const chat = this.getChatById(chatId);
      if (chat) {
        for (const message of messages) {
          const index = chat.messages.findIndex(
            (m) => Number(m.id) === Number(message.id),
          );
          if (index !== -1) {
            chat.messages[index] = message;
          } else {
            chat.messages.push(message);
          }
        }
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
  },
});
```

---

### `vuejs-app/src/components/pages/ChatBox.vue`

> **Edited manually** — implement full message display with API integration, store synchronization, pagination, infinite scroll, and scroll position handling.

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
            <div class="direct-chat-messages" style="min-height: calc(100vh - 280px)">
              <template v-for="message in chat?.messages" :key="message.id">
                <div class="direct-chat-msg" :class="isOwnMessage(message) ? 'right' : 'left'">
                  <div class="direct-chat-infos clearfix">
                    <span class="direct-chat-timestamp mx-1"
                      :class="isOwnMessage(message) ? 'float-right' : 'float-left'">{{
                        formatChatTime(message.created_at) }}</span>
                  </div>
                  <img class="direct-chat-img" :src="message.creator.profile_thumbnail || emptyImage"
                    alt="message user image">
                  <div class="direct-chat-text"
                    :class="isOwnMessage(message) ? 'text-right float-right' : 'text-left float-left'">
                    {{ message.content }}
                  </div>
                </div>
                <div class="direct-chat-infos clearfix">
                  <span class="direct-chat-name" :class="isOwnMessage(message) ? 'float-right' : 'float-left'">{{
                    message.creator.name }}</span>
                  <i v-if="isOwnMessage(message)" class="fas fa-trash-alt text-danger float-right mt-1 mx-3"></i>
                </div>
                <hr>
              </template>

            </div>
            <!--/.direct-chat-messages-->
          </div>
          <div class="card-footer">
            <form action="#" method="post">
              <div class="input-group">
                <input type="text" name="message" placeholder="Type Message ..." class="form-control">
                <span class="input-group-append">
                  <button type="button" class="btn btn-primary">Send</button>
                </span>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { watch, ref, onMounted, nextTick, computed } from "vue";
import emptyImage from "@/assets/images/emptyImage.png";
import { useUserStore } from '@/stores/user';
import { useRecentChatsStore } from "@/stores/recentChats";
import { formatChatTime } from "@/functions/datetime";
import { apiGetChatMessages } from "@/functions/api/chat";
import $ from "jquery";
import { apiReadChat } from "@/functions/api/chat";

const userStore = useUserStore();
const recentChatsStore = useRecentChatsStore();

const props = defineProps({
  chatId: {
    required: true,
  },
});

// Local state for messages (independent of store)
const chat = computed(() => recentChatsStore.getChatById(props.chatId));


function isOwnMessage(message) {
  if (!message) return false;
  return (message.creator.id === userStore.id);
}

const currentPage = ref(1);
const lastPage = ref(1);
const pageSize = ref(25);
const isLoadingMore = ref(false);

async function loadChat() {
  try {
    if (chat.value) {
      return; // Chat already exists in store
    }
    // If not in store, fetch from API
    const response = await apiReadChat(props.chatId);
    recentChatsStore.syncChat(response.data.chat);
  } catch (error) {
    console.error('Error loading chat:', error);
  }
}

async function loadMessages(page = 1) {
  try {
    // Fetch from API
    const response = await apiGetChatMessages(props.chatId, {
      page: page,
      per_page: pageSize.value,
    });

    recentChatsStore.syncMultiChatMessages(props.chatId, [...response.data.chat_messages, ...chat.value.messages]);

    currentPage.value = response.data.meta.current_page;
    lastPage.value = response.data.meta.last_page;
  } catch (error) {
    console.error('Error loading messages:', error);
  }
}

async function loadMoreMessages() {
  if (isLoadingMore.value) {
    return;
  }

  if (currentPage.value >= lastPage.value) {
    return;
  }

  isLoadingMore.value = true;

  await loadMessages(currentPage.value + 1);

  isLoadingMore.value = false;
}

function scrollToBottom() {
  const chatContainer = $('.direct-chat-messages');
  if (chatContainer.length > 0) {
    console.log('Scrolling to bottom');
    chatContainer.scrollTop(chatContainer[0].scrollHeight);
  }
}

function setupScrollListener() {
  const chatContainer = $('.direct-chat-messages');

  // Remove existing listener
  chatContainer.off('scroll');

  // Add scroll listener for infinite scroll
  chatContainer.on('scroll', async function () {
    if (isLoadingMore.value) {
      return;
    }

    if (currentPage.value >= lastPage.value) {
      return;
    }

    if (currentPage.value >= lastPage.value) {
      return; // No more pages to load
    }
    // Load more when scrolling near the top
    const scrollTop = this.scrollTop;
    if (scrollTop > 150) {
      return; // Not near the top yet
    }
    const previousScrollHeight = this.scrollHeight;
    await loadMoreMessages();

    // Maintain scroll position after prepending messages
    const newScrollHeight = this.scrollHeight;
    this.scrollTop = newScrollHeight - previousScrollHeight + scrollTop;
  });
}

// Watch for chat changes
watch(() => props.chatId, async () => {
  // Reset state
  isLoadingMore.value = false;
  currentPage.value = 1;
  lastPage.value = 1;

  await loadChat();
  // await loadMessages(1);
  scrollToBottom();
  setupScrollListener();
});

// Initial load
onMounted(async () => {
  await loadChat();
  await loadMessages(1);
  scrollToBottom();
  setupScrollListener();
});

</script>
```

---

## How Each File Works

### Chat Message API Module (`chat.js`)

The chat message API module provides a simple wrapper around the five backend message endpoints. Each exported function constructs the correct API endpoint path and makes the corresponding HTTP request via axios. `apiGetChatMessages()` accepts a chat ID and optional query parameters (page, per_page) and returns the paginated message list. `apiCreateChatMessage()` and `apiUpdateChatMessage()` send the message content in the request body. `apiDeleteChatMessage()` and `apiMarkAllChatMessagesAsSeen()` are stateless operations requiring only the chat and message IDs. All functions inherit the Sanctum authentication token automatically through axios interceptors configured elsewhere in the application.

### Recent Chats Store Extensions

Four new actions extend the `recentChatsStore` to manage message-level synchronization. `sortChatMessages()` sorts a specific chat's messages array chronologically by creation date. `syncMultiChatMessages()` replaces a chat's entire message list with a new array (used when pagination loads older messages) and re-sorts both the messages and the chat list. `syncChatMessage()` adds a new message or updates an existing one by ID, useful for real-time updates after create/update operations. `removeChatMessage()` deletes a message by ID from a chat's list, used after successful deletion. All four actions call `sortChats()` to re-sort the chat list by most recent activity, ensuring the store reflects the latest message timestamps.

### ChatBox Component

The refactored ChatBox component integrates API calls and store synchronization to display messages with pagination. The component maintains three pieces of reactive state: `chat` (the current chat object with nested messages), `currentPage` and `lastPage` (pagination tracking), and `isLoadingMore` (flag to prevent concurrent requests). `loadChat()` first checks if the chat exists in the store; if not, it fetches via `apiReadChat()` and syncs to the store via `syncChat()`. `loadMessages()` fetches a page of messages from the API and merges them with existing messages via `syncMultiChatMessages()`, prepending new older messages to maintain chronological order. The infinite scroll listener monitors scroll position and triggers `loadMoreMessages()` when the user scrolls within 100 pixels of the top, preserving scroll position by calculating the difference between the previous and new container heights. Helper functions `isOwnMessage()` checks if a message was created by the authenticated user and `formatMessageDate()` converts UTC timestamps to local timezone-aware format. Lifecycle hooks `onMounted` and the `watch` on `props.chatId` initialize the chat display, reset pagination state on chat switch, and set up scroll listeners.

---

## Common Commands

```bash
# No CLI commands used in this session — all files created or edited manually with Vue, JavaScript, and Pinia patterns.
```
