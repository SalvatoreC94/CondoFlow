import { defineStore } from "pinia";
import api from "@/lib/api";

export const useNotificationsStore = defineStore("notifications", {
  state: () => ({
    items: [],
    unreadCount: 0,
  }),

  actions: {
    async fetchUnreadCount() {
      const { data } = await api.get("/api/notifications", {
        params: { per_page: 50 },
      });
      this.items = data.data;
      this.unreadCount = data.data.filter((n) => !n.read_at).length;
      return this.unreadCount;
    },

    async markRead(id) {
      await api.patch(`/api/notifications/${id}/read`);
      const item = this.items.find((n) => n.id === id);
      if (item) item.read_at = new Date().toISOString();
      this.unreadCount = Math.max(0, this.unreadCount - 1);
    },

    async markAllRead() {
      await api.post("/api/notifications/read-all");
      this.items.forEach(
        (n) => (n.read_at = n.read_at ?? new Date().toISOString()),
      );
      this.unreadCount = 0;
    },
  },
});
