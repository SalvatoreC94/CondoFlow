<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import Button from "@/components/ui/Button.vue";
import TextField from "@/components/ui/TextField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";
import { useToastStore } from "@/stores/toast";

const route = useRoute();
const router = useRouter();
const toast = useToastStore();

const form = ref({
  token: route.query.token || "",
  email: route.query.email || "",
  password: "",
  password_confirmation: "",
});
const errors = ref({});
const generalError = ref("");
const loading = ref(false);

async function submit() {
  loading.value = true;
  errors.value = {};
  generalError.value = "";
  try {
    await api.post("/api/reset-password", form.value);
    toast.success("Password aggiornata. Ora puoi accedere.");
    router.push("/login");
  } catch (error) {
    const parsed = parseApiError(error);
    errors.value = parsed.errors;
    generalError.value = Object.keys(parsed.errors).length
      ? ""
      : parsed.message;
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-sm">
      <h1 class="mb-6 text-center text-2xl font-bold text-slate-900">
        Reimposta password
      </h1>

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
          v-model="form.email"
          type="email"
          label="Email"
          required
          :error="errors.email?.[0]"
        />
        <TextField
          v-model="form.password"
          type="password"
          label="Nuova password"
          required
          :error="errors.password?.[0]"
        />
        <TextField
          v-model="form.password_confirmation"
          type="password"
          label="Conferma password"
          required
        />
        <Button type="submit" block :loading="loading"
          >Aggiorna password</Button
        >
      </form>
    </div>
  </div>
</template>
