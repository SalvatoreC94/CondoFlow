<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import Avatar from "@/components/ui/Avatar.vue";
import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
import TextField from "@/components/ui/TextField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";
import {
  disablePush,
  enablePush,
  getPushSubscription,
  isPushSupported,
} from "@/lib/push";
import { useAuthStore } from "@/stores/auth";
import { useToastStore } from "@/stores/toast";

const auth = useAuthStore();
const router = useRouter();
const toast = useToastStore();

const roleLabels = {
  administrator: "Amministratore",
  caretaker: "Custode",
  condomino: "Condomino",
};

const profileForm = ref({
  name: auth.user?.name ?? "",
  phone: auth.user?.phone ?? "",
});
const profileErrors = ref({});
const savingProfile = ref(false);

const passwordForm = ref({
  current_password: "",
  password: "",
  password_confirmation: "",
});
const passwordErrors = ref({});
const savingPassword = ref(false);

async function saveProfile() {
  savingProfile.value = true;
  profileErrors.value = {};
  try {
    await auth.updateProfile(profileForm.value);
    toast.success("Profilo aggiornato.");
  } catch (error) {
    profileErrors.value = parseApiError(error).errors;
  } finally {
    savingProfile.value = false;
  }
}

async function savePassword() {
  savingPassword.value = true;
  passwordErrors.value = {};
  try {
    await api.put("/api/me/password", passwordForm.value);
    passwordForm.value = {
      current_password: "",
      password: "",
      password_confirmation: "",
    };
    toast.success("Password aggiornata.");
  } catch (error) {
    passwordErrors.value = parseApiError(error).errors;
  } finally {
    savingPassword.value = false;
  }
}

async function logout() {
  await auth.logout();
  router.push("/login");
}

const pushSupported = ref(false);
const pushEnabled = ref(false);
const pushLoading = ref(false);

onMounted(async () => {
  pushSupported.value = isPushSupported();
  if (pushSupported.value) {
    pushEnabled.value = !!(await getPushSubscription());
  }
});

async function togglePush() {
  pushLoading.value = true;
  try {
    if (pushEnabled.value) {
      await disablePush();
      pushEnabled.value = false;
      toast.success("Notifiche push disattivate.");
    } else {
      await enablePush();
      pushEnabled.value = true;
      toast.success("Notifiche push attivate.");
    }
  } catch {
    toast.error(
      "Non è stato possibile attivare le notifiche push. Controlla i permessi del browser.",
    );
  } finally {
    pushLoading.value = false;
  }
}
</script>

<template>
  <div class="space-y-5 px-4 pt-6 pb-6">
    <div class="flex items-center gap-3">
      <Avatar :name="auth.user?.name" size="lg" />
      <div>
        <h1 class="text-lg font-bold text-slate-900">{{ auth.user?.name }}</h1>
        <p class="text-sm text-slate-500">{{ roleLabels[auth.user?.role] }}</p>
      </div>
    </div>

    <Card>
      <h2 class="mb-3 text-sm font-semibold text-slate-900">Dati personali</h2>
      <form class="space-y-3" @submit.prevent="saveProfile">
        <TextField
          v-model="profileForm.name"
          label="Nome"
          :error="profileErrors.name?.[0]"
        />
        <TextField
          v-model="profileForm.phone"
          label="Telefono"
          :error="profileErrors.phone?.[0]"
        />
        <TextField
          :model-value="auth.user?.email"
          label="Email"
          type="email"
          disabled
        />
        <Button type="submit" size="sm" :loading="savingProfile">Salva</Button>
      </form>
    </Card>

    <Card>
      <h2 class="mb-3 text-sm font-semibold text-slate-900">Cambia password</h2>
      <form class="space-y-3" @submit.prevent="savePassword">
        <TextField
          v-model="passwordForm.current_password"
          type="password"
          label="Password attuale"
          :error="passwordErrors.current_password?.[0]"
        />
        <TextField
          v-model="passwordForm.password"
          type="password"
          label="Nuova password"
          :error="passwordErrors.password?.[0]"
        />
        <TextField
          v-model="passwordForm.password_confirmation"
          type="password"
          label="Conferma nuova password"
        />
        <Button
          type="submit"
          size="sm"
          variant="secondary"
          :loading="savingPassword"
          >Aggiorna password</Button
        >
      </form>
    </Card>

    <Card v-if="pushSupported">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="text-sm font-semibold text-slate-900">Notifiche push</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            {{
              pushEnabled
                ? "Attive su questo dispositivo."
                : "Ricevi un avviso su questo dispositivo per segnalazioni, comunicazioni e assemblee."
            }}
          </p>
        </div>
        <Button
          size="sm"
          :variant="pushEnabled ? 'secondary' : 'primary'"
          :loading="pushLoading"
          @click="togglePush"
          >{{ pushEnabled ? "Disattiva" : "Attiva" }}</Button
        >
      </div>
    </Card>

    <Button variant="ghost" block @click="logout">Esci</Button>
  </div>
</template>
