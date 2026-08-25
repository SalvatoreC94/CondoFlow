<script setup>
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";

import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
import SelectField from "@/components/ui/SelectField.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import TextareaField from "@/components/ui/TextareaField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";
import { useToastStore } from "@/stores/toast";

const route = useRoute();
const toast = useToastStore();

const assembly = ref(null);
const loading = ref(true);
const updatingStatus = ref(false);

const resolutionForm = ref({ description: "", outcome: "approved", notes: "" });
const resolutionErrors = ref({});
const savingResolution = ref(false);

const minutesFile = ref(null);
const uploadingMinutes = ref(false);

const outcomeBadge = {
  approved: "bg-green-50 text-green-700",
  rejected: "bg-red-50 text-red-700",
  postponed: "bg-amber-50 text-amber-700",
};

async function load() {
  loading.value = true;
  const { data } = await api.get(`/api/assemblies/${route.params.id}`);
  assembly.value = data.data;
  loading.value = false;
}

onMounted(load);

async function setStatus(status) {
  updatingStatus.value = true;
  try {
    const payload = { status };
    if (status === "held") payload.held_at = new Date().toISOString();
    const { data } = await api.put(
      `/api/assemblies/${assembly.value.id}`,
      payload,
    );
    assembly.value = data.data;
    toast.success("Stato assemblea aggiornato.");
  } catch (error) {
    toast.error(parseApiError(error).message);
  } finally {
    updatingStatus.value = false;
  }
}

async function addResolution() {
  savingResolution.value = true;
  resolutionErrors.value = {};
  try {
    await api.post(
      `/api/assemblies/${assembly.value.id}/resolutions`,
      resolutionForm.value,
    );
    resolutionForm.value = { description: "", outcome: "approved", notes: "" };
    toast.success("Delibera aggiunta.");
    await load();
  } catch (error) {
    resolutionErrors.value = parseApiError(error).errors;
  } finally {
    savingResolution.value = false;
  }
}

async function deleteResolution(id) {
  await api.delete(`/api/assembly-resolutions/${id}`);
  toast.success("Delibera rimossa.");
  await load();
}

async function uploadMinutes() {
  if (!minutesFile.value) return;
  uploadingMinutes.value = true;
  try {
    const fd = new FormData();
    fd.append("file", minutesFile.value);
    const { data } = await api.post(
      `/api/assemblies/${assembly.value.id}/minutes`,
      fd,
      {
        headers: { "Content-Type": "multipart/form-data" },
      },
    );
    assembly.value = data.data;
    minutesFile.value = null;
    toast.success("Verbale caricato.");
  } catch (error) {
    toast.error(parseApiError(error).message);
  } finally {
    uploadingMinutes.value = false;
  }
}
</script>

<template>
  <div class="space-y-4">
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
        <div class="flex flex-shrink-0 gap-2">
          <Button
            v-if="assembly.status === 'scheduled'"
            size="sm"
            :loading="updatingStatus"
            @click="setStatus('held')"
            >Segna come svolta</Button
          >
          <Button
            v-if="assembly.status === 'scheduled'"
            size="sm"
            variant="ghost"
            :loading="updatingStatus"
            @click="setStatus('cancelled')"
            >Annulla</Button
          >
        </div>
      </div>

      <Card>
        <h2 class="mb-2 text-sm font-semibold text-slate-900">
          Ordine del giorno
        </h2>
        <p class="whitespace-pre-line text-sm text-slate-700">
          {{ assembly.agenda }}
        </p>
      </Card>

      <Card>
        <h2 class="mb-2 text-sm font-semibold text-slate-900">Verbale</h2>
        <a
          v-if="assembly.minutes_document"
          :href="assembly.minutes_document.download_url"
          target="_blank"
          rel="noopener"
          class="text-sm font-medium text-primary-600 hover:text-primary-700"
          >📄 {{ assembly.minutes_document.original_name }}</a
        >
        <div v-else class="flex items-center gap-2">
          <input
            type="file"
            accept=".pdf,.doc,.docx"
            class="block flex-1 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700"
            @change="minutesFile = $event.target.files[0]"
          />
          <Button
            size="sm"
            :disabled="!minutesFile"
            :loading="uploadingMinutes"
            @click="uploadMinutes"
            >Carica</Button
          >
        </div>
      </Card>

      <Card>
        <h2 class="mb-2 text-sm font-semibold text-slate-900">Delibere</h2>
        <div v-if="assembly.resolutions?.length" class="mb-4 space-y-2">
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
            <div class="flex flex-shrink-0 items-center gap-2">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="outcomeBadge[r.outcome]"
                >{{ r.outcome_label }}</span
              >
              <button
                type="button"
                class="text-slate-400 hover:text-red-600"
                @click="deleteResolution(r.id)"
              >
                ✕
              </button>
            </div>
          </div>
        </div>
        <p v-else class="mb-4 text-sm text-slate-400">
          Nessuna delibera registrata.
        </p>

        <form
          class="space-y-2 border-t border-slate-100 pt-3"
          @submit.prevent="addResolution"
        >
          <TextareaField
            v-model="resolutionForm.description"
            label="Nuova delibera"
            placeholder="Es. Approvazione bilancio consuntivo"
            :rows="2"
            required
            :error="resolutionErrors.description?.[0]"
          />
          <div class="grid grid-cols-2 gap-2">
            <SelectField
              v-model="resolutionForm.outcome"
              label="Esito"
              :options="[
                { value: 'approved', label: 'Approvata' },
                { value: 'rejected', label: 'Respinta' },
                { value: 'postponed', label: 'Rinviata' },
              ]"
            />
            <Button
              type="submit"
              class="mt-6"
              size="sm"
              :loading="savingResolution"
              >Aggiungi delibera</Button
            >
          </div>
        </form>
      </Card>
    </template>
  </div>
</template>
