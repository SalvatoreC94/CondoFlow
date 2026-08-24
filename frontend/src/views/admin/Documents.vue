<script setup>
import { storeToRefs } from "pinia";
import { onMounted, ref, watch } from "vue";

import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Modal from "@/components/ui/Modal.vue";
import SelectField from "@/components/ui/SelectField.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import TextField from "@/components/ui/TextField.vue";
import TextareaField from "@/components/ui/TextareaField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";
import { useTenantStore } from "@/stores/tenant";
import { useToastStore } from "@/stores/toast";

const tenant = useTenantStore();
const { selectedId } = storeToRefs(tenant);
const toast = useToastStore();

const documents = ref([]);
const categories = ref([]);
const loading = ref(true);
const modalOpen = ref(false);
const submitting = ref(false);
const errors = ref({});
const file = ref(null);

const form = ref({
  title: "",
  description: "",
  document_category_id: "",
  visibility: "all",
});

function formatSize(bytes) {
  if (!bytes) return "";
  const mb = bytes / 1024 / 1024;
  return mb >= 1 ? `${mb.toFixed(1)} MB` : `${Math.round(bytes / 1024)} KB`;
}

async function load() {
  if (!selectedId.value) return;
  loading.value = true;
  const { data } = await api.get("/api/documents", {
    params: { condominium_id: selectedId.value, per_page: 100 },
  });
  documents.value = data.data;
  loading.value = false;
}

onMounted(async () => {
  if (!tenant.loaded) await tenant.fetchCondominiums();
  const { data } = await api.get("/api/document-categories");
  categories.value = data.data;
  load();
});
watch(selectedId, load);

async function submit() {
  submitting.value = true;
  errors.value = {};
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => fd.append(k, v));
    fd.append("condominium_id", selectedId.value);
    if (file.value) fd.append("file", file.value);

    await api.post("/api/documents", fd, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    modalOpen.value = false;
    form.value = {
      title: "",
      description: "",
      document_category_id: "",
      visibility: "all",
    };
    file.value = null;
    toast.success("Documento pubblicato.");
    await load();
  } catch (error) {
    errors.value = parseApiError(error).errors;
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold text-slate-900">Documenti</h1>
      <Button size="sm" @click="modalOpen = true">+ Carica documento</Button>
    </div>

    <Skeleton v-if="loading" :rows="4" />
    <EmptyState
      v-else-if="documents.length === 0"
      icon="📄"
      title="Nessun documento"
    />

    <div v-else class="space-y-2">
      <a
        v-for="doc in documents"
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
              {{ doc.category?.name }} · {{ formatSize(doc.size) }} ·
              {{ doc.visibility }}
            </p>
          </div>
        </Card>
      </a>
    </div>

    <Modal
      :open="modalOpen"
      title="Carica documento"
      @close="modalOpen = false"
    >
      <form class="space-y-3" @submit.prevent="submit">
        <TextField
          v-model="form.title"
          label="Titolo"
          required
          :error="errors.title?.[0]"
        />
        <TextareaField
          v-model="form.description"
          label="Descrizione (opzionale)"
        />
        <SelectField
          v-model="form.document_category_id"
          label="Categoria"
          :options="categories.map((c) => ({ value: c.id, label: c.name }))"
          :error="errors.document_category_id?.[0]"
        />
        <SelectField
          v-model="form.visibility"
          label="Visibilità"
          :options="[
            { value: 'all', label: 'Tutti' },
            { value: 'condomini', label: 'Solo condòmini' },
            { value: 'caretakers', label: 'Solo custodi' },
            { value: 'administrators', label: 'Solo amministratori' },
          ]"
        />
        <label class="block">
          <span class="mb-1.5 block text-sm font-medium text-slate-700"
            >File</span
          >
          <input
            type="file"
            class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700"
            @change="file = $event.target.files[0]"
          />
          <span
            v-if="errors.file?.[0]"
            class="mt-1 block text-xs text-red-600"
            >{{ errors.file[0] }}</span
          >
        </label>
        <Button type="submit" block :loading="submitting"
          >Pubblica documento</Button
        >
      </form>
    </Modal>
  </div>
</template>
