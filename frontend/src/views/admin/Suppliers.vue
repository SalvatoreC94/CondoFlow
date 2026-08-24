<script setup>
import { onMounted, ref } from "vue";

import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Modal from "@/components/ui/Modal.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import TextField from "@/components/ui/TextField.vue";
import TextareaField from "@/components/ui/TextareaField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";
import { useTenantStore } from "@/stores/tenant";
import { useToastStore } from "@/stores/toast";

const tenant = useTenantStore();
const toast = useToastStore();

const suppliers = ref([]);
const loading = ref(true);
const modalOpen = ref(false);
const submitting = ref(false);
const errors = ref({});

const form = ref({
  name: "",
  category: "",
  phone: "",
  email: "",
  contact_name: "",
  notes: "",
  condominium_ids: [],
});

async function load() {
  loading.value = true;
  const { data } = await api.get("/api/suppliers", {
    params: { per_page: 100 },
  });
  suppliers.value = data.data;
  loading.value = false;
}

onMounted(async () => {
  if (!tenant.loaded) await tenant.fetchCondominiums();
  load();
});

async function submit() {
  submitting.value = true;
  errors.value = {};
  try {
    await api.post("/api/suppliers", form.value);
    modalOpen.value = false;
    form.value = {
      name: "",
      category: "",
      phone: "",
      email: "",
      contact_name: "",
      notes: "",
      condominium_ids: [],
    };
    toast.success("Fornitore creato.");
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
      <h1 class="text-xl font-bold text-slate-900">Fornitori</h1>
      <Button size="sm" @click="modalOpen = true">+ Nuovo fornitore</Button>
    </div>

    <Skeleton v-if="loading" :rows="4" />
    <EmptyState
      v-else-if="suppliers.length === 0"
      icon="🛠️"
      title="Nessun fornitore"
      description="Aggiungi il primo fornitore per assegnare gli interventi."
    />

    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <Card v-for="s in suppliers" :key="s.id">
        <div class="flex items-start justify-between">
          <p class="font-semibold text-slate-900">{{ s.name }}</p>
          <span
            v-if="!s.is_active"
            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500"
            >Inattivo</span
          >
        </div>
        <p class="text-xs font-medium text-primary-600">{{ s.category }}</p>
        <p v-if="s.phone" class="mt-2 text-sm text-slate-600">
          📞 {{ s.phone }}
        </p>
        <p v-if="s.email" class="text-sm text-slate-600">✉️ {{ s.email }}</p>
        <p v-if="s.condominiums?.length" class="mt-2 text-xs text-slate-400">
          {{ s.condominiums.map((c) => c.name).join(", ") }}
        </p>
      </Card>
    </div>

    <Modal :open="modalOpen" title="Nuovo fornitore" @close="modalOpen = false">
      <form class="space-y-3" @submit.prevent="submit">
        <TextField
          v-model="form.name"
          label="Nome"
          required
          :error="errors.name?.[0]"
        />
        <TextField
          v-model="form.category"
          label="Categoria"
          placeholder="Es. Idraulica"
          required
          :error="errors.category?.[0]"
        />
        <div class="grid grid-cols-2 gap-3">
          <TextField
            v-model="form.phone"
            label="Telefono"
            :error="errors.phone?.[0]"
          />
          <TextField
            v-model="form.email"
            type="email"
            label="Email"
            :error="errors.email?.[0]"
          />
        </div>
        <TextField v-model="form.contact_name" label="Referente" />
        <TextareaField v-model="form.notes" label="Note" />
        <div>
          <span class="mb-1.5 block text-sm font-medium text-slate-700"
            >Condomini associati</span
          >
          <div class="flex flex-wrap gap-2">
            <label
              v-for="c in tenant.condominiums"
              :key="c.id"
              class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm"
            >
              <input
                v-model="form.condominium_ids"
                type="checkbox"
                :value="c.id"
              />
              {{ c.name }}
            </label>
          </div>
        </div>
        <Button type="submit" block :loading="submitting"
          >Crea fornitore</Button
        >
      </form>
    </Modal>
  </div>
</template>
