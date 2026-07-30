# ChatSystem — Frontend voice message UI with browser audio recording and playback

## Table of Contents

- [What Changed in Session 9.1](#what-changed-in-session-91)
- [File Contents](#file-contents)
  - [vuejs-app/src/functions/api/chat.js](#vuejs-appsrcfunctionsapichatjs)
  - [vuejs-app/src/stores/recentChats.js](#vuejs-appsrcstoresrecentchatsjs)
  - [vuejs-app/src/components/pages/ChatBox.vue](#vuejs-appsrccomponentspageschatboxvue)
- [How Each File Works](#how-each-file-works)
  - [Voice Message API Integration](#voice-message-api-integration)
  - [File Blob Management in State](#file-blob-management-in-state)
  - [Voice Recording and Playback UI](#voice-recording-and-playback-ui)
- [Common Commands](#common-commands)

---

## What Changed in Session 9.1

Session 9.0 implemented the backend voice message functionality with file storage, validation, and event broadcasting to allow users to record and upload voice messages to chats with secure file serving. Session 9.1 implements the complete frontend voice message workflow by adding the browser-based audio recording UI and playback capabilities to the ChatBox component, adding an `apiCreateVoiceChatMessage()` function to the chat API module that sends the recorded voice blob as FormData to the backend endpoint, modifying the Pinia store to include a new `loadFile()` action that fetches voice message file blobs from the API and converts them to object URLs for browser playback, extending the store's message sync actions to call `loadFile()` for every synced message so voice files are automatically loaded when messages are added, and updating the ChatBox template to display audio player controls for voice messages, add voice recording state management with timer and recording UI feedback, add microphone permission handling, add send button logic to detect when a voice message is ready to send versus text message input, and add state reset when switching between chats to prevent audio state leakage. The session demonstrates the complete frontend voice message workflow: capturing audio from the user's microphone, storing the recorded blob in component state, sending it to the backend API with proper FormData encoding, syncing the response message into the store, fetching the file blob from the API through the model accessor URL, converting it to a playable object URL, and rendering audio player controls in the chat UI.

| Area | Session 9.0 | Session 9.1 |
|---|---|---|
| Voice message API endpoint | Backend created | Frontend API function added |
| Audio recording | Backend only | Frontend browser MediaRecorder integration |
| Voice file blob | Stored on server | Fetched from API and stored in message objects |
| Message display | Not implemented | Audio player UI renders for voice messages |
| Recording UI | Not needed | Timer, microphone button, recording state display |
| File playback | Not implemented | Browser audio element with controls |
| Message sync | API synchronization only | Also loads file blobs for playback |
| Microphone access | Not requested | Browser permission request with error handling |
| Frontend voice workflow | Not implemented | Complete recording → sending → receiving → playing |

`vuejs-app/src/functions/api/chat.js` was edited manually to add an `apiCreateVoiceChatMessage()` export function that accepts chatId and voiceBlob parameters, creates a FormData object, appends the blob as the `voice` field with filename `voice.webm`, and sends a POST request to `/chats/{chatId}/messages/create-voice` returning the API response containing the created message. `vuejs-app/src/stores/recentChats.js` was edited manually to import axios at the top of the file for HTTP requests, add a new `loadFile()` action that checks if a message is text-type or already has a fileBlob property and returns early to avoid redundant requests, otherwise fetches the file blob from the message's file_path URL with blob response type, and converts the response blob to an object URL stored in the message's fileBlob property for browser playback, modify the `syncMultiChatMessages()` action to call `loadFile()` for every message being synced into the store so file blobs are preloaded, modify the `syncChatMessage()` action to call `loadFile()` for newly synced messages, and remove debug console.log statements that were logging WebSocket event data. `vuejs-app/src/components/pages/ChatBox.vue` was edited manually to add voice recording state variables (isRecording, mediaRecorder, audioChunks, recordedBlob, recordingSeconds, recordingTimer), add `resetRecordingState()` function that clears recording state and stops the active recorder, add `formatRecordingTime()` function that formats seconds as M:SS display format, add `toggleRecording()` async function that uses navigator.mediaDevices.getUserMedia() to request microphone access and create a MediaRecorder to capture audio chunks into a Blob, update the form input section to conditionally display recording timer UI when isRecording is true, display recorded time when a blob is available but not recording, or show the normal text input otherwise, add microphone button to toggle recording state with dynamic button color (red when recording), add delete button for recorded blob, add send button that detects voice blob first before checking text content, add `sendVoiceMessage()` async function that calls the API with the recorded blob and syncs the response into the store, update `sendMessage()` to handle voice message sending by checking for recordedBlob and calling sendVoiceMessage first, add a template conditional to display audio player with controls for messages with type voice using the message.fileBlob URL, import the new `apiCreateVoiceChatMessage` function, add a watch on chatId to call `resetRecordingState()` when switching chats to prevent audio state from persisting across chat sessions, and remove a console.log statement that was logging scroll operations.

---

## File Contents

The labels below tell you what action to take:
- **Generated by command** — scaffold generator or CLI command created the file fully; command block only.
- **Generated by command, then manually edited** — CLI command created a stub; developer replaces the body with a command block, then file content block.
- **Edited manually** — file already existed; paste the file content block to replace its contents.
- **Created manually** — file does not exist and no CLI command creates it; paste the file content block only.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/functions/api/chat.js`

> **Edited manually** — add apiCreateVoiceChatMessage() function to send recorded voice blobs to the backend API.

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

export async function apiCreateVoiceChatMessage(chatId, voiceBlob) {
  const formData = new FormData();
  formData.append("voice", voiceBlob, "voice.webm");
  return await axios.post(
    APP_API_URL + `/chats/${chatId}/messages/create-voice`,
    formData,
  );
}
```

---

### `vuejs-app/src/stores/recentChats.js`

> **Edited manually** — add loadFile() action to fetch voice message file blobs from API, import axios, call loadFile() during message sync operations, and remove debug console.log statements.

```js
import axios from "axios";
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
      for (const chat of chats) {
        const index = this.chats.findIndex(
          (c) => Number(c.id) === Number(chat.id),
        );
        if (index !== -1) {
          this.chats[index] = chat;
        } else {
          this.chats.push(chat);
        }
        this.subscribeToChatMessageEvents(chat.id); // Subscribe to chat message events for each chat
      }
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
        for (const message of messages) {
          const index = chat.messages.findIndex(
            (m) => Number(m.id) === Number(message.id),
          );
          if (index !== -1) {
            chat.messages[index] = message;
            this.loadFile(chat.messages[index]); // reactive reference
          } else {
            chat.messages.push(message);
            this.loadFile(chat.messages[chat.messages.length - 1]); // reactive reference
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
          this.loadFile(chat.messages[index]); // reactive reference
        } else {
          chat.messages.push(message);
          this.loadFile(chat.messages[chat.messages.length - 1]); // reactive reference
        }
        this.sortChats();
      }
    },
    async loadFile(message) {
      if (message.type === "text" || message.fileBlob) {
        return;
      }
      const response = await axios.get(message.file_path, {
        responseType: "blob",
      });
      message.fileBlob = URL.createObjectURL(response.data);
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

      window.Echo.private(`MessageEvent.${chatId}`)
        .listen(".MessageCreated", async ({ message }) => {
          this.syncChatMessage(chatId, message);
        })
        .listen(".MessageUpdated", async ({ message }) => {
          this.syncChatMessage(chatId, message);
        })
        .listen(".MessageDeleted", async ({ message_id }) => {
          this.removeChatMessage(chatId, message_id);
        });

      // Mark as subscribed
      subscribedChatMessageIds.add(chatId);
    },

    unsubscribeFromChatMessageEvents(chatId) {
      if (subscribedChatMessageIds.has(chatId)) {
        window.Echo.leave(`MessageEvent.${chatId}`);
        subscribedChatMessageIds.delete(chatId);
      }
    },
  },
});
```

---

### `vuejs-app/src/components/pages/ChatBox.vue`

> **Edited manually** — add voice recording state management, recording UI with timer, audio playback template for voice messages, microphone permission handling, and send logic for voice messages.

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
                    <template v-else-if="message.type === 'voice'">
                      <audio controls :src="message.fileBlob" style="max-width: 250px;"></audio>
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
                <template v-if="isRecording">
                  <span class="form-control d-flex align-items-center text-danger">
                    <i class="fas fa-circle mr-2"></i> {{ formatRecordingTime(recordingSeconds) }}
                  </span>
                </template>
                <template v-else-if="recordedBlob">
                  <span class="form-control d-flex align-items-center">
                    <i class="fas fa-microphone mr-2 text-secondary"></i> {{ formatRecordingTime(recordingSeconds) }}
                  </span>
                </template>
                <template v-else>
                  <input v-model="messageContent" type="text" name="message" placeholder="Type Message ..."
                    class="form-control" maxlength="5000">
                </template>
                <span class="input-group-append">
                  <button v-if="recordedBlob && !isRecording" type="button" class="btn btn-secondary"
                    @click="resetRecordingState">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                  <button v-if="!recordedBlob" type="button" class="btn"
                    :class="isRecording ? 'btn-danger' : 'btn-secondary'" @click="toggleRecording">
                    <i class="fas fa-microphone"></i>
                  </button>
                  <button type="submit" class="btn btn-primary"
                    :disabled="!recordedBlob && !messageContent.trim()">Send</button>
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
import { apiGetChatMessages, apiCreateChatMessage, apiCreateVoiceChatMessage, apiUpdateChatMessage, apiDeleteChatMessage, apiMarkAllChatMessagesAsSeen } from "@/functions/api/chat";
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


// Voice recording state
const isRecording = ref(false);
const mediaRecorder = ref(null);
const audioChunks = ref([]);
const recordedBlob = ref(null);
const recordingSeconds = ref(0);
let recordingTimer = null;

function resetRecordingState() {
  recordedBlob.value = null;
  recordingSeconds.value = 0;
  clearInterval(recordingTimer);
  mediaRecorder.value?.stop();
  isRecording.value = false;
}
function formatRecordingTime(seconds) {
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return `${m}:${s.toString().padStart(2, '0')}`;
}

async function toggleRecording() {
  if (isRecording.value) {
    clearInterval(recordingTimer);
    mediaRecorder.value?.stop();
    isRecording.value = false;
  } else {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      mediaRecorder.value = new MediaRecorder(stream);
      audioChunks.value = [];
      recordingSeconds.value = 0;

      mediaRecorder.value.ondataavailable = (e) => {
        audioChunks.value.push(e.data);
      };

      mediaRecorder.value.onstop = () => {
        recordedBlob.value = new Blob(audioChunks.value, { type: 'audio/webm' });
        stream.getTracks().forEach(track => track.stop());
      };

      mediaRecorder.value.start();
      isRecording.value = true;
      recordingTimer = setInterval(() => {
        recordingSeconds.value++;
        if (recordingSeconds.value >= 60) { // Limit recording to 60 seconds
          clearInterval(recordingTimer);
          mediaRecorder.value?.stop();
          isRecording.value = false;
        }
      }, 1000);
    } catch (error) {
      return MessageModal({ icon: "error", title: "Error", text: "Microphone access denied." });
    }
  }
}

async function sendVoiceMessage(blob) {
  try {
    const response = await apiCreateVoiceChatMessage(props.chatId, blob);
    recentChatsStore.syncChatMessage(props.chatId, response.data.chat_message);
    scrollToBottom();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
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
  if (recordedBlob.value) {
    await sendVoiceMessage(recordedBlob.value);
    resetRecordingState();
    return;
  }

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
  resetRecordingState(); // Reset recording state when switching chatss

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

### Voice Message API Integration

The `apiCreateVoiceChatMessage()` function in [vuejs-app/src/functions/api/chat.js](vuejs-app/src/functions/api/chat.js) handles the HTTP communication between the frontend and backend. It accepts a chat ID and the recorded voice blob from the browser's MediaRecorder API, wraps the blob in a FormData object with the form field name `voice` and filename `voice.webm` to match the backend's validation rules, and sends a POST request to the `/chats/{chatId}/messages/create-voice` endpoint. The backend processes this request and returns a JSON response containing the created message object with all fields including `file_path`, `type`, and timestamps, which the frontend uses to update the store and UI.

### File Blob Management in State

The Pinia store in [vuejs-app/src/stores/recentChats.js](vuejs-app/src/stores/recentChats.js) introduces a new `loadFile()` action that handles fetching and caching voice message file blobs for browser playback. When a message is synced into the store via `syncChatMessage()` or `syncMultiChatMessages()`, the store automatically calls `loadFile()` for each message. The `loadFile()` action checks the message type and early-returns for text messages or messages that already have a cached `fileBlob`, then uses axios to fetch the file blob from the message's `file_path` URL (which is a generated route URL from the backend's `filePath()` accessor) with `responseType: 'blob'`, converts the response blob to a browser object URL using `URL.createObjectURL()`, and stores it in the message object as `fileBlob` for the Vue template to reference. This pattern allows the browser to stream file data on-demand and convert it to playable URLs without storing raw files in the state.

### Voice Recording and Playback UI

The ChatBox component in [vuejs-app/src/components/pages/ChatBox.vue](vuejs-app/src/components/pages/ChatBox.vue) implements the complete voice message workflow on the frontend. The component manages voice recording state with variables for `isRecording`, `mediaRecorder`, `audioChunks`, `recordedBlob`, and `recordingSeconds`, and uses the `toggleRecording()` function to request microphone access via `navigator.mediaDevices.getUserMedia({ audio: true })`, create a MediaRecorder instance to capture audio chunks into a Blob with webm MIME type, and handle error cases when the user denies microphone access. The template conditionally renders different UI states based on the recording state: when actively recording, it shows a red recording timer in M:SS format; when a blob is recorded but not actively recording, it shows the microphone icon with the recorded duration; otherwise it shows the normal text input field. The send form includes a microphone button that toggles recording state (red when recording, gray otherwise), a delete button to discard the recording and reset to text input, and a send button that prioritizes sending a recorded voice message if one exists, falling back to text message sending otherwise. When a voice message is sent, the `sendVoiceMessage()` function calls the API with the blob, receives the created message in the response, syncs it into the store (which automatically calls `loadFile()` to fetch the blob), and scrolls to the newest message. Voice messages in the chat display are rendered as HTML5 audio elements with browser-native controls, sourced from the message's `fileBlob` property populated by the store's `loadFile()` action. When switching between chats, the component calls `resetRecordingState()` to clear any in-progress recording, preventing audio state from persisting across different conversations.

---

## Common Commands

```bash
# No CLI commands were used in this session.
```
