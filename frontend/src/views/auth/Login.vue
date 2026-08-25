<script setup>
import { ref } from "vue";
import { RouterLink, useRouter } from "vue-router";

import Button from "@/components/ui/Button.vue";
import TextField from "@/components/ui/TextField.vue";
import { parseApiError } from "@/lib/errors";
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const router = useRouter();

const form = ref({ identifier: "", password: "" });
const errors = ref({});
const generalError = ref("");
const loading = ref(false);

async function submit() {
  loading.value = true;
  errors.value = {};
  generalError.value = "";

  try {
    await auth.login(form.value);
    router.push(auth.homeRoute);
  } catch (error) {
    const parsed = parseApiError(error);
    errors.value = parsed.errors;
    generalError.value = parsed.status === 422 ? "" : parsed.message;
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-sm">
      <div class="mb-8 flex flex-col items-center">
        <div
          class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-600 text-xl font-bold text-white"
        >
          C
        </div>
        <h1 class="text-2xl font-bold text-slate-900">CondoFlow</h1>
        <p class="mt-1 text-sm text-slate-500">Accedi al tuo condominio</p>
      </div>

      <form
        class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        @submit.prevent="submit"
      >
        <p
          v-if="generalError"
          class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {{ generalError }}
        </p>

        <TextField
          v-model="form.identifier"
          type="text"
          label="Email o numero di cellulare"
          autocomplete="username"
          required
          :error="errors.identifier?.[0]"
        />
        <TextField
          v-model="form.password"
          type="password"
          label="Password"
          autocomplete="current-password"
          required
          :error="errors.password?.[0]"
        />

        <Button type="submit" block :loading="loading">Accedi</Button>

        <RouterLink
          to="/password-dimenticata"
          class="block text-center text-sm font-medium text-primary-600 hover:text-primary-700"
        >
          Password dimenticata?
        </RouterLink>
      </form>

      <p class="mt-6 text-center text-xs text-slate-400">
        L'accesso a CondoFlow avviene solo su invito del tuo amministratore.
      </p>
    </div>
  </div>
</template>
