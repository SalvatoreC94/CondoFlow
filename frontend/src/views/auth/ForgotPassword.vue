<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";

import Button from "@/components/ui/Button.vue";
import TextField from "@/components/ui/TextField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";

const email = ref("");
const sent = ref(false);
const loading = ref(false);
const errors = ref({});

async function submit() {
  loading.value = true;
  errors.value = {};
  try {
    await api.post("/api/forgot-password", { email: email.value });
    sent.value = true;
  } catch (error) {
    errors.value = parseApiError(error).errors;
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-sm">
      <h1 class="mb-6 text-center text-2xl font-bold text-slate-900">
        Password dimenticata
      </h1>

      <div
        v-if="sent"
        class="rounded-2xl border border-slate-200 bg-white p-6 text-center"
      >
        <p class="text-sm text-slate-600">
          Se l'indirizzo <strong>{{ email }}</strong> è registrato, riceverai a
          breve un'email con le istruzioni per reimpostare la password.
        </p>
        <RouterLink
          to="/login"
          class="mt-4 inline-block text-sm font-medium text-primary-600"
          >Torna al login</RouterLink
        >
      </div>

      <form
        v-else
        class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        @submit.prevent="submit"
      >
        <p class="text-sm text-slate-600">
          Inserisci il tuo indirizzo email, ti invieremo un link per reimpostare
          la password.
        </p>
        <TextField
          v-model="email"
          type="email"
          label="Email"
          required
          :error="errors.email?.[0]"
        />
        <Button type="submit" block :loading="loading">Invia link</Button>
        <RouterLink
          to="/login"
          class="block text-center text-sm font-medium text-slate-500"
          >Torna al login</RouterLink
        >
      </form>
    </div>
  </div>
</template>
