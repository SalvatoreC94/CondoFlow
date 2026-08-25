<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";

import Card from "@/components/ui/Card.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import StatusBadge from "@/components/tickets/StatusBadge.vue";
import api from "@/lib/api";
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();

const loading = ref(true);
const recentTickets = ref([]);
const recentAnnouncements = ref([]);

const firstName = computed(() => auth.user?.name?.split(" ")[0] ?? "");
const condominium = computed(() => auth.user?.units?.[0]?.condominium);

const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 12) return "Buongiorno";
  if (hour < 18) return "Buon pomeriggio";
  return "Buonasera";
});

onMounted(async () => {
  try {
    const condominiumId = condominium.value?.id;
    const [tickets, announcements] = await Promise.all([
      api.get("/api/tickets", { params: { per_page: 3 } }),
      condominiumId
        ? api.get("/api/announcements", {
            params: { condominium_id: condominiumId, per_page: 3 },
          })
        : Promise.resolve({ data: { data: [] } }),
    ]);
    recentTickets.value = tickets.data.data;
    recentAnnouncements.value = announcements.data.data;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="space-y-6 px-4 pt-6">
    <div>
      <p class="text-sm text-slate-500">{{ condominium?.name }}</p>
      <h1 class="text-2xl font-bold text-slate-900">
        {{ greeting }}, {{ firstName }}
      </h1>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <RouterLink
        to="/app/segnalazioni/nuova"
        class="flex flex-col items-start gap-2 rounded-2xl bg-primary-600 p-4 text-white shadow-sm transition active:scale-[0.98]"
      >
        <span class="text-2xl">🔧</span>
        <span class="text-sm font-semibold leading-tight"
          >Segnala un problema</span
        >
      </RouterLink>
      <RouterLink
        to="/app/segnalazioni"
        class="flex flex-col items-start gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.98]"
      >
        <span class="text-2xl">📋</span>
        <span class="text-sm font-semibold leading-tight text-slate-900"
          >Le mie segnalazioni</span
        >
      </RouterLink>
      <RouterLink
        to="/app/comunicazioni"
        class="flex flex-col items-start gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.98]"
      >
        <span class="text-2xl">📣</span>
        <span class="text-sm font-semibold leading-tight text-slate-900"
          >Comunicazioni</span
        >
      </RouterLink>
      <RouterLink
        to="/app/documenti"
        class="flex flex-col items-start gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.98]"
      >
        <span class="text-2xl">📄</span>
        <span class="text-sm font-semibold leading-tight text-slate-900"
          >Documenti</span
        >
      </RouterLink>
      <RouterLink
        to="/app/assemblee"
        class="flex flex-col items-start gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.98]"
      >
        <span class="text-2xl">🗳️</span>
        <span class="text-sm font-semibold leading-tight text-slate-900"
          >Assemblee</span
        >
      </RouterLink>
    </div>

    <section>
      <div class="mb-2 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Ultime attività</h2>
        <RouterLink
          to="/app/segnalazioni"
          class="text-xs font-medium text-primary-600"
          >Vedi tutte</RouterLink
        >
      </div>

      <Skeleton v-if="loading" :rows="2" />
      <div
        v-else-if="
          recentTickets.length === 0 && recentAnnouncements.length === 0
        "
        class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-400"
      >
        Nessuna attività recente.
      </div>
      <div v-else class="space-y-2">
        <RouterLink
          v-for="ticket in recentTickets"
          :key="`t-${ticket.id}`"
          :to="`/app/segnalazioni/${ticket.id}`"
        >
          <Card class="flex items-center justify-between">
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-slate-900">
                {{ ticket.title }}
              </p>
              <p class="text-xs text-slate-500">{{ ticket.category?.name }}</p>
            </div>
            <StatusBadge :status="ticket.status" :label="ticket.status_label" />
          </Card>
        </RouterLink>
        <RouterLink
          v-for="announcement in recentAnnouncements"
          :key="`a-${announcement.id}`"
          :to="`/app/comunicazioni/${announcement.id}`"
        >
          <Card>
            <p class="truncate text-sm font-medium text-slate-900">
              📣 {{ announcement.title }}
            </p>
          </Card>
        </RouterLink>
      </div>
    </section>
  </div>
</template>
