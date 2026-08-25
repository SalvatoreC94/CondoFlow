<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import Button from "@/components/ui/Button.vue";
import TextField from "@/components/ui/TextField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";
import { useAuthStore } from "@/stores/auth";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const invite = ref(null);
const invalidToken = ref(false);
const loadingInvite = ref(true);

const form = ref({ password: "", password_confirmation: "" });
const errors = ref({});
const generalError = ref("");
const submitting = ref(false);

onMounted(async () => {
  try {
    const { data } = await api.get(`/api/invitations/${route.params.token}`);
    invite.value = data.data;
  } catch {
    invalidToken.value = true;
  } finally {
    loadingInvite.value = false;
  }
});

async function submit() {
  submitting.value = true;
  errors.value = {};
  generalError.value = "";

  try {
    await api.post(`/api/invitations/${route.params.token}/accept`, form.value);
    await auth.fetchUser();
    router.push(auth.homeRoute);
  } catch (error) {
    const parsed = parseApiError(error);
    errors.value = parsed.errors;
    generalError.value = Object.keys(parsed.errors).length
      ? ""
      : parsed.message;
  } finally {
    submitting.value = false;
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
        <h1 class="text-2xl font-bold text-slate-900">
          Benvenuto su CondoFlow
        </h1>
      </div>

      <div
        v-if="loadingInvite"
        class="rounded-2xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500"
      >
        Verifica dell'invito in corso…
      </div>

      <div
        v-else-if="invalidToken"
        class="rounded-2xl border border-slate-200 bg-white p-6 text-center"
      >
        <p class="text-sm font-semibold text-slate-900">
          Invito non valido o scaduto
        </p>
        <p class="mt-1 text-sm text-slate-500">
          Chiedi al tuo amministratore di inviarti un nuovo invito.
        </p>
      </div>

      <form
        v-else
        class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        @submit.prevent="submit"
      >
        <p class="text-sm text-slate-600">
          Ciao <strong>{{ invite.name }}</strong
          >, imposta una password per completare la registrazione con
          <template v-if="invite.email">
            l'indirizzo <strong>{{ invite.email }}</strong></template
          ><template v-else>
            il numero <strong>{{ invite.phone }}</strong></template
          >.
        </p>

        <p
          v-if="generalError"
          class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {{ generalError }}
        </p>

        <TextField
          v-model="form.password"
          type="password"
          label="Nuova password"
          autocomplete="new-password"
          required
          :error="errors.password?.[0]"
        />
        <TextField
          v-model="form.password_confirmation"
          type="password"
          label="Conferma password"
          autocomplete="new-password"
          required
        />

        <Button type="submit" block :loading="submitting"
          >Imposta password e accedi</Button
        >
      </form>
    </div>
  </div>
</template>
