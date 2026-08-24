import { defineStore } from "pinia";
import api from "@/lib/api";

/**
 * Holds the list of condominiums the current staff user (administrator or
 * caretaker) can manage, plus which one is currently selected for the
 * admin/caretaker views. A condomino never needs this — they only ever see
 * their own condominium.
 */
export const useTenantStore = defineStore("tenant", {
  state: () => ({
    condominiums: [],
    selectedId:
      Number(localStorage.getItem("condoflow:selectedCondominium")) || null,
    loaded: false,
  }),

  getters: {
    selected: (state) =>
      state.condominiums.find((c) => c.id === state.selectedId) ?? null,
  },

  actions: {
    async fetchCondominiums() {
      const { data } = await api.get("/api/condominiums");
      this.condominiums = data.data;
      if (
        !this.selectedId ||
        !this.condominiums.some((c) => c.id === this.selectedId)
      ) {
        this.selectedId = this.condominiums[0]?.id ?? null;
      }
      this.loaded = true;
      return this.condominiums;
    },

    select(id) {
      this.selectedId = id;
      localStorage.setItem("condoflow:selectedCondominium", String(id));
    },
  },
});
