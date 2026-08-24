<script setup>
import { storeToRefs } from "pinia";
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";

import Card from "@/components/ui/Card.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import api from "@/lib/api";
import { useTenantStore } from "@/stores/tenant";

const tenant = useTenantStore();
const { selectedId } = storeToRefs(tenant);

const stats = ref(null);
const loading = ref(true);

async function load() {
  if (!selectedId.value) return;
  loading.value = true;
  const { data } = await api.get("/api/dashboard/stats", {
    params: { condominium_id: selectedId.value },
  });
  stats.value = data.data;
  loading.value = false;
}

onMounted(async () => {
  if (!tenant.loaded) await tenant.fetchCondominiums();
  load();
});

watch(selectedId, load);

const tiles = computed(() => [
  {
    label: "Condomini",
    value: stats.value?.condominiums_count,
    icon: "🏢",
    tone: "text-slate-900",
  },
  {
    label: "Unità",
    value: stats.value?.units_count,
    icon: "🏠",
    tone: "text-slate-900",
  },
  {
    label: "Segnalazioni aperte",
    value: stats.value?.tickets_open,
    icon: "🔧",
    tone: "text-primary-700",
  },
  {
    label: "Urgenti",
    value: stats.value?.tickets_urgent,
    icon: "🚨",
    tone: "text-red-600",
  },
  {
    label: "In attesa fornitore",
    value: stats.value?.tickets_waiting_supplier,
    icon: "⏳",
    tone: "text-amber-600",
  },
  {
    label: "Risolte",
    value: stats.value?.tickets_resolved,
    icon: "✅",
    tone: "text-emerald-600",
  },
]);
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-slate-900">Dashboard</h1>
      <p class="text-sm text-slate-500">{{ tenant.selected?.name }}</p>
    </div>

    <Skeleton v-if="loading" :rows="3" />

    <template v-else-if="stats">
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <Card v-for="tile in tiles" :key="tile.label" class="text-center">
          <div class="text-xl">{{ tile.icon }}</div>
          <p class="mt-1 text-2xl font-bold" :class="tile.tone">
            {{ tile.value ?? 0 }}
          </p>
          <p class="text-xs text-slate-500">{{ tile.label }}</p>
        </Card>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card>
          <h2 class="mb-3 text-sm font-semibold text-slate-900">
            Tempo medio di risoluzione
          </h2>
          <p class="text-3xl font-bold text-slate-900">
            {{
              stats.avg_resolution_hours != null
                ? `${stats.avg_resolution_hours}h`
                : "—"
            }}
          </p>
          <p class="text-xs text-slate-500">
            Sulle segnalazioni risolte nel periodo selezionato
          </p>
        </Card>

        <Card>
          <h2 class="mb-3 text-sm font-semibold text-slate-900">
            Fornitori da sollecitare
          </h2>
          <div
            v-if="!stats.suppliers_to_follow_up?.length"
            class="text-sm text-slate-400"
          >
            Nessun fornitore in attesa.
          </div>
          <ul v-else class="space-y-2">
            <li
              v-for="row in stats.suppliers_to_follow_up"
              :key="row.supplier?.id"
              class="flex items-center justify-between text-sm"
            >
              <span class="font-medium text-slate-700">{{
                row.supplier?.name
              }}</span>
              <span class="text-slate-500"
                >{{ row.tickets_waiting }} in attesa</span
              >
            </li>
          </ul>
        </Card>
      </div>

      <Card>
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">
            Comunicazioni recenti
          </h2>
          <RouterLink
            to="/admin/comunicazioni"
            class="text-xs font-medium text-primary-600"
            >Vedi tutte</RouterLink
          >
        </div>
        <div
          v-if="!stats.recent_announcements?.length"
          class="text-sm text-slate-400"
        >
          Nessuna comunicazione pubblicata.
        </div>
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="a in stats.recent_announcements"
            :key="a.id"
            class="py-2 text-sm"
          >
            <p class="font-medium text-slate-800">{{ a.title }}</p>
            <p class="text-xs text-slate-500">
              {{ new Date(a.published_at).toLocaleDateString("it-IT") }}
            </p>
          </li>
        </ul>
      </Card>
    </template>
  </div>
</template>
