<script setup>
import { storeToRefs } from "pinia";
import { computed, onMounted, ref, watch } from "vue";

import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import CaretakerTicketRow from "@/components/tickets/CaretakerTicketRow.vue";
import api from "@/lib/api";
import { useTenantStore } from "@/stores/tenant";
import { useToastStore } from "@/stores/toast";

const tenant = useTenantStore();
const { selectedId } = storeToRefs(tenant);
const toast = useToastStore();

const tickets = ref([]);
const loading = ref(true);
const acting = ref(null);

async function load() {
  if (!selectedId.value) return;
  loading.value = true;
  const { data } = await api.get("/api/tickets", {
    params: { condominium_id: selectedId.value, per_page: 100 },
  });
  tickets.value = data.data;
  loading.value = false;
}

onMounted(async () => {
  if (!tenant.loaded) await tenant.fetchCondominiums();
  load();
});
watch(selectedId, load);

const newTickets = computed(() =>
  tickets.value.filter((t) => t.status === "new"),
);
const inProgress = computed(() =>
  tickets.value.filter((t) =>
    ["taken_in_charge", "in_progress"].includes(t.status),
  ),
);
const urgent = computed(() =>
  tickets.value.filter(
    (t) =>
      t.priority === "urgent" && !["resolved", "closed"].includes(t.status),
  ),
);

async function quickTransition(ticket, status) {
  acting.value = ticket.id;
  try {
    await api.patch(`/api/tickets/${ticket.id}/status`, { status });
    await load();
    toast.success("Stato aggiornato.");
  } catch {
    toast.error("Impossibile aggiornare lo stato.");
  } finally {
    acting.value = null;
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-slate-900">Ciao 👋</h1>
      <p class="text-sm text-slate-500">{{ tenant.selected?.name }}</p>
    </div>

    <div class="grid grid-cols-3 gap-2">
      <Card class="text-center">
        <p class="text-2xl font-bold text-slate-900">{{ newTickets.length }}</p>
        <p class="text-xs text-slate-500">Nuove</p>
      </Card>
      <Card class="text-center">
        <p class="text-2xl font-bold text-primary-700">
          {{ inProgress.length }}
        </p>
        <p class="text-xs text-slate-500">In corso</p>
      </Card>
      <Card class="text-center">
        <p class="text-2xl font-bold text-red-600">{{ urgent.length }}</p>
        <p class="text-xs text-slate-500">Urgenti</p>
      </Card>
    </div>

    <Skeleton v-if="loading" :rows="4" />

    <template v-else>
      <section v-if="urgent.length">
        <h2 class="mb-2 text-sm font-semibold text-red-700">🚨 Urgenti</h2>
        <div class="space-y-2">
          <CaretakerTicketRow
            v-for="t in urgent"
            :key="t.id"
            :ticket="t"
            :acting="acting"
            @act="quickTransition"
          />
        </div>
      </section>

      <section>
        <h2 class="mb-2 text-sm font-semibold text-slate-900">
          Nuove segnalazioni
        </h2>
        <EmptyState
          v-if="!newTickets.length"
          icon="✅"
          title="Nessuna nuova segnalazione"
        />
        <div v-else class="space-y-2">
          <CaretakerTicketRow
            v-for="t in newTickets"
            :key="t.id"
            :ticket="t"
            :acting="acting"
            @act="quickTransition"
          />
        </div>
      </section>

      <section>
        <h2 class="mb-2 text-sm font-semibold text-slate-900">
          In lavorazione
        </h2>
        <EmptyState
          v-if="!inProgress.length"
          icon="🔧"
          title="Nessuna segnalazione in lavorazione"
        />
        <div v-else class="space-y-2">
          <CaretakerTicketRow
            v-for="t in inProgress"
            :key="t.id"
            :ticket="t"
            :acting="acting"
            @act="quickTransition"
          />
        </div>
      </section>
    </template>
  </div>
</template>
