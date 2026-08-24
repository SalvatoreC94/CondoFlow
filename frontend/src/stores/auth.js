import { defineStore } from "pinia";
import api, { ensureCsrfCookie } from "@/lib/api";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    loaded: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    role: (state) => state.user?.role ?? null,
    isAdministrator: (state) => state.user?.role === "administrator",
    isCaretaker: (state) => state.user?.role === "caretaker",
    isCondomino: (state) => state.user?.role === "condomino",
    homeRoute: (state) => {
      switch (state.user?.role) {
        case "administrator":
          return "/admin";
        case "caretaker":
          return "/custode";
        case "condomino":
          return "/app";
        default:
          return "/login";
      }
    },
  },

  actions: {
    async fetchUser() {
      try {
        const { data } = await api.get("/api/me");
        this.user = data.data;
      } catch {
        this.user = null;
      } finally {
        this.loaded = true;
      }
      return this.user;
    },

    async login(credentials) {
      await ensureCsrfCookie();
      await api.post("/api/login", credentials);
      await this.fetchUser();
    },

    async logout() {
      await api.post("/api/logout").catch(() => {});
      this.user = null;
    },

    async updateProfile(payload) {
      const { data } = await api.put("/api/me", payload);
      this.user = data.data;
      return this.user;
    },
  },
});
