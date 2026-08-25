<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";

import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import api from "@/lib/api";
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const assemblies = ref([]);
const loading = ref(true);
const filter = ref("upcoming");

const statusBadge = {
  scheduled: "bg-primary-50 text-primary-700",
  held: "bg-green-50 text-green-700",
  cancelled: "bg-slate-100 text-slate-500",
};

const filtered = computed(() => {
  const now = new Date();
  if (filter.value === "upcoming") {
    return assemblies.value.filter(
      (a) => a.status === "scheduled" && new Date(a.scheduled_at) >= now,
    );
  }
  if (filter.value === "past") {
    return assemblies.value.filter(
      (a) => a.status !== "scheduled" || new Date(a.scheduled_at) < now,
    );
  }
  return assemblies.value;
});

onMounted(async () => {
  const condominiumId = auth.user?.units?.[0]?.condominium?.id;
  if (!condominiumId) {
    loading.value = false;
    return;
  }
  const { data } = await api.get("/api/assemblies", {
    params: { condominium_id: condominiumId, per_page: 50 },
  });
  assemblies.value = data.data;
  loading.value = false;
});
</script>

<template>
  <div class="space-y-4 px-4 pt-6">
    <h1 class="text-xl font-bold text-slate-900">Assemblee</h1>

    <div class="flex gap-2">
      <button
        v-for="opt in [
          { value: 'upcoming', label: 'Prossime' },
          { value: 'past', label: 'Passate' },
          { value: 'all', label: 'Tutte' },
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
      icon="🗳️"
      title="Nessuna assemblea"
      description="Non ci sono assemblee in questa categoria."
    />

    <div v-else class="space-y-2">
      <RouterLink
        v-for="a in filtered"
        :key="a.id"
        :to="`/app/assemblee/${a.id}`"
      >
        <Card>
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-slate-900">
                {{ a.title }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ new Date(a.scheduled_at).toLocaleString("it-IT") }} ·
                {{ a.type_label }}
              </p>
            </div>
            <span
              class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
              :class="statusBadge[a.status]"
              >{{ a.status_label }}</span
            >
          </div>
        </Card>
      </RouterLink>
    </div>
  </div>
</template>
