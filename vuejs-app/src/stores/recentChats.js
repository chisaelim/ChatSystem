import { defineStore } from "pinia";
import { useUserStore } from "@/stores/user";

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
          this.syncChat(chat);
        })
        .listen(".ChatUpdated", async ({ chat }) => {
          this.syncChat(chat);
        })
        .listen(".ChatDeleted", ({ chat_id }) => {
          this.removeChat(chat_id);
        });
    },
    subscribeToChatMessageEvents() {
      const userStore = useUserStore();
      window.Echo.private(`ChatMessageEvent.${userStore.id}`)
        .listen(".ChatMessageCreated", async ({ chat, message }) => {
          if (!this.getChatById(chat.id)) {
            this.syncChat(chat);
          }
          this.syncChatMessage(chat.id, message);
        })
        .listen(".ChatMessageUpdated", async ({ chat, message }) => {
          if (!this.getChatById(chat.id)) {
            this.syncChat(chat);
          }
          this.syncChatMessage(chat.id, message);
        })
        .listen(".ChatMessageDeleted", async ({ chat, message_id }) => {
          if (!this.getChatById(chat.id)) {
            this.syncChat(chat);
          }
          this.removeChatMessage(chat.id, message_id);
        });
    },
  },
});
