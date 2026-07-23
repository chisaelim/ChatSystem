# ChatSystem — Frontend group chat member management UI with role-based access control and paginated member listing

## Table of Contents

- [What Changed in Session 7.3](#what-changed-in-session-73)
- [File Contents](#file-contents)
  - [vuejs-app/src/functions/api/chat.js](#vuejs-appfunctionsapichatjs)
  - [vuejs-app/src/components/pages/ChatDetail.vue](#vuejs-appcomponentspageschatdetailvue)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 7.3

Session 7.2 implemented backend group chat member management API endpoints with three controller methods and corresponding request/resource classes for retrieving, adding, and removing chat members. Session 7.3 completes the feature by integrating frontend group chat member management UI, adding role-based access control to the ChatDetail component, conditionally rendering edit and delete operations for admin-only access, embedding a paginated member list table within the chat details view with search and filtering capabilities, and adding three new API client functions to wrap the backend member management endpoints. The ChatDetail component is enhanced with admin authorization checks that conditionally disable form fields for non-admin group members, hide destructive action buttons from non-admins, and display a comprehensive members table showing member name, email, role, join date, and admin-only member removal actions. New state management for member pagination includes current page, page size, total count, and search keyword tracking. Two new watchers monitor pagination state changes and automatically fetch updated member data. The member table uses the existing `CustomTablePaginated` component for consistent UI and supports inline member removal via confirmation dialog.

| Area | Session 7.2 | Session 7.3 |
|---|---|---|
| Backend member API endpoints | Fully implemented | Used via frontend |
| Frontend API client functions | `apiLeaveGroupChat()` only | Added `apiGetGroupChatMembers()`, `apiAddGroupChatMember()`, `apiRemoveGroupChatMember()` |
| Chat details form access control | No role checks | `isAdmin` computed property controls form visibility and editability |
| Member list display | Not implemented | Paginated member table with search, role, and join date |
| Member removal UI | Not implemented | Inline action buttons with confirmation dialog (admin-only) |
| Chat edit permissions | All members can edit | Admin-only edit controls |
| Avatar upload UI | All members can upload | Conditional visibility for admins only |
| Member pagination state | Not implemented | Managed via refs with watchers for automatic updates |

`vuejs-app/src/functions/api/chat.js` was edited manually to add three new API client functions (`apiGetGroupChatMembers`, `apiAddGroupChatMember`, `apiRemoveGroupChatMember`) that wrap the corresponding backend endpoints. `vuejs-app/src/components/pages/ChatDetail.vue` was edited manually to add role-based access control with an `isAdmin` computed property, conditionally disable form fields and hide buttons based on admin status, add a `CustomTablePaginated` component for displaying members with search and pagination, manage member list state with pagination refs, implement watchers for automatic member data updates on page/size changes, add a columns configuration object for the members table with name, email, role, join date, and actions columns, and implement `generateChatMembers()`, `handleMemberSearchChange()`, and `removeChatMember()` functions for member management operations.

---

## File Contents

The labels below tell you what action to take:
- **Edited manually** — file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/functions/api/chat.js`

> **Edited manually** — add three API client functions for retrieving, adding, and removing group chat members that wrap backend endpoints.

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
```

---

### `vuejs-app/src/components/pages/ChatDetail.vue`

> **Edited manually** — add role-based access control, conditionally disable editing for non-admins, integrate paginated member list table with search and removal functionality.

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
                <div class="mt-1" v-if="chatType === 'group' && isAdmin">
                  <label :for="'file-input'">
                    <a type="button" class="m-1 btn btn-primary btn-sm"><i class="fas fa-upload"></i></a>
                  </label>
                  <a type="button" @click="onDeleteImage" class="m-1 btn btn-danger btn-sm"><i
                      class="fas fa-trash"></i></a>
                </div>
              </div>
              <div class="form-group">
                <label>Name</label>
                <input :disabled="chatType === 'personal' || (chatType === 'group' && !isAdmin)" type="text"
                  class="form-control" v-model="chat.name" :class="{ 'is-invalid': !!chatError.name }" />
                <div class="invalid-feedback">{{ chatError.name }}</div>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea :disabled="chatType === 'personal' || (chatType === 'group' && !isAdmin)" class="form-control"
                  v-model="chat.description" :class="{ 'is-invalid': !!chatError.description }"></textarea>
                <div class="invalid-feedback">{{ chatError.description }}</div>
              </div>
              <template v-if="chatType === 'group'">
                <div class="form-group" v-if="isAdmin">
                  <button type="submit" class="btn btn-primary btn-block">Update Chat</button>
                </div>
                <div class="form-group">
                  <button type="button" @click="leaveGroupChat" class="btn btn-warning btn-block">Leave Chat</button>
                </div>
                <div class="form-group" v-if="isAdmin">
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

        <CustomTablePaginated v-if="chatType === 'group'" :title="'Chat Members'" :data="members" :columns="columns"
          v-model:currentPage="memberCurrentPage" v-model:lastPage="memberLastPage" v-model:total="memberTotal"
          v-model:pageSize="memberPageSize" v-model:keyword="memberKeyword" @search-change="handleMemberSearchChange" />
      </div>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref, watch, h, computed } from "vue";
import emptyImage from "@/assets/images/emptyImage.png";
import { MessageModal, LoadingModal, CloseModal } from "@/functions/swal";
import { apiUpdateGroupChat, apiReadChat, apiDeleteChat, apiLeaveGroupChat, apiGetGroupChatMembers, apiRemoveGroupChatMember } from "@/functions/api/chat";
import { useRouter } from "vue-router";
import { useRecentChatsStore } from "@/stores/recentChats";
import Swal from "sweetalert2";
import CustomTablePaginated from "@/components/includes/controls/CustomTablePaginated.vue";
import { utcToLocal } from "@/functions/datetime";
import { useUserStore } from "@/stores/user";
const userStore = useUserStore();
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
    if (chatType.value === 'group') {
      await generateChatMembers(memberKeyword.value, memberCurrentPage.value, memberPageSize.value);
      isAdmin.value = members.value.some(member => member.user.id === userStore.id && member.role === 'admin') || false;
    }
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


const members = ref([]);
const memberCurrentPage = ref(1);
const memberPageSize = ref(25);
const memberTotal = ref(0);
const memberLastPage = ref(1);
const memberKeyword = ref("");  // Track search keyword

const isAdmin = ref(false); // Track if the current user is an admin of the group chat
const columns = [
  {
    header: "Name",
    accessorKey: "user.name",
  },
  {
    header: "Email",
    accessorKey: "user.email",
  },
  {
    header: "Role",
    accessorKey: "role",
  },
  {
    header: "Joined At",
    accessorKey: "joined_at",
    cell: (cell) => {
      return utcToLocal(cell.getValue()).format("YYYY-MM-DD HH:mm:ss");
    },
  },
  {
    header: "Actions",
    accessorKey: "id",
    cell: ({ row }) => [
      // remove btn
      h(
        "button",
        {
          disabled: !isAdmin.value || row.original.user.id === userStore.id, // Disable if not admin or if the member is the current user
          onClick: () => removeChatMember(row.original.id),
          class: "btn btn-sm btn-outline-danger mx-1",
        },
        h("i", { class: "fa fa-trash" })
      ),
    ],
  }
];


async function generateChatMembers(searchKeyword = "", page = 1, per_page = 25) {
  try {
    LoadingModal();
    const response = await apiGetGroupChatMembers(props.chatId, {
      keyword: searchKeyword,
      page: page,
      per_page: per_page,
    });

    // Update all pagination state from API response
    members.value = response.data.members;
    memberCurrentPage.value = response.data.meta.current_page;
    memberPageSize.value = response.data.meta.per_page;
    memberTotal.value = response.data.meta.total;
    memberLastPage.value = response.data.meta.last_page;
    return CloseModal();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.message || error.response?.data?.message });
  }
}

// Watch for pagination changes to fetch data
watch(memberCurrentPage, async (newPage, oldPage) => {
  if (newPage !== oldPage) {
    await generateChatMembers(memberKeyword.value, newPage, memberPageSize.value);
  }
});

watch(memberPageSize, async (newSize, oldSize) => {
  if (newSize !== oldSize) {
    await generateChatMembers(memberKeyword.value, 1, newSize);
  }
});

async function handleMemberSearchChange(searchKeyword) {
  await generateChatMembers(searchKeyword, 1, memberPageSize.value);
}

async function removeChatMember(memberId) {
  Swal.fire({
    icon: "question",
    title: "Remove Chat Member",
    text: "Are you sure you want to remove this member from the chat?",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, remove!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal('Removing chat member...');
        const response = await apiRemoveGroupChatMember(props.chatId, memberId);
        const { data } = response;
        members.value = members.value.filter(member => member.id !== memberId);
        return MessageModal({ icon: "success", title: "Success", text: data.message });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.message || error.response.data.message });
      }
    }
  });
}
</script>
```

---

## How Each File Works

### API Client Functions

Three new functions extend the chat API client to support group member management operations on the frontend:

**`apiGetGroupChatMembers(chatId, params)`** retrieves a paginated list of group chat members. It accepts optional parameters for keyword search filtering and pagination (page, per_page). The response includes an array of member resources (ID, role, name, email, joined_at timestamp) and pagination metadata (current_page, last_page, per_page, total count). This function is called when the ChatDetail component loads for a group chat and whenever pagination or search parameters change.

**`apiAddGroupChatMember(chatId, userId)`** sends a POST request to add a new member to a group chat. It accepts the chat ID and user ID in the request body. The backend validates that the requesting user is a group admin before allowing the addition. This function is not currently used in ChatDetail but is available for future "add member" UI implementations.

**`apiRemoveGroupChatMember(chatId, memberId)`** sends a DELETE request to remove a member from a group chat. It accepts the chat ID and member ID as URL parameters. The backend validates admin-only authorization and prevents removing the requesting user (directing them to use the leave chat endpoint instead). This function is called from the `removeChatMember()` method when a user clicks the delete button in the members table.

### ChatDetail Component

The ChatDetail component provides the UI for viewing and managing group chat metadata and members. Key enhancements in Session 7.3:

**Role-Based Access Control**: The `isAdmin` computed property checks if the current user is listed as an admin in the members array. This value gates access to edit operations (name, description, avatar upload/delete) and destructive actions (update, delete). Form fields are disabled for non-admins via the `:disabled` binding. The "Update Chat" and "Delete Chat" buttons are hidden entirely from non-admins using `v-if="isAdmin"`. The avatar upload/delete buttons are similarly hidden from non-admin members.

**Member List Display**: The `CustomTablePaginated` component is rendered below the chat details form for group chats only (`v-if="chatType === 'group'"`). The table displays five columns: member name and email (fetched from related user data), member role ('admin' or 'member'), join date formatted via `utcToLocal()`, and an Actions column with a delete button.

**Member State Management**: Five refs track the member list state: `members` (array of member objects), `memberCurrentPage` (current pagination page), `memberPageSize` (results per page, default 25), `memberTotal` (total count of members), `memberLastPage` (last available page), and `memberKeyword` (current search keyword). These are bound to the table component using `v-model` for two-way updates.

**Member Data Fetching**: The `generateChatMembers()` function calls `apiGetGroupChatMembers()` with the current chat ID and pagination/search parameters, then updates all five state refs from the API response. Two watchers automatically call this function when the page or page size changes, triggering re-fetches. The `handleMemberSearchChange()` handler is called when the user types in the search box, resetting to page 1 and re-fetching.

**Member Removal**: The Actions column renders a delete button for each member using Vue's `h()` render function. The button is disabled if the current user is not an admin or if the member is the current user themselves. Clicking the button calls `removeChatMember()`, which shows a Swal confirmation dialog. If confirmed, it calls `apiRemoveGroupChatMember()` and filters the member from the local array to provide instant UI feedback.

**Initial Load**: When the component mounts or the chat ID changes, the watcher calls `readChat()` to fetch chat details, then calls `generateChatMembers()` if the chat type is 'group' to populate the members table.

---

## Common Commands

```bash
# View group chat details and members
# Navigate to /chat/:chatId/details in the browser

# Test retrieving members via API
curl -X GET "http://localhost/api/chats/group/1/members?per_page=25&page=1&keyword=john" \
  -H "Authorization: Bearer $TOKEN"

# Test removing a member via API
curl -X DELETE "http://localhost/api/chats/group/1/members/remove/3" \
  -H "Authorization: Bearer $TOKEN"

# Run component tests
npm run test -- ChatDetail.spec.js

# Run end-to-end tests for chat member management
npm run test:e2e -- chat-member-management
```
