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

const announcements = ref([]);
const buildings = ref([]);
const loading = ref(true);
const modalOpen = ref(false);
const submitting = ref(false);
const errors = ref({});

const form = ref({
  title: "",
  content: "",
  priority: "normal",
  audience: "all",
  building_ids: [],
});

const priorityDot = {
  normal: "bg-slate-300",
  important: "bg-amber-500",
  urgent: "bg-red-500",
};

async function load() {
  if (!selectedId.value) return;
  loading.value = true;
  const [annRes, buildRes] = await Promise.all([
    api.get("/api/announcements", {
      params: { condominium_id: selectedId.value, per_page: 50 },
    }),
    api.get(`/api/condominiums/${selectedId.value}/buildings`),
  ]);
  announcements.value = annRes.data.data;
  buildings.value = buildRes.data.data;
  loading.value = false;
}

onMounted(async () => {
  if (!tenant.loaded) await tenant.fetchCondominiums();
  load();
});
watch(selectedId, load);

async function submit() {
  submitting.value = true;
  errors.value = {};
  try {
    await api.post("/api/announcements", {
      ...form.value,
      condominium_id: selectedId.value,
    });
    modalOpen.value = false;
    form.value = {
      title: "",
      content: "",
      priority: "normal",
      audience: "all",
      building_ids: [],
    };
    toast.success("Comunicazione pubblicata.");
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
      <h1 class="text-xl font-bold text-slate-900">Comunicazioni</h1>
      <Button size="sm" @click="modalOpen = true">+ Nuova comunicazione</Button>
    </div>

    <Skeleton v-if="loading" :rows="4" />
    <EmptyState
      v-else-if="announcements.length === 0"
      icon="📣"
      title="Nessuna comunicazione"
    />

    <div v-else class="space-y-2">
      <Card v-for="a in announcements" :key="a.id">
        <div class="flex items-start gap-2">
          <span
            class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full"
            :class="priorityDot[a.priority]"
          />
          <div class="min-w-0 flex-1">
            <p class="font-semibold text-slate-900">{{ a.title }}</p>
            <p class="mt-1 line-clamp-2 text-sm text-slate-600">
              {{ a.content }}
            </p>
            <p class="mt-1 text-xs text-slate-400">
              {{ new Date(a.published_at).toLocaleDateString("it-IT") }} ·
              {{
                {
                  all: "Tutti",
                  buildings: "Edificio/scala",
                  users: "Gruppo specifico",
                }[a.audience]
              }}
            </p>
          </div>
        </div>
      </Card>
    </div>

    <Modal
      :open="modalOpen"
      title="Nuova comunicazione"
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
          v-model="form.content"
          label="Contenuto"
          required
          :rows="4"
          :error="errors.content?.[0]"
        />
        <SelectField
          v-model="form.priority"
          label="Priorità"
          :options="[
            { value: 'normal', label: 'Normale' },
            { value: 'important', label: 'Importante' },
            { value: 'urgent', label: 'Urgente' },
          ]"
        />
        <SelectField
          v-model="form.audience"
          label="Destinatari"
          :options="[
            { value: 'all', label: 'Tutto il condominio' },
            { value: 'buildings', label: 'Edificio/scala specifico' },
          ]"
        />
        <div v-if="form.audience === 'buildings'">
          <span class="mb-1.5 block text-sm font-medium text-slate-700"
            >Seleziona edifici</span
          >
          <div class="flex flex-wrap gap-2">
            <label
              v-for="b in buildings"
              :key="b.id"
              class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm"
            >
              <input
                v-model="form.building_ids"
                type="checkbox"
                :value="b.id"
              />
              {{ b.name }}
            </label>
          </div>
        </div>
        <Button type="submit" block :loading="submitting">Pubblica</Button>
      </form>
    </Modal>
  </div>
</template>
