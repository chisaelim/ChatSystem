# ChatSystem — Frontend image message upload UI with file preview and send functionality

## Table of Contents

- [What Changed in Session 9.3-frontend](#what-changed-in-session-93-frontend)
- [File Contents](#file-contents)
  - [vuejs-app/src/functions/api/chat.js](#vuejs-appsrcfunctionsapichatjs)
  - [vuejs-app/src/components/pages/ChatBox.vue](#vuejs-appsrccomponentspageschatboxvue)
- [How Each File Works](#how-each-file-works)
  - [Image Message API Integration](#image-message-api-integration)
  - [Image Upload UI and Preview](#image-upload-ui-and-preview)
  - [Image Message Form State Management](#image-message-form-state-management)
- [Common Commands](#common-commands)

---

## What Changed in Session 9.3-frontend

Session 9.2 implemented the backend image message support with file validation and storage. Session 9.3-frontend completes the image message feature by adding the frontend UI and client-side logic to allow users to upload image files to chats. The session adds a new `apiCreateImageChatMessage()` export function to the chat API module that sends selected image files to the backend `/chats/{chatId}/messages/create-image` endpoint via FormData, extends the ChatBox component with image upload state management using a `selectedImageFile` ref to track the selected file from the file input, adds an `onImageSelected()` event handler to populate the selected file when a user selects an image from the file picker, adds a `sendImageMessage()` async function that calls the API with the selected file and syncs the response message into the store, adds an `openImagePreview()` function using SweetAlert to display a full-screen image preview modal when users click on image messages, modifies the message template to render image messages as clickable `<img>` elements with rounded corners and pointer cursor, adds conditional UI state display to show the selected image filename when an image is selected (similar to the voice message recording display), adds an image button that opens a hidden file input accepting only image MIME types (jpg, jpeg, png, gif, webp), adds message form logic to send image messages with priority over voice messages and text messages, and modifies the chat state reset logic to clear the selected image file when switching between chats. The session enables end-to-end image sharing with browser file selection, preview functionality, and real-time message synchronization.

| Area | Session 9.2 | Session 9.3-frontend |
|---|---|---|
| Image upload support | Backend endpoint only | Complete frontend UI added |
| Image selection | Not implemented | File input with image MIME type filtering |
| Image preview | Not implemented | SweetAlert modal on message click |
| Image send API | Not implemented | apiCreateImageChatMessage() function |
| Image display in chat | Not implemented | Rendered as clickable img elements |
| Form state | Voice and text only | Extended to include image |
| Button UI | Microphone and text only | Added image button |
| Message send priority | Voice > text | Image > voice > text |
| Chat state reset | Voice and text only | Extended to clear selected image |
| File metadata display | Not implemented | Filename shown in form control |

`vuejs-app/src/functions/api/chat.js` was edited manually to add a new `apiCreateImageChatMessage()` export function that accepts chatId and imageFile parameters, creates a FormData object, appends the image file as the `image` field, and sends a POST request to `/chats/{chatId}/messages/create-image` returning the API response containing the created message. `vuejs-app/src/components/pages/ChatBox.vue` was edited manually to import `apiCreateImageChatMessage` from the chat API module at the top of the script, add an image upload state variable `selectedImageFile` ref initialized to null, add an `onImageSelected()` event handler that captures the first file from the file input and stores it in the component state, add a `sendImageMessage()` async function that calls the API with the selected file, syncs the response message into the store, and scrolls to the new message, add an `openImagePreview()` function that opens a SweetAlert modal displaying the image URL at full size, modify the message template to add a new `v-else-if="message.type === 'image'"` branch that renders image messages as `<img>` elements with max-width 250px, border-radius 4px, and pointer cursor to indicate interactivity, add an `@click` handler to call `openImagePreview()` when users click on image messages, extend the form input section to add a new `v-else-if="selectedImageFile"` conditional branch that displays the image icon and filename in the form control when an image is selected (following the same pattern as voice recording display), add an image deletion button that clears `selectedImageFile` when clicked, add a new image button that opens the hidden file input via template ref `$refs.imageInput`, add a hidden file input with `ref="imageInput"` that accepts image MIME types and fires `@change="onImageSelected"`, update the microphone button condition to `v-if="!recordedBlob && !selectedImageFile"` so it only shows when neither voice nor image is selected, update the image button condition to `v-if="!recordedBlob && !isRecording"` so it only shows when not recording voice, update the send button disabled state to check `!selectedImageFile` in addition to existing text and voice conditions, update the `sendMessage()` function to prioritize image messages by checking `if (selectedImageFile.value)` first and sending the image before checking for voice or text messages, and extend the chat change watch handler to reset `selectedImageFile` to null when switching between chats along with clearing recording state.

---

## File Contents

The labels below tell you what action to take:
- **Edited manually** — file already existed; paste the file content block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/functions/api/chat.js`

> **Edited manually** — Add apiCreateImageChatMessage() function to send selected image files to the backend API.

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

export async function apiCreateImageChatMessage(chatId, imageFile) {
  const formData = new FormData();
  formData.append("image", imageFile);
  return await axios.post(
    APP_API_URL + `/chats/${chatId}/messages/create-image`,
    formData,
  );
}
```

---

### `vuejs-app/src/components/pages/ChatBox.vue`

> **Edited manually** — Add image upload state management, file picker UI, image preview modal, image rendering in chat, and image message send logic.

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
                    <template v-else-if="message.type === 'image'">
                      <img :src="message.fileBlob" style="max-width: 250px; border-radius: 4px; cursor: pointer;"
                        @click="openImagePreview(message.fileBlob)" alt="image message">
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
                <template v-else-if="selectedImageFile">
                  <span class="form-control d-flex align-items-center">
                    <i class="fas fa-image mr-2 text-secondary"></i> {{ selectedImageFile.name }}
                  </span>
                </template>
                <template v-else>
                  <input v-model="messageContent" type="text" name="message" placeholder="Type Message ..."
                    class="form-control" maxlength="5000">
                </template>
                <span class="input-group-append">
                  <button v-if="selectedImageFile" type="button" class="btn btn-secondary"
                    @click="selectedImageFile = null">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                  <button v-if="recordedBlob && !isRecording" type="button" class="btn btn-secondary"
                    @click="resetRecordingState">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                  <button v-if="!recordedBlob && !selectedImageFile" type="button" class="btn"
                    :class="isRecording ? 'btn-danger' : 'btn-secondary'" @click="toggleRecording">
                    <i class="fas fa-microphone"></i>
                  </button>
                  <button v-if="!recordedBlob && !isRecording" type="button" class="btn btn-secondary"
                    @click="$refs.imageInput.click()">
                    <i class="fas fa-image"></i>
                  </button>
                  <input ref="imageInput" type="file" accept="image/jpg,image/jpeg,image/png,image/gif,image/webp"
                    style="display: none;" @change="onImageSelected">
                  <button type="submit" class="btn btn-primary"
                    :disabled="!recordedBlob && !messageContent.trim() && !selectedImageFile">Send</button>
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
import { apiGetChatMessages, apiCreateChatMessage, apiCreateVoiceChatMessage, apiCreateImageChatMessage, apiUpdateChatMessage, apiDeleteChatMessage, apiMarkAllChatMessagesAsSeen } from "@/functions/api/chat";
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


// Image upload state
const selectedImageFile = ref(null);

function onImageSelected(event) {
  const file = event.target.files[0];
  if (file) {
    selectedImageFile.value = file;
  }
  event.target.value = '';
}

async function sendImageMessage(file) {
  try {
    const response = await apiCreateImageChatMessage(props.chatId, file);
    recentChatsStore.syncChatMessage(props.chatId, response.data.chat_message);
    scrollToBottom();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}

function openImagePreview(src) {
  Swal.fire({ imageUrl: src, imageAlt: 'Image message', showConfirmButton: false, showCloseButton: true });
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
  if (selectedImageFile.value) {
    await sendImageMessage(selectedImageFile.value);
    selectedImageFile.value = null;
    return;
  }

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
  resetRecordingState(); // Reset recording state when switching chats
  selectedImageFile.value = null; // Reset selected image when switching chats

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

### Image Message API Integration

The [vuejs-app/src/functions/api/chat.js](vuejs-app/src/functions/api/chat.js) file exports a new `apiCreateImageChatMessage()` function that handles HTTP communication for sending selected image files to the backend. It accepts a chat ID and the File object selected from the browser's file input, wraps the file in a FormData object with the form field name `image` to match the backend's `CreateImageChatMessageRequest` validation, and sends a POST request to the `/chats/{chatId}/messages/create-image` endpoint. The backend processes the request and returns a JSON response containing the created message object with file metadata, which the frontend uses to update the store and UI. This function follows the same pattern as `apiCreateVoiceChatMessage()`, ensuring consistency in file-based message handling across the API layer.

### Image Upload UI and Preview

The [vuejs-app/src/components/pages/ChatBox.vue](vuejs-app/src/components/pages/ChatBox.vue) template implements a user-friendly image upload and preview experience. The message display section conditionally renders image messages with a `v-else-if="message.type === 'image'"` template that displays a `<img>` element sourced from the message's `fileBlob` property (populated by the backend's model accessor). Each image has max-width 250px to maintain chat layout proportions, border-radius 4px for visual polish, cursor pointer to indicate interactivity, and an `@click="openImagePreview(message.fileBlob)"` handler that opens a full-screen SweetAlert modal when users click on any image message. The `openImagePreview()` function uses `Swal.fire()` to display the image URL at full resolution with a close button, providing a lightweight preview experience without browser navigation. The form input section adds a new conditional branch `v-else-if="selectedImageFile"` that displays the image icon and filename in the form control when an image is selected, using the same visual pattern as the voice recording display for consistency. A new hidden file input with `ref="imageInput"` accepts only image MIME types (jpg, jpeg, png, gif, webp) to prevent invalid file selection at the browser level. An image button with `@click="$refs.imageInput.click()"` opens the file picker programmatically, and an associated delete button clears the selection when clicked.

### Image Message Form State Management

The [vuejs-app/src/components/pages/ChatBox.vue](vuejs-app/src/components/pages/ChatBox.vue) script manages image upload state through a `selectedImageFile` ref initialized to null. The `onImageSelected()` event handler captures the first file from the file input when users select an image, stores it in component state, and clears the input value to allow re-selection of the same file. The `sendImageMessage()` async function calls the API with the selected file, receives the created message in the response, syncs it into the store (which automatically calls `loadFile()` to fetch the image blob via the file accessor URL), and scrolls to the new message. The form's button visibility logic ensures that the microphone button only shows when no image is selected and no voice is recording (`v-if="!recordedBlob && !selectedImageFile"`), the image button only shows when not recording voice (`v-if="!recordedBlob && !isRecording"`), and the send button is disabled when no image, voice, or text is present. The `sendMessage()` function prioritizes image messages first by checking `if (selectedImageFile.value)` before checking for voice or text messages, ensuring users can send images with a single click regardless of prior state. When switching between chats, the watch handler resets `selectedImageFile` to null along with clearing recording state, preventing UI state from persisting across different conversations and preventing accidentally sending a previously selected image to a different chat.

---

## Common Commands

```bash
# No CLI commands were used in this session.
```
