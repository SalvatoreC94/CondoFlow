<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import Button from "@/components/ui/Button.vue";
import SelectField from "@/components/ui/SelectField.vue";
import TextField from "@/components/ui/TextField.vue";
import TextareaField from "@/components/ui/TextareaField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";
import { useAuthStore } from "@/stores/auth";
import { useToastStore } from "@/stores/toast";

const auth = useAuthStore();
const router = useRouter();
const toast = useToastStore();

const categories = ref([]);
const units = ref([]);

const form = ref({
  unit_id: "",
  ticket_category_id: "",
  title: "",
  description: "",
  priority: "medium",
  location: "",
});
const photos = ref([]);
const errors = ref({});
const submitting = ref(false);

const priorityOptions = [
  { value: "low", label: "Bassa" },
  { value: "medium", label: "Media" },
  { value: "high", label: "Alta" },
  { value: "urgent", label: "Urgente" },
];

onMounted(async () => {
  units.value = auth.user?.units ?? [];
  if (units.value.length === 1) form.value.unit_id = units.value[0].id;

  const { data } = await api.get("/api/ticket-categories");
  categories.value = data.data;
});

function onFileChange(event) {
  photos.value = Array.from(event.target.files || []).slice(0, 5);
}

async function submit() {
  submitting.value = true;
  errors.value = {};
  try {
    const condominiumId = units.value.find(
      (u) => u.id === Number(form.value.unit_id),
    )?.condominium_id;
    const { data } = await api.post("/api/tickets", {
      ...form.value,
      unit_id: form.value.unit_id || null,
      condominium_id: condominiumId,
    });

    const ticketId = data.data.id;
    for (const file of photos.value) {
      const fd = new FormData();
      fd.append("file", file);
      await api.post(`/api/tickets/${ticketId}/attachments`, fd, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    }

    toast.success("Segnalazione inviata.");
    router.push(`/app/segnalazioni/${ticketId}`);
  } catch (error) {
    errors.value = parseApiError(error).errors;
    toast.error("Controlla i campi del modulo.");
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="space-y-5 px-4 pt-6 pb-6">
    <h1 class="text-xl font-bold text-slate-900">Nuova segnalazione</h1>

    <form class="space-y-4" @submit.prevent="submit">
      <SelectField
        v-model="form.ticket_category_id"
        label="Categoria"
        placeholder="Seleziona una categoria"
        :options="categories.map((c) => ({ value: c.id, label: c.name }))"
        :error="errors.ticket_category_id?.[0]"
      />

      <SelectField
        v-if="units.length > 1"
        v-model="form.unit_id"
        label="Unità immobiliare"
        placeholder="Seleziona la tua unità"
        :options="
          units.map((u) => ({
            value: u.id,
            label: `${u.condominium?.name} — ${u.code}`,
          }))
        "
        :error="errors.unit_id?.[0]"
      />

      <TextField
        v-model="form.title"
        label="Titolo"
        placeholder="Es. Perdita d'acqua in bagno"
        required
        :error="errors.title?.[0]"
      />

      <TextareaField
        v-model="form.description"
        label="Descrizione"
        placeholder="Descrivi il problema con qualche dettaglio in più"
        required
        :error="errors.description?.[0]"
      />

      <SelectField
        v-model="form.priority"
        label="Priorità"
        :options="priorityOptions"
        :error="errors.priority?.[0]"
      />

      <TextField
        v-model="form.location"
        label="Posizione (opzionale)"
        placeholder="Es. Piano terra, cortile…"
      />

      <label class="block">
        <span class="mb-1.5 block text-sm font-medium text-slate-700"
          >Foto (opzionale)</span
        >
        <input
          type="file"
          accept="image/*"
          capture="environment"
          multiple
          class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700"
          @change="onFileChange"
        />
        <span v-if="photos.length" class="mt-1 block text-xs text-slate-500"
          >{{ photos.length }} file selezionati</span
        >
      </label>

      <Button type="submit" block :loading="submitting"
        >Invia segnalazione</Button
      >
    </form>
  </div>
</template>
