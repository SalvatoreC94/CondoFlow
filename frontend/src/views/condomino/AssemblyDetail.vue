<script setup>
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";

import Card from "@/components/ui/Card.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import api from "@/lib/api";

const route = useRoute();
const assembly = ref(null);
const loading = ref(true);

const statusBadge = {
  scheduled: "bg-primary-50 text-primary-700",
  held: "bg-green-50 text-green-700",
  cancelled: "bg-slate-100 text-slate-500",
};

const outcomeBadge = {
  approved: "bg-green-50 text-green-700",
  rejected: "bg-red-50 text-red-700",
  postponed: "bg-amber-50 text-amber-700",
};

onMounted(async () => {
  const { data } = await api.get(`/api/assemblies/${route.params.id}`);
  assembly.value = data.data;
  loading.value = false;
});
</script>

<template>
  <div class="space-y-4 px-4 pt-6 pb-6">
    <Skeleton v-if="loading" :rows="4" />

    <template v-else-if="assembly">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-900">{{ assembly.title }}</h1>
          <p class="mt-1 text-sm text-slate-500">
            {{ new Date(assembly.scheduled_at).toLocaleString("it-IT") }} ·
            {{ assembly.type_label }}
            <span v-if="assembly.location"> · {{ assembly.location }}</span>
          </p>
        </div>
        <span
          class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
          :class="statusBadge[assembly.status]"
          >{{ assembly.status_label }}</span
        >
      </div>

      <Card>
        <h2 class="mb-2 text-sm font-semibold text-slate-900">
          Ordine del giorno
        </h2>
        <p class="whitespace-pre-line text-sm text-slate-700">
          {{ assembly.agenda }}
        </p>
      </Card>

      <Card v-if="assembly.minutes_document">
        <h2 class="mb-2 text-sm font-semibold text-slate-900">Verbale</h2>
        <a
          :href="assembly.minutes_document.download_url"
          target="_blank"
          rel="noopener"
          class="text-sm font-medium text-primary-600 hover:text-primary-700"
          >📄 {{ assembly.minutes_document.original_name }}</a
        >
      </Card>

      <Card v-if="assembly.resolutions?.length">
        <h2 class="mb-2 text-sm font-semibold text-slate-900">Delibere</h2>
        <div class="space-y-2">
          <div
            v-for="r in assembly.resolutions"
            :key="r.id"
            class="flex items-start justify-between gap-2 rounded-lg border border-slate-100 p-2.5"
          >
            <div class="min-w-0 flex-1">
              <p class="text-sm text-slate-800">{{ r.description }}</p>
              <p v-if="r.notes" class="mt-0.5 text-xs text-slate-500">
                {{ r.notes }}
              </p>
            </div>
            <span
              class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
              :class="outcomeBadge[r.outcome]"
              >{{ r.outcome_label }}</span
            >
          </div>
        </div>
      </Card>
    </template>
  </div>
</template>
