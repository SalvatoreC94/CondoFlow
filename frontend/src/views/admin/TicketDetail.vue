<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";

import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
import SelectField from "@/components/ui/SelectField.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import TextareaField from "@/components/ui/TextareaField.vue";
import PriorityBadge from "@/components/tickets/PriorityBadge.vue";
import StatusBadge from "@/components/tickets/StatusBadge.vue";
import api from "@/lib/api";
import { useAuthStore } from "@/stores/auth";
import { useToastStore } from "@/stores/toast";

const route = useRoute();
const auth = useAuthStore();
const toast = useToastStore();

const ticket = ref(null);
const loading = ref(true);
const caretakers = ref([]);
const suppliers = ref([]);

const newComment = ref("");
const isInternalComment = ref(false);
const sendingComment = ref(false);
const changingStatus = ref(false);
const savingAssignment = ref(false);
const statusNote = ref("");

const isAdmin = computed(() => auth.isAdministrator);

async function load() {
  loading.value = true;
  const { data } = await api.get(`/api/tickets/${route.params.id}`);
  ticket.value = data.data;
  loading.value = false;

  if (isAdmin.value && ticket.value) {
    const [usersRes, suppliersRes] = await Promise.all([
      api.get(`/api/condominiums/${ticket.value.condominium_id}/users`),
      api.get("/api/suppliers", {
        params: { condominium_id: ticket.value.condominium_id, per_page: 100 },
      }),
    ]);
    caretakers.value = usersRes.data.data.caretakers;
    suppliers.value = suppliersRes.data.data;
  }
}

onMounted(load);

async function transition(status) {
  changingStatus.value = true;
  try {
    await api.patch(`/api/tickets/${route.params.id}/status`, {
      status,
      note: statusNote.value || null,
    });
    statusNote.value = "";
    await load();
    toast.success("Stato aggiornato.");
  } catch {
    toast.error("Impossibile aggiornare lo stato.");
  } finally {
    changingStatus.value = false;
  }
}

async function sendComment() {
  if (!newComment.value.trim()) return;
  sendingComment.value = true;
  try {
    await api.post(`/api/tickets/${route.params.id}/comments`, {
      body: newComment.value,
      is_internal: isInternalComment.value,
    });
    newComment.value = "";
    await load();
  } finally {
    sendingComment.value = false;
  }
}

async function updateAssignment(field, value) {
  savingAssignment.value = true;
  try {
    await api.put(`/api/tickets/${route.params.id}`, {
      [field]: value || null,
    });
    await load();
    toast.success("Segnalazione aggiornata.");
  } finally {
    savingAssignment.value = false;
  }
}

function onFileChange(event) {
  const files = Array.from(event.target.files || []);
  uploadFiles(files);
}

async function uploadFiles(files) {
  for (const file of files) {
    const fd = new FormData();
    fd.append("file", file);
    await api.post(`/api/tickets/${route.params.id}/attachments`, fd, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  }
  await load();
}
</script>

<template>
  <div class="space-y-4">
    <Skeleton v-if="loading" :rows="4" />

    <template v-else-if="ticket">
      <div
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
      >
        <div>
          <div class="mb-1 flex items-center gap-2">
            <PriorityBadge
              :priority="ticket.priority"
              :label="ticket.priority_label"
            />
            <StatusBadge :status="ticket.status" :label="ticket.status_label" />
            <span class="text-xs text-slate-400">#{{ ticket.id }}</span>
          </div>
          <h1 class="text-xl font-bold text-slate-900">{{ ticket.title }}</h1>
          <p class="mt-1 text-sm text-slate-500">
            {{ ticket.category?.name }} · {{ ticket.unit?.code }} · segnalato da
            {{ ticket.reporter?.name }}
          </p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
          <Card>
            <h2 class="mb-2 text-sm font-semibold text-slate-900">
              Descrizione
            </h2>
            <p class="whitespace-pre-line text-sm text-slate-600">
              {{ ticket.description }}
            </p>
            <p v-if="ticket.location" class="mt-2 text-xs text-slate-500">
              📍 {{ ticket.location }}
            </p>
          </Card>

          <Card
            v-if="ticket.attachments?.length || isAdmin || auth.isCaretaker"
          >
            <div class="mb-2 flex items-center justify-between">
              <h2 class="text-sm font-semibold text-slate-900">Allegati</h2>
              <label
                class="cursor-pointer text-xs font-semibold text-primary-600"
              >
                + Carica foto
                <input
                  type="file"
                  accept="image/*"
                  multiple
                  class="hidden"
                  @change="onFileChange"
                />
              </label>
            </div>
            <div
              v-if="!ticket.attachments?.length"
              class="text-sm text-slate-400"
            >
              Nessun allegato.
            </div>
            <div v-else class="grid grid-cols-4 gap-2 sm:grid-cols-6">
              <a
                v-for="att in ticket.attachments"
                :key="att.id"
                :href="att.download_url"
                target="_blank"
                rel="noopener"
                class="flex aspect-square items-center justify-center rounded-lg bg-slate-100 text-xl"
              >
                🖼️
              </a>
            </div>
          </Card>

          <Card>
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Commenti</h2>
            <div v-if="!ticket.comments?.length" class="text-sm text-slate-400">
              Nessun commento.
            </div>
            <div v-else class="space-y-3">
              <div
                v-for="comment in ticket.comments"
                :key="comment.id"
                class="rounded-xl p-3"
                :class="comment.is_internal ? 'bg-amber-50' : 'bg-slate-50'"
              >
                <p
                  class="flex items-center gap-2 text-xs font-semibold text-slate-700"
                >
                  {{ comment.user?.name }}
                  <span
                    v-if="comment.is_internal"
                    class="rounded-full bg-amber-200 px-1.5 py-0.5 text-[10px] text-amber-800"
                    >Nota interna</span
                  >
                </p>
                <p class="mt-0.5 text-sm text-slate-600">{{ comment.body }}</p>
              </div>
            </div>

            <div class="mt-4 space-y-2">
              <TextareaField
                v-model="newComment"
                placeholder="Scrivi un commento…"
                :rows="2"
              />
              <div class="flex items-center justify-between">
                <label
                  v-if="isAdmin || auth.isCaretaker"
                  class="flex items-center gap-2 text-xs text-slate-500"
                >
                  <input
                    v-model="isInternalComment"
                    type="checkbox"
                    class="rounded border-slate-300"
                  />
                  Nota interna (non visibile al condomino)
                </label>
                <span v-else />
                <Button size="sm" :loading="sendingComment" @click="sendComment"
                  >Invia</Button
                >
              </div>
            </div>
          </Card>

          <Card>
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Storico</h2>
            <ol class="space-y-3">
              <li
                v-for="h in ticket.status_history"
                :key="h.id"
                class="flex items-start gap-3 text-sm"
              >
                <span
                  class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-primary-500"
                />
                <div>
                  <p class="font-medium text-slate-800">
                    {{ h.to_status_label }}
                  </p>
                  <p class="text-xs text-slate-500">
                    {{ new Date(h.created_at).toLocaleString("it-IT") }} ·
                    {{ h.changed_by?.name }}
                  </p>
                  <p v-if="h.note" class="mt-0.5 text-xs text-slate-500">
                    "{{ h.note }}"
                  </p>
                </div>
              </li>
            </ol>
          </Card>
        </div>

        <div class="space-y-4">
          <Card>
            <h2 class="mb-3 text-sm font-semibold text-slate-900">
              Azioni rapide
            </h2>
            <div class="flex flex-wrap gap-2">
              <Button
                v-for="next in ticket.allowed_next_statuses"
                :key="next"
                size="sm"
                :variant="next === 'closed' ? 'secondary' : 'primary'"
                :loading="changingStatus"
                @click="transition(next)"
              >
                {{
                  {
                    taken_in_charge: "Prendi in carico",
                    in_progress: "In lavorazione",
                    waiting_supplier: "Attesa fornitore",
                    resolved: "Risolto",
                    closed: "Chiudi",
                  }[next]
                }}
              </Button>
            </div>
            <TextareaField
              v-model="statusNote"
              placeholder="Nota sul cambio stato (opzionale)"
              :rows="2"
              class="mt-3"
            />
          </Card>

          <Card v-if="isAdmin">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">
              Assegnazione
            </h2>
            <div class="space-y-3">
              <SelectField
                :model-value="ticket.assigned_caretaker?.id ?? ''"
                label="Custode"
                placeholder="Nessuno"
                :options="
                  caretakers.map((c) => ({ value: c.id, label: c.name }))
                "
                @update:model-value="
                  updateAssignment('assigned_caretaker_id', $event)
                "
              />
              <SelectField
                :model-value="ticket.supplier?.id ?? ''"
                label="Fornitore"
                placeholder="Nessuno"
                :options="
                  suppliers.map((s) => ({ value: s.id, label: s.name }))
                "
                @update:model-value="updateAssignment('supplier_id', $event)"
              />
            </div>
          </Card>
        </div>
      </div>
    </template>
  </div>
</template>
