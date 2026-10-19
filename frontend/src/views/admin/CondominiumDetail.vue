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

function emptyUnitForm() {
  return {
    code: "",
    floor: "",
    type: "apartment",
    building_name: "",
    surface_sqm: "",
    millesimi: "",
  };
}

const unitModalOpen = ref(false);
const unitForm = ref(emptyUnitForm());
const unitErrors = ref({});
const savingUnit = ref(false);
const editingUnit = ref(null);
const deletingUnit = ref(false);

const addResidentForm = ref({ user_id: "", relationship: "owner", is_primary: false });
const addingResident = ref(false);
const removingResidentId = ref(null);

const availableResidentsForUnit = computed(() => {
  if (!editingUnit.value) return [];
  const attachedIds = new Set((editingUnit.value.residents ?? []).map((r) => r.id));
  return residents.value.filter((r) => !attachedIds.has(r.id));
});

const brandColor = ref("#4f46e5");
const savingBrandColor = ref(false);
const uploadingLogo = ref(false);
const removingLogo = ref(false);
const logoInput = ref(null);
const logoCacheBust = ref(0);

async function saveBrandColor() {
  savingBrandColor.value = true;
  try {
    const { data } = await api.put(`/api/condominiums/${condominiumId}`, {
      brand_color: brandColor.value,
    });
    condominium.value = data.data;
    toast.success("Colore aggiornato.");
  } catch {
    toast.error("Impossibile aggiornare il colore.");
  } finally {
    savingBrandColor.value = false;
  }
}

async function uploadLogo(event) {
  const file = event.target.files[0];
  if (!file) return;
  uploadingLogo.value = true;
  try {
    const fd = new FormData();
    fd.append("logo", file);
    const { data } = await api.post(
      `/api/condominiums/${condominiumId}/logo`,
      fd,
      { headers: { "Content-Type": "multipart/form-data" } },
    );
    condominium.value = data.data;
    logoCacheBust.value++;
    toast.success("Logo aggiornato.");
  } catch (error) {
    toast.error(parseApiError(error).message ?? "Impossibile caricare il logo.");
  } finally {
    uploadingLogo.value = false;
    event.target.value = "";
  }
}

async function removeLogo() {
  removingLogo.value = true;
  try {
    const { data } = await api.delete(
      `/api/condominiums/${condominiumId}/logo`,
    );
    condominium.value = data.data;
    toast.success("Logo rimosso.");
  } catch {
    toast.error("Impossibile rimuovere il logo.");
  } finally {
    removingLogo.value = false;
  }
}

const logoPreviewUrl = computed(() => {
  if (!condominium.value?.has_logo) return null;
  return `${condominium.value.logo_url}?v=${logoCacheBust.value}`;
});

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
  brandColor.value = condominium.value.brand_color || "#4f46e5";
  units.value = unitsRes.data.data;
  buildings.value = buildingsRes.data.data;
  residents.value = usersRes.data.data.residents;
  caretakers.value = usersRes.data.data.caretakers;
  loading.value = false;
}

onMounted(load);

function openUnitModal(unit = null) {
  editingUnit.value = unit;
  unitForm.value = unit
    ? {
        code: unit.code,
        floor: unit.floor ?? "",
        type: unit.type,
        building_name: unit.building?.name ?? "",
        surface_sqm: unit.surface_sqm ?? "",
        millesimi: unit.millesimi ?? "",
      }
    : emptyUnitForm();
  addResidentForm.value = { user_id: "", relationship: "owner", is_primary: false };
  unitErrors.value = {};
  unitModalOpen.value = true;
}

async function saveUnit() {
  savingUnit.value = true;
  unitErrors.value = {};
  try {
    if (editingUnit.value) {
      await api.put(`/api/units/${editingUnit.value.id}`, unitForm.value);
      toast.success("Unità aggiornata.");
    } else {
      await api.post(`/api/condominiums/${condominiumId}/units`, unitForm.value);
      toast.success("Unità creata.");
      unitModalOpen.value = false;
    }
    await load();
    if (editingUnit.value) {
      editingUnit.value = units.value.find((u) => u.id === editingUnit.value.id) ?? null;
    }
  } catch (error) {
    unitErrors.value = parseApiError(error).errors;
  } finally {
    savingUnit.value = false;
  }
}

async function deleteUnit() {
  if (!editingUnit.value) return;
  if (!window.confirm(`Eliminare l'unità ${editingUnit.value.code}?`)) return;
  deletingUnit.value = true;
  try {
    await api.delete(`/api/units/${editingUnit.value.id}`);
    unitModalOpen.value = false;
    toast.success("Unità eliminata.");
    await load();
  } catch {
    toast.error("Impossibile eliminare l'unità.");
  } finally {
    deletingUnit.value = false;
  }
}

async function attachExistingResident() {
  if (!editingUnit.value || !addResidentForm.value.user_id) return;
  addingResident.value = true;
  try {
    await api.post(`/api/units/${editingUnit.value.id}/residents`, addResidentForm.value);
    addResidentForm.value = { user_id: "", relationship: "owner", is_primary: false };
    toast.success("Residente collegato.");
    await load();
    editingUnit.value = units.value.find((u) => u.id === editingUnit.value.id) ?? null;
  } catch (error) {
    toast.error(parseApiError(error).message ?? "Impossibile collegare il residente.");
  } finally {
    addingResident.value = false;
  }
}

async function removeResident(userId) {
  if (!editingUnit.value) return;
  if (!window.confirm("Scollegare questo residente dall'unità?")) return;
  removingResidentId.value = userId;
  try {
    await api.delete(`/api/units/${editingUnit.value.id}/residents/${userId}`);
    toast.success("Residente scollegato.");
    await load();
    editingUnit.value = units.value.find((u) => u.id === editingUnit.value.id) ?? null;
  } catch {
    toast.error("Impossibile scollegare il residente.");
  } finally {
    removingResidentId.value = null;
  }
}

function openInviteModal() {
  inviteErrors.value = {};
  inviteForm.value = {
    name: "",
    email: "",
    phone: "",
    role: "condomino",
    unit_id: "",
    relationship: "owner",
  };
  inviteModalOpen.value = true;
}

function inviteForThisUnit() {
  unitModalOpen.value = false;
  openInviteModal();
  inviteForm.value.unit_id = editingUnit.value.id;
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
  if (floor === "—") return "Senza piano specificato";
  return floor;
}

const unitTypeOptions = [
  { value: "apartment", label: "Appartamento" },
  { value: "garage", label: "Box auto" },
  { value: "cellar", label: "Cantina" },
  { value: "shop", label: "Negozio" },
  { value: "other", label: "Altro" },
];

const millesimiTotal = computed(() => {
  const sum = units.value.reduce((acc, u) => acc + (u.millesimi ? Number(u.millesimi) : 0), 0);
  return Math.round(sum * 1000) / 1000;
});
const unitsWithoutMillesimi = computed(
  () => units.value.filter((u) => u.millesimi === null || u.millesimi === undefined).length,
);

function relationshipLabel(relationship) {
  return relationship === "tenant" ? "inquilino" : "proprietario";
}
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
        <button
          type="button"
          class="border-b-2 px-3 py-2 text-sm font-semibold"
          :class="
            tab === 'branding'
              ? 'border-primary-600 text-primary-700'
              : 'border-transparent text-slate-500'
          "
          @click="tab = 'branding'"
        >
          Branding
        </button>
      </div>

      <div v-if="tab === 'units'" class="space-y-3">
        <div class="flex items-center justify-end gap-2">
          <p v-if="units.length" class="mr-auto text-xs text-slate-500">
            Millesimi assegnati: {{ millesimiTotal }}/1000
            <span v-if="unitsWithoutMillesimi" class="text-amber-600">
              · {{ unitsWithoutMillesimi }} unità senza millesimi</span
            >
          </p>
          <Button size="sm" @click="openUnitModal()">+ Nuova unità</Button>
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
              <Card
                v-for="u in group.items"
                :key="u.id"
                class="cursor-pointer transition hover:border-primary-300 hover:shadow-md"
                @click="openUnitModal(u)"
              >
                <p class="font-semibold text-slate-900">{{ u.code }}</p>
                <p class="text-xs text-slate-500">
                  {{ u.type_label }}
                  <span v-if="u.building"> · {{ u.building.name }}</span>
                  <span v-if="u.millesimi"> · {{ u.millesimi }}‰</span>
                </p>
                <p class="mt-2 text-xs text-slate-600">
                  <span v-if="u.residents?.length">{{
                    u.residents
                      .map(
                        (r) =>
                          r.name +
                          (r.relationship ? ` (${relationshipLabel(r.relationship)})` : ""),
                      )
                      .join(", ")
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

      <div v-else-if="tab === 'people'" class="space-y-4">
        <div class="flex justify-end">
          <Button size="sm" @click="openInviteModal"
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
                  {{ r.email
                  }}<span v-if="r.units?.length">
                    ·
                    {{
                      r.units
                        .map(
                          (u) =>
                            u.code +
                            (u.relationship ? ` (${relationshipLabel(u.relationship)})` : ""),
                        )
                        .join(", ")
                    }}</span
                  >
                </p>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <div v-else-if="tab === 'branding'" class="space-y-4">
        <Card>
          <h2 class="mb-1 text-sm font-semibold text-slate-900">Logo</h2>
          <p class="mb-3 text-xs text-slate-500">
            Mostrato ai condòmini e ai custodi di questo condominio.
            Formati supportati: JPG, PNG, WebP — max 2 MB.
          </p>
          <div class="flex items-center gap-4">
            <div
              class="flex h-16 w-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
            >
              <img
                v-if="logoPreviewUrl"
                :src="logoPreviewUrl"
                alt="Logo del condominio"
                class="h-full w-full object-contain"
              />
              <span v-else class="text-2xl">🏢</span>
            </div>
            <div class="flex gap-2">
              <Button
                size="sm"
                variant="secondary"
                :loading="uploadingLogo"
                @click="logoInput.click()"
                >{{
                  condominium.has_logo ? "Cambia logo" : "Carica logo"
                }}</Button
              >
              <Button
                v-if="condominium.has_logo"
                size="sm"
                variant="secondary"
                :loading="removingLogo"
                @click="removeLogo"
                >Rimuovi</Button
              >
            </div>
            <input
              ref="logoInput"
              type="file"
              accept="image/png,image/jpeg,image/webp"
              class="hidden"
              @change="uploadLogo"
            />
          </div>
        </Card>

        <Card>
          <h2 class="mb-1 text-sm font-semibold text-slate-900">
            Colore identificativo
          </h2>
          <p class="mb-3 text-xs text-slate-500">
            Usato come accento nell'app dei condòmini per riconoscere subito
            il proprio condominio.
          </p>
          <div class="flex items-center gap-3">
            <input
              v-model="brandColor"
              type="color"
              class="h-10 w-14 cursor-pointer rounded-lg border border-slate-200"
            />
            <span class="font-mono text-sm text-slate-600">{{
              brandColor
            }}</span>
            <Button
              size="sm"
              :loading="savingBrandColor"
              @click="saveBrandColor"
              >Salva colore</Button
            >
          </div>
        </Card>
      </div>

      <Modal
        :open="unitModalOpen"
        :title="editingUnit ? `Unità ${editingUnit.code}` : 'Nuova unità'"
        @close="unitModalOpen = false"
      >
        <form class="space-y-3" @submit.prevent="saveUnit">
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
          <TextField
            v-model="unitForm.building_name"
            label="Scala/edificio (opzionale)"
            placeholder="Es. Edificio A, Scala 2…"
            list="buildings-datalist"
          />
          <datalist id="buildings-datalist">
            <option v-for="b in buildings" :key="b.id" :value="b.name" />
          </datalist>
          <div class="grid grid-cols-2 gap-3">
            <TextField
              v-model="unitForm.floor"
              label="Piano"
              placeholder="Es. T, 1, Rialzato…"
            />
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
          <div class="flex gap-2">
            <Button type="submit" block :loading="savingUnit">{{
              editingUnit ? "Salva modifiche" : "Crea unità"
            }}</Button>
            <Button
              v-if="editingUnit"
              type="button"
              variant="danger"
              :loading="deletingUnit"
              @click="deleteUnit"
              >Elimina</Button
            >
          </div>
        </form>

        <div v-if="editingUnit" class="mt-5 border-t border-slate-200 pt-4">
          <h3 class="mb-2 text-sm font-semibold text-slate-900">Residenti</h3>
          <div
            v-if="!editingUnit.residents?.length"
            class="mb-3 text-sm text-slate-400"
          >
            Nessun residente collegato.
          </div>
          <div v-else class="mb-3 space-y-2">
            <div
              v-for="r in editingUnit.residents"
              :key="r.id"
              class="flex items-center justify-between gap-2"
            >
              <div class="min-w-0">
                <p class="truncate text-sm text-slate-900">{{ r.name }}</p>
                <p class="text-xs text-slate-500">
                  {{ relationshipLabel(r.relationship) }}
                  <span v-if="r.is_primary">· principale</span>
                </p>
              </div>
              <Button
                size="sm"
                variant="ghost"
                :loading="removingResidentId === r.id"
                @click="removeResident(r.id)"
                >Rimuovi</Button
              >
            </div>
          </div>

          <div v-if="availableResidentsForUnit.length" class="flex items-end gap-2">
            <SelectField
              v-model="addResidentForm.user_id"
              label="Collega persona già registrata"
              class="flex-1"
              :options="availableResidentsForUnit.map((r) => ({ value: r.id, label: r.name }))"
            />
            <SelectField
              v-model="addResidentForm.relationship"
              :options="[
                { value: 'owner', label: 'Proprietario' },
                { value: 'tenant', label: 'Inquilino' },
              ]"
            />
            <Button
              type="button"
              size="sm"
              :loading="addingResident"
              @click="attachExistingResident"
              >Collega</Button
            >
          </div>
          <button
            type="button"
            class="mt-2 text-xs font-medium text-primary-700 hover:underline"
            @click="inviteForThisUnit"
          >
            + Invita una persona nuova per questa unità
          </button>
        </div>
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
