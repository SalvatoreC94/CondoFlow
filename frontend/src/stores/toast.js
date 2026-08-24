import { defineStore } from "pinia";

let nextId = 1;

export const useToastStore = defineStore("toast", {
  state: () => ({
    toasts: [],
  }),

  actions: {
    push(message, type = "info", timeout = 4000) {
      const id = nextId++;
      this.toasts.push({ id, message, type });
      if (timeout) {
        setTimeout(() => this.dismiss(id), timeout);
      }
      return id;
    },

    success(message) {
      return this.push(message, "success");
    },

    error(message) {
      return this.push(message, "error", 6000);
    },

    dismiss(id) {
      this.toasts = this.toasts.filter((t) => t.id !== id);
    },
  },
});
