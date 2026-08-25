<script setup>
import { computed, onMounted, ref } from "vue";

import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import api from "@/lib/api";

const charges = ref([]);
const loading = ref(true);
const filter = ref("all");

function currency(amount) {
  return new Intl.NumberFormat("it-IT", {
    style: "currency",
    currency: "EUR",
  }).format(Number(amount ?? 0));
}

const filtered = computed(() => {
  if (filter.value === "unpaid") return charges.value.filter((c) => !c.paid);
  if (filter.value === "paid") return charges.value.filter((c) => c.paid);
  return charges.value;
});

const totalDue = computed(() =>
  charges.value
    .filter((c) => !c.paid)
    .reduce((sum, c) => sum + Number(c.amount), 0),
);

onMounted(async () => {
  const { data } = await api.get("/api/me/charges");
  charges.value = data.data;
  loading.value = false;
});
</script>

<template>
  <div class="space-y-4 px-4 pt-6">
    <h1 class="text-xl font-bold text-slate-900">Le mie spese</h1>

    <Card v-if="!loading" class="bg-primary-50">
      <p class="text-xs font-medium text-primary-700">Totale da pagare</p>
      <p class="text-2xl font-bold text-primary-900">
        {{ currency(totalDue) }}
      </p>
    </Card>

    <div class="flex gap-2">
      <button
        v-for="opt in [
          { value: 'all', label: 'Tutte' },
          { value: 'unpaid', label: 'Da pagare' },
          { value: 'paid', label: 'Pagate' },
        ]"
        :key="opt.value"
        type="button"
        class="flex-shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium"
        :class="
          filter === opt.value
            ? 'bg-primary-600 text-white'
            : 'bg-white text-slate-600 border border-slate-200'
        "
        @click="filter = opt.value"
      >
        {{ opt.label }}
      </button>
    </div>

    <Skeleton v-if="loading" :rows="4" />
    <EmptyState
      v-else-if="filtered.length === 0"
      icon="💶"
      title="Nessuna spesa"
      description="Non ci sono rate condominiali per la tua unità in questa categoria."
    />

    <div v-else class="space-y-2">
      <Card
        v-for="c in filtered"
        :key="c.id"
        class="flex items-center justify-between gap-3"
      >
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-slate-900">
            {{ c.installment?.title }}
          </p>
          <p class="text-xs text-slate-500">
            Unità {{ c.unit?.code }} · Scadenza {{ c.installment?.due_date }}
          </p>
        </div>
        <div class="flex flex-col items-end gap-1">
          <p class="text-sm font-semibold text-slate-900">
            {{ currency(c.amount) }}
          </p>
          <span
            class="rounded-full px-2 py-0.5 text-xs font-semibold"
            :class="
              c.paid
                ? 'bg-green-50 text-green-700'
                : 'bg-amber-50 text-amber-700'
            "
          >
            {{ c.paid ? "Pagata" : "Da pagare" }}
          </span>
        </div>
      </Card>
    </div>
  </div>
</template>
