<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";

import Avatar from "@/components/ui/Avatar.vue";
import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Modal from "@/components/ui/Modal.vue";
import SelectField from "@/components/ui/SelectField.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import TextField from "@/components/ui/TextField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";
import { useToastStore } from "@/stores/toast";

const route = useRoute();
const toast = useToastStore();
const condominiumId = route.params.id;

const condominium = ref(null);
const units = ref([]);
const buildings = ref([]);
const residents = ref([]);
const caretakers = ref([]);
const loading = ref(true);
const tab = ref("units");

const unitModalOpen = ref(false);
const unitForm = ref({
  code: "",
  floor: "",
  type: "apartment",
  building_id: "",
  surface_sqm: "",
  millesimi: "",
});
const unitErrors = ref({});
const savingUnit = ref(false);

const inviteModalOpen = ref(false);
const inviteForm = ref({
  name: "",
  email: "",
  phone: "",
  role: "condomino",
  unit_id: "",
  relationship: "owner",
});
const inviteErrors = ref({});
const sendingInvite = ref(false);
const invitationLink = ref("");

async function copyInvitationLink() {
  await navigator.clipboard.writeText(invitationLink.value);
  toast.success("Link copiato.");
}

async function load() {
  loading.value = true;
  const [condRes, unitsRes, buildingsRes, usersRes] = await Promise.all([
    api.get(`/api/condominiums/${condominiumId}`),
    api.get(`/api/condominiums/${condominiumId}/units`, {
      params: { per_page: 200 },
    }),
    api.get(`/api/condominiums/${condominiumId}/buildings`),
    api.get(`/api/condominiums/${condominiumId}/users`),
  ]);
  condominium.value = condRes.data.data;
  units.value = unitsRes.data.data;
  buildings.value = buildingsRes.data.data;
  residents.value = usersRes.data.data.residents;
  caretakers.value = usersRes.data.data.caretakers;
  loading.value = false;
}

onMounted(load);

async function createUnit() {
  savingUnit.value = true;
  unitErrors.value = {};
  try {
    await api.post(`/api/condominiums/${condominiumId}/units`, {
      ...unitForm.value,
      building_id: unitForm.value.building_id || null,
    });
    unitModalOpen.value = false;
    unitForm.value = {
      code: "",
      floor: "",
      type: "apartment",
      building_id: "",
      surface_sqm: "",
      millesimi: "",
    };
    toast.success("Unità creata.");
    await load();
  } catch (error) {
    unitErrors.value = parseApiError(error).errors;
  } finally {
    savingUnit.value = false;
  }
}

async function sendInvite() {
  sendingInvite.value = true;
  inviteErrors.value = {};
  try {
    const { data } = await api.post(
      `/api/condominiums/${condominiumId}/invitations`,
      inviteForm.value,
    );
    inviteModalOpen.value = false;
    inviteForm.value = {
      name: "",
      email: "",
      phone: "",
      role: "condomino",
      unit_id: "",
      relationship: "owner",
    };
    if (data.invitation_url) {
      // No email on file: hand the link to the admin to share themselves
      // (SMS, WhatsApp…) instead of the usual "invito inviato" toast.
      invitationLink.value = data.invitation_url;
    } else {
      toast.success("Invito inviato.");
    }
    await load();
  } catch (error) {
    inviteErrors.value = parseApiError(error).errors;
  } finally {
    sendingInvite.value = false;
  }
}

const groupedUnits = computed(() => {
  const groups = new Map();
  for (const u of units.value) {
    const key = u.floor || "—";
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push(u);
  }
  return Array.from(groups, ([floor, items]) => ({ floor, items }));
});

function floorLabel(floor) {
  if (floor === "T") return "Piano terra";
  if (floor === "—") return "Piano non specificato";
  return `Piano ${floor}`;
}

const unitTypeOptions = [
  { value: "apartment", label: "Appartamento" },
  { value: "garage", label: "Box auto" },
  { value: "cellar", label: "Cantina" },
  { value: "shop", label: "Negozio" },
  { value: "other", label: "Altro" },
];
</script>

<template>
  <div class="space-y-4">
    <Skeleton v-if="loading" :rows="4" />

    <template v-else-if="condominium">
      <div>
        <h1 class="text-xl font-bold text-slate-900">{{ condominium.name }}</h1>
        <p class="text-sm text-slate-500">
          {{ condominium.address }}, {{ condominium.city }}
        </p>
      </div>

      <div class="flex gap-2 border-b border-slate-200">
        <button
          type="button"
          class="border-b-2 px-3 py-2 text-sm font-semibold"
          :class="
            tab === 'units'
              ? 'border-primary-600 text-primary-700'
              : 'border-transparent text-slate-500'
          "
          @click="tab = 'units'"
        >
          Unità ({{ units.length }})
        </button>
        <button
          type="button"
          class="border-b-2 px-3 py-2 text-sm font-semibold"
          :class="
            tab === 'people'
              ? 'border-primary-600 text-primary-700'
              : 'border-transparent text-slate-500'
          "
          @click="tab = 'people'"
        >
          Persone ({{ residents.length + caretakers.length }})
        </button>
      </div>

      <div v-if="tab === 'units'" class="space-y-3">
        <div class="flex justify-end">
          <Button size="sm" @click="unitModalOpen = true">+ Nuova unità</Button>
        </div>
        <EmptyState
          v-if="units.length === 0"
          icon="🏠"
          title="Nessuna unità"
          description="Aggiungi la prima unità immobiliare."
        />
        <div v-else class="space-y-5">
          <div v-for="group in groupedUnits" :key="group.floor">
            <h3
              class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase"
            >
              {{ floorLabel(group.floor) }} ({{ group.items.length }})
            </h3>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
              <Card v-for="u in group.items" :key="u.id">
                <p class="font-semibold text-slate-900">{{ u.code }}</p>
                <p class="text-xs text-slate-500">
                  {{ u.type_label }}
                  <span v-if="u.building"> · {{ u.building.name }}</span>
                  <span v-if="u.millesimi"> · {{ u.millesimi }}‰</span>
                </p>
                <p class="mt-2 text-xs text-slate-600">
                  <span v-if="u.residents?.length">{{
                    u.residents.map((r) => r.name).join(", ")
                  }}</span>
                  <span v-else class="text-slate-400"
                    >Nessun residente collegato</span
                  >
                </p>
              </Card>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="space-y-4">
        <div class="flex justify-end">
          <Button size="sm" @click="inviteModalOpen = true"
            >+ Invita persona</Button
          >
        </div>

        <Card>
          <h2 class="mb-2 text-sm font-semibold text-slate-900">Custodi</h2>
          <div v-if="caretakers.length === 0" class="text-sm text-slate-400">
            Nessun custode assegnato.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="c in caretakers"
              :key="c.id"
              class="flex items-center gap-3"
            >
              <Avatar :name="c.name" size="sm" />
              <div>
                <p class="text-sm font-medium text-slate-900">{{ c.name }}</p>
                <p class="text-xs text-slate-500">{{ c.email }}</p>
              </div>
            </div>
          </div>
        </Card>

        <Card>
          <h2 class="mb-2 text-sm font-semibold text-slate-900">Condòmini</h2>
          <div v-if="residents.length === 0" class="text-sm text-slate-400">
            Nessun condomino registrato.
          </div>
          <div v-else class="divide-y divide-slate-100">
            <div
              v-for="r in residents"
              :key="r.id"
              class="flex items-center gap-3 py-2"
            >
              <Avatar :name="r.name" size="sm" />
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900">
                  {{ r.name }}
                </p>
                <p class="truncate text-xs text-slate-500">
                  {{ r.email }} · {{ r.units?.map((u) => u.code).join(", ") }}
                </p>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <Modal
        :open="unitModalOpen"
        title="Nuova unità"
        @close="unitModalOpen = false"
      >
        <form class="space-y-3" @submit.prevent="createUnit">
          <TextField
            v-model="unitForm.code"
            label="Codice unità"
            placeholder="Es. A101"
            required
            :error="unitErrors.code?.[0]"
          />
          <SelectField
            v-model="unitForm.type"
            label="Tipo"
            :options="unitTypeOptions"
            :error="unitErrors.type?.[0]"
          />
          <SelectField
            v-if="buildings.length"
            v-model="unitForm.building_id"
            label="Scala/edificio (opzionale)"
            :options="buildings.map((b) => ({ value: b.id, label: b.name }))"
          />
          <div class="grid grid-cols-2 gap-3">
            <TextField v-model="unitForm.floor" label="Piano" />
            <TextField
              v-model="unitForm.surface_sqm"
              label="Superficie (mq)"
              type="number"
            />
          </div>
          <TextField
            v-model="unitForm.millesimi"
            label="Millesimi (opzionale, su 1000)"
            type="number"
            :error="unitErrors.millesimi?.[0]"
          />
          <Button type="submit" block :loading="savingUnit">Crea unità</Button>
        </form>
      </Modal>

      <Modal
        :open="inviteModalOpen"
        title="Invita persona"
        @close="inviteModalOpen = false"
      >
        <form class="space-y-3" @submit.prevent="sendInvite">
          <TextField
            v-model="inviteForm.name"
            label="Nome e cognome"
            required
            :error="inviteErrors.name?.[0]"
          />
          <TextField
            v-model="inviteForm.email"
            type="email"
            label="Email (opzionale se si indica il cellulare)"
            :error="inviteErrors.email?.[0]"
          />
          <TextField
            v-model="inviteForm.phone"
            label="Cellulare (opzionale se si indica l'email)"
            :error="inviteErrors.phone?.[0]"
          />
          <SelectField
            v-model="inviteForm.role"
            label="Ruolo"
            :options="[
              { value: 'condomino', label: 'Condomino' },
              { value: 'caretaker', label: 'Custode' },
            ]"
          />
          <template v-if="inviteForm.role === 'condomino'">
            <SelectField
              v-model="inviteForm.unit_id"
              label="Unità immobiliare"
              :options="units.map((u) => ({ value: u.id, label: u.code }))"
              :error="inviteErrors.unit_id?.[0]"
            />
            <SelectField
              v-model="inviteForm.relationship"
              label="Rapporto con l'unità"
              :options="[
                { value: 'owner', label: 'Proprietario' },
                { value: 'tenant', label: 'Inquilino' },
              ]"
            />
          </template>
          <Button type="submit" block :loading="sendingInvite"
            >Invia invito</Button
          >
        </form>
      </Modal>

      <Modal
        :open="!!invitationLink"
        title="Invito creato"
        @close="invitationLink = ''"
      >
        <p class="mb-3 text-sm text-slate-600">
          Non è stata indicata un'email: condividi questo link con la persona
          invitata (SMS, WhatsApp…) per farle impostare la password.
        </p>
        <div class="flex gap-2">
          <input
            readonly
            :value="invitationLink"
            class="w-full flex-1 rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700"
            @click="$event.target.select()"
          />
          <Button size="sm" @click="copyInvitationLink">Copia</Button>
        </div>
      </Modal>
    </template>
  </div>
</template>
