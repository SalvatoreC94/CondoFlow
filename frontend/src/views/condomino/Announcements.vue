<script setup>
import { onMounted, ref } from "vue";
import { RouterLink } from "vue-router";

import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import api from "@/lib/api";
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const announcements = ref([]);
const loading = ref(true);

const priorityDot = {
  normal: "bg-slate-300",
  important: "bg-amber-500",
  urgent: "bg-red-500",
};

onMounted(async () => {
  const condominiumId = auth.user?.units?.[0]?.condominium?.id;
  if (!condominiumId) {
    loading.value = false;
    return;
  }
  const { data } = await api.get("/api/announcements", {
    params: { condominium_id: condominiumId, per_page: 50 },
  });
  announcements.value = data.data;
  loading.value = false;
});
</script>

<template>
  <div class="space-y-4 px-4 pt-6">
    <h1 class="text-xl font-bold text-slate-900">Comunicazioni</h1>

    <Skeleton v-if="loading" :rows="4" />

    <EmptyState
      v-else-if="announcements.length === 0"
      icon="📣"
      title="Nessuna comunicazione"
      description="Il tuo amministratore non ha ancora pubblicato comunicazioni."
    />

    <div v-else class="space-y-2">
      <RouterLink
        v-for="a in announcements"
        :key="a.id"
        :to="`/app/comunicazioni/${a.id}`"
      >
        <Card>
          <div class="flex items-start gap-2">
            <span
              class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full"
              :class="priorityDot[a.priority]"
            />
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-slate-900">
                {{ a.title }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ new Date(a.published_at).toLocaleDateString("it-IT") }} ·
                {{ a.author?.name }}
              </p>
            </div>
            <span
              v-if="!a.is_read"
              class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-primary-600"
            />
          </div>
        </Card>
      </RouterLink>
    </div>
  </div>
</template>
