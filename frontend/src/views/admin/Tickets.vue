<script setup>
import { storeToRefs } from "pinia";
import { onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";

import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import SelectField from "@/components/ui/SelectField.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import TextField from "@/components/ui/TextField.vue";
import PriorityBadge from "@/components/tickets/PriorityBadge.vue";
import StatusBadge from "@/components/tickets/StatusBadge.vue";
import api from "@/lib/api";
import { useTenantStore } from "@/stores/tenant";

const tenant = useTenantStore();
const { selectedId } = storeToRefs(tenant);

const tickets = ref([]);
const categories = ref([]);
const loading = ref(true);

const filters = ref({
  status: "",
  priority: "",
  ticket_category_id: "",
  search: "",
});

const statusOptions = [
  { value: "new", label: "Nuova" },
  { value: "taken_in_charge", label: "Presa in carico" },
  { value: "in_progress", label: "In lavorazione" },
  { value: "waiting_supplier", label: "Attesa fornitore" },
  { value: "resolved", label: "Risolta" },
  { value: "closed", label: "Chiusa" },
];

const priorityOptions = [
  { value: "low", label: "Bassa" },
  { value: "medium", label: "Media" },
  { value: "high", label: "Alta" },
  { value: "urgent", label: "Urgente" },
];

async function load() {
  if (!selectedId.value) return;
  loading.value = true;
  const params = { condominium_id: selectedId.value, per_page: 50 };
  Object.entries(filters.value).forEach(([k, v]) => {
    if (v) params[k] = v;
  });
  const { data } = await api.get("/api/tickets", { params });
  tickets.value = data.data;
  loading.value = false;
}

onMounted(async () => {
  if (!tenant.loaded) await tenant.fetchCondominiums();
  const { data } = await api.get("/api/ticket-categories");
  categories.value = data.data;
  load();
});

watch(selectedId, load);
watch(filters, load, { deep: true });
</script>

<template>
  <div class="space-y-4">
    <h1 class="text-xl font-bold text-slate-900">Segnalazioni</h1>

    <Card>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <SelectField
          v-model="filters.status"
          label="Stato"
          placeholder="Tutti"
          :options="statusOptions"
        />
        <SelectField
          v-model="filters.priority"
          label="Priorità"
          placeholder="Tutte"
          :options="priorityOptions"
        />
        <SelectField
          v-model="filters.ticket_category_id"
          label="Categoria"
          placeholder="Tutte"
          :options="categories.map((c) => ({ value: c.id, label: c.name }))"
        />
        <TextField
          v-model="filters.search"
          label="Cerca"
          placeholder="Titolo…"
        />
      </div>
    </Card>

    <Skeleton v-if="loading" :rows="5" />
    <EmptyState
      v-else-if="tickets.length === 0"
      icon="🔧"
      title="Nessuna segnalazione"
      description="Non ci sono segnalazioni con questi filtri."
    />

    <div
      v-else
      class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
    >
      <RouterLink
        v-for="ticket in tickets"
        :key="ticket.id"
        :to="`/admin/segnalazioni/${ticket.id}`"
        class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0 hover:bg-slate-50"
      >
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-slate-900">
            {{ ticket.title }}
          </p>
          <p class="truncate text-xs text-slate-500">
            {{ ticket.unit?.code }} · {{ ticket.category?.name }} ·
            {{ ticket.reporter?.name }}
          </p>
        </div>
        <PriorityBadge
          :priority="ticket.priority"
          :label="ticket.priority_label"
        />
        <StatusBadge :status="ticket.status" :label="ticket.status_label" />
      </RouterLink>
    </div>
  </div>
</template>
