<script setup>
import { onMounted, ref } from "vue";
import { RouterLink } from "vue-router";

import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import PriorityBadge from "@/components/tickets/PriorityBadge.vue";
import StatusBadge from "@/components/tickets/StatusBadge.vue";
import api from "@/lib/api";

const tickets = ref([]);
const loading = ref(true);
const filter = ref("open");

async function load() {
  loading.value = true;
  try {
    const params = { per_page: 50 };
    const { data } = await api.get("/api/tickets", { params });
    tickets.value = data.data;
  } finally {
    loading.value = false;
  }
}

onMounted(load);

const openStatuses = [
  "new",
  "taken_in_charge",
  "in_progress",
  "waiting_supplier",
];
</script>

<template>
  <div class="space-y-4 px-4 pt-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold text-slate-900">Le mie segnalazioni</h1>
      <RouterLink
        to="/app/segnalazioni/nuova"
        class="text-sm font-semibold text-primary-600"
        >+ Nuova</RouterLink
      >
    </div>

    <div class="flex gap-2 overflow-x-auto scrollbar-none">
      <button
        type="button"
        class="flex-shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium"
        :class="
          filter === 'open'
            ? 'bg-primary-600 text-white'
            : 'bg-white text-slate-600 border border-slate-200'
        "
        @click="filter = 'open'"
      >
        Aperte
      </button>
      <button
        type="button"
        class="flex-shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium"
        :class="
          filter === 'all'
            ? 'bg-primary-600 text-white'
            : 'bg-white text-slate-600 border border-slate-200'
        "
        @click="filter = 'all'"
      >
        Tutte
      </button>
    </div>

    <Skeleton v-if="loading" :rows="4" />

    <EmptyState
      v-else-if="
        tickets.filter(
          (t) => filter === 'all' || openStatuses.includes(t.status),
        ).length === 0
      "
      icon="🔧"
      title="Nessuna segnalazione"
      description="Non hai ancora inviato segnalazioni per il tuo condominio."
    >
      <template #action>
        <RouterLink
          to="/app/segnalazioni/nuova"
          class="text-sm font-semibold text-primary-600"
          >Crea la prima segnalazione</RouterLink
        >
      </template>
    </EmptyState>

    <div v-else class="space-y-2">
      <RouterLink
        v-for="ticket in tickets.filter(
          (t) => filter === 'all' || openStatuses.includes(t.status),
        )"
        :key="ticket.id"
        :to="`/app/segnalazioni/${ticket.id}`"
      >
        <Card>
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-900">
                {{ ticket.title }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ ticket.category?.name }} ·
                {{ new Date(ticket.created_at).toLocaleDateString("it-IT") }}
              </p>
            </div>
            <PriorityBadge
              :priority="ticket.priority"
              :label="ticket.priority_label"
            />
          </div>
          <div class="mt-3">
            <StatusBadge :status="ticket.status" :label="ticket.status_label" />
          </div>
        </Card>
      </RouterLink>
    </div>
  </div>
</template>
