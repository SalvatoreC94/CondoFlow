<script setup>
import { onMounted, ref } from "vue";
import { RouterLink } from "vue-router";

import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
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

const loading = ref(true);
const modalOpen = ref(false);
const submitting = ref(false);
const errors = ref({});

const form = ref({
  name: "",
  address: "",
  city: "",
  province: "",
  postal_code: "",
  total_units: "",
  description: "",
});

async function load() {
  loading.value = true;
  await tenant.fetchCondominiums();
  loading.value = false;
}

onMounted(load);

async function submit() {
  submitting.value = true;
  errors.value = {};
  try {
    await api.post("/api/condominiums", form.value);
    modalOpen.value = false;
    form.value = {
      name: "",
      address: "",
      city: "",
      province: "",
      postal_code: "",
      total_units: "",
      description: "",
    };
    toast.success("Condominio creato.");
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
      <h1 class="text-xl font-bold text-slate-900">Condomini</h1>
      <Button size="sm" @click="modalOpen = true">+ Nuovo condominio</Button>
    </div>

    <Skeleton v-if="loading" :rows="3" />

    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <RouterLink
        v-for="c in tenant.condominiums"
        :key="c.id"
        :to="`/admin/condomini/${c.id}`"
      >
        <Card>
          <p class="text-base font-semibold text-slate-900">{{ c.name }}</p>
          <p class="text-sm text-slate-500">{{ c.city }}</p>
          <p class="mt-3 text-sm text-slate-600">
            {{ c.units_count ?? c.total_units }} unità
          </p>
        </Card>
      </RouterLink>
    </div>

    <Modal
      :open="modalOpen"
      title="Nuovo condominio"
      @close="modalOpen = false"
    >
      <form class="space-y-3" @submit.prevent="submit">
        <TextField
          v-model="form.name"
          label="Nome"
          required
          :error="errors.name?.[0]"
        />
        <TextField
          v-model="form.address"
          label="Indirizzo"
          required
          :error="errors.address?.[0]"
        />
        <div class="grid grid-cols-2 gap-3">
          <TextField
            v-model="form.city"
            label="Città"
            required
            :error="errors.city?.[0]"
          />
          <TextField
            v-model="form.province"
            label="Provincia"
            :error="errors.province?.[0]"
          />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <TextField
            v-model="form.postal_code"
            label="CAP"
            :error="errors.postal_code?.[0]"
          />
          <TextField
            v-model="form.total_units"
            label="Numero unità"
            type="number"
            :error="errors.total_units?.[0]"
          />
        </div>
        <TextareaField
          v-model="form.description"
          label="Descrizione (opzionale)"
        />
        <Button type="submit" block :loading="submitting"
          >Crea condominio</Button
        >
      </form>
    </Modal>
  </div>
</template>
