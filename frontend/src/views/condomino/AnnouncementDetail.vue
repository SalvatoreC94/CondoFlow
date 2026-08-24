<script setup>
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";

import Card from "@/components/ui/Card.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import api from "@/lib/api";

const route = useRoute();
const announcement = ref(null);
const loading = ref(true);

onMounted(async () => {
  const { data } = await api.get(`/api/announcements/${route.params.id}`);
  announcement.value = data.data;
  loading.value = false;
  await api.post(`/api/announcements/${route.params.id}/read`).catch(() => {});
});
</script>

<template>
  <div class="space-y-4 px-4 pt-6 pb-6">
    <Skeleton v-if="loading" :rows="3" />
    <template v-else-if="announcement">
      <div>
        <h1 class="text-xl font-bold text-slate-900">
          {{ announcement.title }}
        </h1>
        <p class="mt-1 text-xs text-slate-500">
          {{ new Date(announcement.published_at).toLocaleDateString("it-IT") }}
          · {{ announcement.author?.name }}
        </p>
      </div>
      <Card>
        <p class="whitespace-pre-line text-sm text-slate-700">
          {{ announcement.content }}
        </p>
      </Card>
    </template>
  </div>
</template>
