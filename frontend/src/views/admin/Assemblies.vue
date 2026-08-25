<script setup>
import { storeToRefs } from "pinia";
import { onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";

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

const assemblies = ref([]);
const loading = ref(true);
const modalOpen = ref(false);
const submitting = ref(false);
const errors = ref({});

function emptyForm() {
  return {
    title: "",
    type: "ordinary",
    agenda: "",
    location: "",
    scheduled_at: "",
  };
}
const form = ref(emptyForm());

const statusBadge = {
  scheduled: "bg-primary-50 text-primary-700",
  held: "bg-green-50 text-green-700",
  cancelled: "bg-slate-100 text-slate-500",
};

async function load() {
  if (!selectedId.value) return;
  loading.value = true;
  const { data } = await api.get("/api/assemblies", {
    params: { condominium_id: selectedId.value, per_page: 50 },
  });
  assemblies.value = data.data;
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
    await api.post("/api/assemblies", {
      ...form.value,
      condominium_id: selectedId.value,
    });
    modalOpen.value = false;
    form.value = emptyForm();
    toast.success("Assemblea convocata.");
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
      <h1 class="text-xl font-bold text-slate-900">Assemblee</h1>
      <Button size="sm" @click="modalOpen = true">+ Convoca assemblea</Button>
    </div>

    <Skeleton v-if="loading" :rows="4" />
    <EmptyState
      v-else-if="assemblies.length === 0"
      icon="🗳️"
      title="Nessuna assemblea"
      description="Convoca la prima assemblea condominiale."
    />

    <div v-else class="space-y-2">
      <RouterLink
        v-for="a in assemblies"
        :key="a.id"
        :to="`/admin/assemblee/${a.id}`"
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
                <span v-if="a.location"> · {{ a.location }}</span>
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

    <Modal
      :open="modalOpen"
      title="Convoca assemblea"
      @close="modalOpen = false"
    >
      <form class="space-y-3" @submit.prevent="submit">
        <TextField
          v-model="form.title"
          label="Titolo"
          placeholder="Es. Assemblea ordinaria 2027"
          required
          :error="errors.title?.[0]"
        />
        <SelectField
          v-model="form.type"
          label="Tipo"
          :options="[
            { value: 'ordinary', label: 'Ordinaria' },
            { value: 'extraordinary', label: 'Straordinaria' },
          ]"
        />
        <TextareaField
          v-model="form.agenda"
          label="Ordine del giorno"
          :rows="4"
          required
          :error="errors.agenda?.[0]"
        />
        <TextField
          v-model="form.location"
          label="Luogo (opzionale)"
          placeholder="Es. Sede amministrazione, videoconferenza…"
        />
        <TextField
          v-model="form.scheduled_at"
          type="datetime-local"
          label="Data e ora"
          required
          :error="errors.scheduled_at?.[0]"
        />
        <Button type="submit" block :loading="submitting"
          >Convoca e notifica i condòmini</Button
        >
      </form>
    </Modal>
  </div>
</template>
