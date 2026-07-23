# ChatSystem — Frontend add chat member modal dialog with paginated user selection and admin authorization

## Table of Contents

- [What Changed in Session 7.4](#what-changed-in-session-74)
- [File Contents](#file-contents)
  - [vuejs-app/src/components/includes/modals/AddChatMemberModal.vue](#vuejs-appcomponentsincludesmodalsaddchatmembermodalvue)
  - [vuejs-app/src/components/pages/ChatDetail.vue](#vuejs-appcomponentspageschatdetailvue)
- [How Each File Works](#how-each-file-works)
- [Common Commands](#common-commands)

---

## What Changed in Session 7.4

Session 7.3 implemented the complete frontend group chat member management UI with role-based access control, paginated member listing with search capabilities, and inline member removal functionality. Session 7.4 extends the member management workflow by introducing a reusable modal component for adding new members to group chats, enabling admins to select users from a paginated list of available (non-member) users with search and pagination support, displaying user profile thumbnails alongside member information for visual identification, implementing member availability checking to disable the "Add" button for users already in the chat, and integrating the modal into ChatDetail with an "Add Members" button visible only to group admins. The new AddChatMemberModal component leverages the existing CustomTablePaginated table component for consistent UI, manages independent pagination state for the modal's user list, and accepts a callback function from the parent ChatDetail component to refresh the members list after successful additions. The ChatDetail component is updated to import and instantiate the modal, expose the modal reference for parent control, and add an "Add Members" button adjacent to the "Update Chat" button for admin-only member addition workflows.

| Area | Session 7.3 | Session 7.4 |
|---|---|---|
| Member list display | Paginated table below chat details | Same, unchanged |
| Member removal UI | Inline delete buttons | Same, unchanged |
| Adding members workflow | Not implemented | AddChatMemberModal modal dialog |
| User selection for member add | Not implemented | Paginated available users table |
| Member availability checking | Not implemented | Disable "Add" button for existing members |
| User profile display | Not implemented | Profile thumbnail in modal user list |
| Add member button | Not implemented | "Add Members" button (admin-only) |
| Modal state management | Not implemented | Independent pagination and search |
| Modal callback pattern | Not implemented | `onMemberAdded` callback to parent |

`vuejs-app/src/components/includes/modals/AddChatMemberModal.vue` was created manually as a reusable modal component for adding members to group chats, accepting props for chat ID, current members array, and callback function, providing paginated user listing with search via CustomTablePaginated, implementing member availability checking via `isMember()` function, calling the API `apiAddGroupChatMember()` on user selection, and exposing `showModal()` and `hideModal()` methods for parent control. `vuejs-app/src/components/pages/ChatDetail.vue` was edited manually to import AddChatMemberModal, add a template ref for modal access, instantiate the modal at the bottom of the template with proper prop bindings, add an "Add Members" button (visible to admins only) that calls the modal's `showModal()` method, and update the `isAdmin` state handling to properly track admin status reactively.

---

## File Contents

The labels below tell you what action to take:
- **Created manually** — file does not exist and no CLI command creates it; paste the block to replace its contents.
- **Edited manually** — file already exists from a previous session; paste the block to replace its contents.

Follow the sections in order from top to bottom.

---

### `vuejs-app/src/components/includes/modals/AddChatMemberModal.vue`

> **Created manually** — modal component for adding new members to group chats with paginated user selection, profile display, and member availability checking.

```vue
<template>
  <div class="modal fade" ref="AddChatMemberModal" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Add Chat Member</h4>
          <button type="button" class="close" @click="hideModal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">
          <CustomTablePaginated :title="'Users'" :data="users" :columns="columns" v-model:currentPage="currentPage"
            v-model:lastPage="lastPage" v-model:total="total" v-model:pageSize="pageSize" v-model:keyword="keyword"
            @search-change="handleSearchChange" />
        </div>
      </div>
    </div>
  </div>

</template>

<script setup>
import emptyImage from "@/assets/images/emptyImage.png";
import { ref, watch, h } from "vue";
import { LoadingModal, CloseModal, MessageModal } from "@/functions/swal";
import { apiAddGroupChatMember } from "@/functions/api/chat";
import $ from "jquery";
import { apiGetChatUsers } from "@/functions/api/chat";
import CustomTablePaginated from "@/components/includes/controls/CustomTablePaginated.vue";

const props = defineProps({
  chatId: {
    required: true,
  },
  members: {
    type: Array,
    required: true,
  },
  onMemberAdded: {
    type: Function,
    required: true,
  },
});

const AddChatMemberModal = ref();
const users = ref([]);
const columns = [
  {
    header: "Profile",
    accessorKey: "profile_thumbnail",
    cell: (info) => h("img", {
      src: info.getValue() || emptyImage,
      alt: "Profile Thumbnail",
      class: "img-circle elevation-2",
      style: "width: 40px; height: 40px; object-fit: cover;"
    })
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
    header: "Action",
    accessorKey: "id",
    cell: (cell) =>
      h("button", {
        disabled: isMember(cell.getValue()),  // Disable if already a member
        class: "btn btn-primary btn-sm",
        onClick: () => addMember(cell.getValue())
      }, "Add")
  }
];

// Pagination state
const currentPage = ref(1);
const pageSize = ref(25);
const total = ref(0);
const lastPage = ref(1);
const keyword = ref("");  // Track search keyword

async function generateUsers(searchKeyword = "", page = 1, per_page = 25) {
  try {
    LoadingModal();
    const response = await apiGetChatUsers({
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
    return CloseModal();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.message || error.response?.data?.message });
  }
}

// Watch for pagination changes to fetch data
watch(currentPage, async (newPage, oldPage) => {
  if (newPage !== oldPage) {
    await generateUsers(keyword.value, newPage, pageSize.value);
  }
});

watch(pageSize, async (newSize, oldSize) => {
  if (newSize !== oldSize) {
    await generateUsers(keyword.value, 1, newSize);
  }
});

async function handleSearchChange(searchKeyword) {
  await generateUsers(searchKeyword, 1, pageSize.value);
}

async function addMember(userId) {
  try {
    const response = await apiAddGroupChatMember(props.chatId, userId);
    props.onMemberAdded(); // Call the parent function to refresh the member list
    return MessageModal({ icon: "success", title: "Success", text: response.data.message });
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}
function isMember(userId) {
  return props.members.some(member => member.user.id === userId);
}
function hideModal() {
  $(AddChatMemberModal.value).modal('hide');
}
function showModal() {
  $(AddChatMemberModal.value).modal('show');
}
defineExpose({
  showModal,
  hideModal
});


</script>
```

---

### `vuejs-app/src/components/pages/ChatDetail.vue`

> **Edited manually** — import AddChatMemberModal, add modal reference and template instance, add "Add Members" button for admins, update isAdmin to reactive ref with proper initialization.

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
                <div class="form-group" v-if="isAdmin">
                  <button type="button" class="btn btn-outline-primary btn-block"
                    @click="AddChatMemberModalRef.showModal()">Add Members</button>
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
  <AddChatMemberModal ref="AddChatMemberModalRef" :chatId="props.chatId" :members="members"
    :onMemberAdded="generateChatMembers" />
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
import AddChatMemberModal from "@/components/includes/modals/AddChatMemberModal.vue";

const AddChatMemberModalRef = ref(null);
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

### AddChatMemberModal Component

The AddChatMemberModal is a reusable modal dialog that enables group chat admins to add new members from a paginated list of available users. It accepts three props from the parent ChatDetail component:

**Props**: `chatId` (required) identifies which chat to add members to; `members` (required array) contains the current members and is used to determine which users are already members; `onMemberAdded` (required function) is a callback invoked after successfully adding a member, typically `generateChatMembers` from the parent to refresh the display.

**User List Display**: The modal uses CustomTablePaginated to display five columns: profile thumbnail (displayed as a 40×40px circular image), user name, user email, and an "Add" button in the Actions column. The table fetches data from `apiGetChatUsers()` which returns users not already in any personal chat with the current user.

**Availability Checking**: The `isMember()` function checks if a user ID exists in the current members array. The "Add" button is disabled via the `:disabled` binding when `isMember()` returns true, preventing duplicate additions.

**Member Addition**: Clicking the "Add" button calls `addMember(userId)`, which:
1. Calls `apiAddGroupChatMember(props.chatId, userId)` to send the addition request to the backend
2. Invokes the parent's `onMemberAdded()` callback to refresh the chat members table
3. Displays a success message if the API call succeeds
4. Displays an error message if the API call fails (e.g., user already exists)

**Pagination and Search**: The modal maintains independent pagination state (`currentPage`, `pageSize`, `total`, `lastPage`) and search keyword. Watchers automatically re-fetch user data when these values change. The `handleSearchChange()` handler is bound to the table's search input and resets to page 1 when the user types.

**Modal Visibility Control**: The component exposes `showModal()` and `hideModal()` methods via `defineExpose()`, which are called by the parent via a template ref (`AddChatMemberModalRef`). Bootstrap's jQuery modal methods are used to toggle visibility.

### ChatDetail Updates

ChatDetail is updated to integrate the AddChatMemberModal component:

**Import and Ref**: The modal component is imported at the top of the script setup. A template ref `AddChatMemberModalRef` is created to hold the modal instance for method access.

**Modal Instance**: At the bottom of the template, the AddChatMemberModal component is instantiated with props bound to the current chat ID, members array, and `generateChatMembers` function as the callback.

**"Add Members" Button**: A new button is added to the group chat form section with `v-if="isAdmin"` to make it admin-only. The button calls `AddChatMemberModalRef.showModal()` to display the modal when clicked.

**isAdmin Ref**: The `isAdmin` value is changed from a computed property to a reactive ref (`ref(false)`) to allow direct assignment. It is initialized after fetching members in the route watcher: `isAdmin.value = members.value.some(member => member.user.id === userStore.id && member.role === 'admin') || false;`

**Callback Pattern**: When the modal successfully adds a member, it calls `props.onMemberAdded()`, which is bound to `generateChatMembers`. This automatically refreshes the members list below the form.

---

## Common Commands

```bash
# View chat details and add members
# Navigate to /chat/:chatId/details and click "Add Members" button as admin

# Test adding a member via API
curl -X POST "http://localhost/api/chats/group/1/members/add" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 2}'

# Test retrieving available users (non-members)
curl -X GET "http://localhost/api/chats/users?per_page=25&page=1&keyword=john" \
  -H "Authorization: Bearer $TOKEN"

# Run component tests
npm run test -- AddChatMemberModal.spec.js

# Run end-to-end tests for add member workflow
npm run test:e2e -- add-member-modal
```
