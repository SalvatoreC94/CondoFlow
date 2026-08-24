<script setup>
import { computed, onMounted, ref } from "vue";

import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import api from "@/lib/api";
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const documents = ref([]);
const categories = ref([]);
const loading = ref(true);
const activeCategory = ref(null);

const filtered = computed(() =>
  activeCategory.value
    ? documents.value.filter((d) => d.category?.id === activeCategory.value)
    : documents.value,
);

function formatSize(bytes) {
  if (!bytes) return "";
  const mb = bytes / 1024 / 1024;
  return mb >= 1 ? `${mb.toFixed(1)} MB` : `${Math.round(bytes / 1024)} KB`;
}

onMounted(async () => {
  const condominiumId = auth.user?.units?.[0]?.condominium?.id;
  const [docsRes, catsRes] = await Promise.all([
    condominiumId
      ? api.get("/api/documents", {
          params: { condominium_id: condominiumId, per_page: 100 },
        })
      : Promise.resolve({ data: { data: [] } }),
    api.get("/api/document-categories"),
  ]);
  documents.value = docsRes.data.data;
  categories.value = catsRes.data.data;
  loading.value = false;
});
</script>

<template>
  <div class="space-y-4 px-4 pt-6">
    <h1 class="text-xl font-bold text-slate-900">Documenti</h1>

    <div class="flex gap-2 overflow-x-auto scrollbar-none">
      <button
        type="button"
        class="flex-shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium"
        :class="
          !activeCategory
            ? 'bg-primary-600 text-white'
            : 'bg-white text-slate-600 border border-slate-200'
        "
        @click="activeCategory = null"
      >
        Tutti
      </button>
      <button
        v-for="cat in categories"
        :key="cat.id"
        type="button"
        class="flex-shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium"
        :class="
          activeCategory === cat.id
            ? 'bg-primary-600 text-white'
            : 'bg-white text-slate-600 border border-slate-200'
        "
        @click="activeCategory = cat.id"
      >
        {{ cat.name }}
      </button>
    </div>

    <Skeleton v-if="loading" :rows="4" />
    <EmptyState
      v-else-if="filtered.length === 0"
      icon="📄"
      title="Nessun documento"
      description="Non ci sono documenti pubblicati in questa categoria."
    />

    <div v-else class="space-y-2">
      <a
        v-for="doc in filtered"
        :key="doc.id"
        :href="doc.download_url"
        target="_blank"
        rel="noopener"
      >
        <Card class="flex items-center gap-3">
          <span class="text-2xl">📄</span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900">
              {{ doc.title }}
            </p>
            <p class="text-xs text-slate-500">
              {{ doc.category?.name }} · {{ formatSize(doc.size) }}
            </p>
          </div>
        </Card>
      </a>
    </div>
  </div>
</template>
