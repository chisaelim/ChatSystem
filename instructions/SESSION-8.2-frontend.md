# ChatSystem — Frontend chat message send and delete with form validation, SweetAlert confirmation, and auto-scroll

## Table of Contents

- [What Changed in Session 8.2](#what-changed-in-session-82)
- [File Contents](#file-contents)
  - [vuejs-app/src/components/pages/ChatBox.vue](#vuejs-appcomponentspageschatboxvue)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 8.2

Session 8.1 implemented the frontend message display interface that consumes the message API endpoints, featuring infinite scroll pagination that loads older messages when scrolling near the top, store synchronization to manage message data, and message formatting with ownership detection. Session 8.2 implements the send and delete message functionality that completes the frontend CRUD operations for text messages, featuring a two-way bound message input field with character limit validation, a send message handler that posts new messages via `apiCreateChatMessage()` and syncs them to the store before clearing the input and auto-scrolling to bottom, a delete message handler with SweetAlert confirmation dialog that calls `apiDeleteChatMessage()` and removes the message from the store via `removeChatMessage()`, updated imports to include `apiCreateChatMessage` and `apiDeleteChatMessage` from the chat API module and `MessageModal` from the swal utility for consistent error presentation, trash icon click handler with cursor pointer styling and hover title, form submit prevention with `@submit.prevent` directive, input field state management with `messageContent` ref that clears when switching chats, disabled send button when input is empty or whitespace-only, and removal of the unused `nextTick` import.

| Area | Session 8.1 | Session 8.2 |
|---|---|---|
| Message sending | Not implemented | Fully functional with form validation |
| Message deletion | Not implemented | Fully functional with confirmation dialog |
| Message input field | Static placeholder only | Two-way bound with v-model and maxlength |
| Form submission | No handler | @submit.prevent with sendMessage() |
| Send button | Static with no action | Submit type with disabled state binding |
| Trash icon | Display only | Clickable with deleteMessage() handler |
| Error handling | console.error only | MessageModal for user-facing errors |
| Input state management | Not implemented | messageContent ref with chat-switch clearing |
| Auto-scroll after send | Not implemented | scrollToBottom() after successful send |
| Swal integration | Not imported | Confirmation dialog for delete |

`vuejs-app/src/components/pages/ChatBox.vue` was edited manually to implement send and delete message functionality. The template was updated to add `v-model="messageContent"` binding to the message input with `maxlength="5000"`, change the form to use `@submit.prevent="sendMessage"`, convert the send button to `type="submit"` with `:disabled="!messageContent.trim()"` binding, and add `@click="deleteMessage(message.id)"` handler to the trash icon with `style="cursor: pointer;"` and `title="Delete message"` attributes. The script was updated to import `apiCreateChatMessage` and `apiDeleteChatMessage` from the chat API, import `Swal` from sweetalert2 and `MessageModal` from the swal utility, remove the unused `nextTick` import, add `messageContent` ref for input state, implement `sendMessage()` async function that validates non-empty content, calls `apiCreateChatMessage()`, syncs the new message to the store via `syncChatMessage()`, clears the input, auto-scrolls to bottom, and uses `MessageModal` for error display, implement `deleteMessage()` async function that shows a Swal confirmation dialog with warning icon and "Yes, delete it!" button, calls `apiDeleteChatMessage()` if confirmed, removes the message from the store via `removeChatMessage()`, displays success feedback with `MessageModal`, and handles errors with `MessageModal`, and update the chat ID watcher to reset `messageContent` to an empty string when switching chats.

---

## File Contents

The labels below tell you what action to take:
- **Edited manually** — file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/components/pages/ChatBox.vue`

> **Edited manually** — implement send and delete message handlers with form binding, validation, SweetAlert confirmation, and auto-scroll.

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
                    <template v-if="editingMessageId === message.id">
                      <div class="input-group input-group-sm">
                        <input v-model="editContent" type="text" class="form-control" maxlength="5000"
                          @keyup.enter="saveEdit(message.id)" @keyup.esc="cancelEdit">
                        <span class="input-group-append">
                          <button type="button" class="btn btn-success btn-sm" @click="saveEdit(message.id)"
                            :disabled="!editContent.trim()">
                            <i class="fas fa-check"></i>
                          </button>
                          <button type="button" class="btn btn-secondary btn-sm" @click="cancelEdit">
                            <i class="fas fa-times"></i>
                          </button>
                        </span>
                      </div>
                    </template>
                    <template v-else>
                      {{ message.content }}
                    </template>
                  </div>
                </div>
                <div class="direct-chat-infos clearfix">
                  <span class="direct-chat-name" :class="isOwnMessage(message) ? 'float-right' : 'float-left'">{{
                    message.creator.name }}</span>
                  <i v-if="isOwnMessage(message)" @click="deleteMessage(message.id)"
                    class="fas fa-trash-alt text-danger float-right mt-1 mx-1" style="cursor: pointer;"
                    title="Delete message"></i>
                  <i v-if="isOwnMessage(message) && isTextMessage(message)" @click="startEditMessage(message)"
                    class="fas fa-edit text-primary float-right mt-1 mx-1" style="cursor: pointer;"
                    title="Edit message"></i>
                </div>
                <hr>
              </template>

            </div>
            <!--/.direct-chat-messages-->
          </div>
          <div class="card-footer">
            <form @submit.prevent="sendMessage">
              <div class="input-group">
                <input v-model="messageContent" type="text" name="message" placeholder="Type Message ..."
                  class="form-control" maxlength="5000">
                <span class="input-group-append">
                  <button type="submit" class="btn btn-primary" :disabled="!messageContent.trim()">Send</button>
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
import { watch, ref, onMounted, computed } from "vue";
import emptyImage from "@/assets/images/emptyImage.png";
import { useUserStore } from '@/stores/user';
import { useRecentChatsStore } from "@/stores/recentChats";
import { formatChatTime } from "@/functions/datetime";
import { apiGetChatMessages, apiCreateChatMessage, apiUpdateChatMessage, apiDeleteChatMessage, apiMarkAllChatMessagesAsSeen } from "@/functions/api/chat";
import $ from "jquery";
import { apiReadChat } from "@/functions/api/chat";
import Swal from "sweetalert2";
import { MessageModal } from "@/functions/swal";

const userStore = useUserStore();
const recentChatsStore = useRecentChatsStore();

const props = defineProps({
  chatId: {
    required: true,
  },
});

// Local state for messages (independent of store)

// Message input
const messageContent = ref('');
const chat = computed(() => recentChatsStore.getChatById(props.chatId));

// Edit state
const editingMessageId = ref(null);
const editContent = ref('');


function isOwnMessage(message) {
  if (!message) return false;
  return (message.creator.id === userStore.id);
}

function isTextMessage(message) {
  return message?.type === 'text';
}

function startEditMessage(message) {
  editingMessageId.value = message.id;
  editContent.value = message.content;
}

function cancelEdit() {
  editingMessageId.value = null;
  editContent.value = '';
}

async function saveEdit(messageId) {
  if (!editContent.value.trim()) {
    return;
  }

  try {
    const response = await apiUpdateChatMessage(props.chatId, messageId, editContent.value);
    recentChatsStore.syncChatMessage(props.chatId, response.data.chat_message);
    editingMessageId.value = null;
    editContent.value = '';
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}


async function sendMessage() {
  if (!messageContent.value.trim()) {
    return;
  }

  try {
    const response = await apiCreateChatMessage(props.chatId, messageContent.value);

    // Add message to store
    recentChatsStore.syncChatMessage(props.chatId, response.data.chat_message);

    // Clear input
    messageContent.value = '';

    scrollToBottom();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}

async function deleteMessage(messageId) {
  Swal.fire({
    icon: "warning",
    title: "Delete Message",
    text: "Are you sure you want to delete this message?",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const response = await apiDeleteChatMessage(props.chatId, messageId);
        recentChatsStore.removeChatMessage(props.chatId, messageId);
        return MessageModal({ icon: "success", title: "Success", text: response.data.message });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
      }
    }
  });
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

async function markMessagesAsSeen() {
  try {
    await apiMarkAllChatMessagesAsSeen(props.chatId);
  } catch (error) {
    console.error('Error marking messages as seen:', error);
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
  messageContent.value = ''; // Clear message input when switching chats
  editingMessageId.value = null; // Cancel any ongoing edit
  editContent.value = '';

  await loadChat();
  // await loadMessages(1);
  scrollToBottom();
  setupScrollListener();
  await markMessagesAsSeen();
});

// Initial load
onMounted(async () => {
  await loadChat();
  await loadMessages(1);
  scrollToBottom();
  setupScrollListener();
  await markMessagesAsSeen();
});

</script>
```

---

## How Each File Works

### ChatBox Component Send and Delete

The ChatBox component now supports full CRUD operations for text messages. The message input field uses two-way data binding via `v-model="messageContent"` and enforces a 5000-character limit with the `maxlength` attribute matching the backend validation rule. The form uses `@submit.prevent="sendMessage"` to prevent page reload and trigger the custom send handler. `sendMessage()` first validates that the trimmed content is non-empty to prevent whitespace-only submissions, then calls `apiCreateChatMessage()` to post the message to the backend, syncs the returned message object to the store via `syncChatMessage()` which updates the chat's message array and re-sorts the chat list by most recent activity, clears the input field by resetting `messageContent` to an empty string, and calls `scrollToBottom()` to auto-scroll the message container to show the newly sent message. Error handling uses `MessageModal()` from the swal utility to display user-facing error messages with consistent styling. The send button is disabled via `:disabled="!messageContent.trim()"` when the input is empty or contains only whitespace, preventing unnecessary API calls. `deleteMessage()` shows a SweetAlert confirmation dialog with a warning icon, red confirm button, and cancel option, then executes the delete operation only if the user confirms via `result.isConfirmed`. Upon confirmation, it calls `apiDeleteChatMessage()` to remove the message from the backend, removes the message from the store via `removeChatMessage()` which filters the message out of the chat's array and re-sorts the chat list, and displays a success message via `MessageModal()` with the backend response message. The trash icon is only shown for messages where `isOwnMessage()` returns true (matching the backend authorization rule that only allows creators to delete their own messages), includes `@click="deleteMessage(message.id)"` to trigger the delete handler, uses `style="cursor: pointer;"` to indicate interactivity, and displays `title="Delete message"` on hover for accessibility. The chat ID watcher now resets `messageContent` to an empty string when switching between chats, preventing the previous chat's input from carrying over to the new chat. The `nextTick` import was removed because `scrollToBottom()` no longer requires waiting for the next render cycle — the jQuery scroll operation executes synchronously after the store update completes.

---

## Common Commands

```bash
# No CLI commands used in this session — all changes made by manually editing the Vue component.
```
