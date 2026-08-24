<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";

import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import TextareaField from "@/components/ui/TextareaField.vue";
import PriorityBadge from "@/components/tickets/PriorityBadge.vue";
import api from "@/lib/api";
import { useToastStore } from "@/stores/toast";

const route = useRoute();
const toast = useToastStore();

const ticket = ref(null);
const loading = ref(true);
const newComment = ref("");
const sendingComment = ref(false);

const steps = [
  { key: "new", label: "Segnalazione ricevuta" },
  { key: "taken_in_charge", label: "Presa in carico" },
  { key: "in_progress", label: "In lavorazione" },
  { key: "resolved", label: "Risolta" },
];

const currentStepIndex = computed(() => {
  if (!ticket.value) return -1;
  if (ticket.value.status === "closed") return steps.length - 1;
  if (ticket.value.status === "waiting_supplier") return 2;
  return steps.findIndex((s) => s.key === ticket.value.status);
});

async function load() {
  loading.value = true;
  const { data } = await api.get(`/api/tickets/${route.params.id}`);
  ticket.value = data.data;
  loading.value = false;
}

onMounted(load);

async function sendComment() {
  if (!newComment.value.trim()) return;
  sendingComment.value = true;
  try {
    await api.post(`/api/tickets/${route.params.id}/comments`, {
      body: newComment.value,
    });
    newComment.value = "";
    await load();
    toast.success("Commento inviato.");
  } finally {
    sendingComment.value = false;
  }
}
</script>

<template>
  <div class="space-y-5 px-4 pt-6 pb-6">
    <Skeleton v-if="loading" :rows="4" />

    <template v-else-if="ticket">
      <div>
        <div class="mb-2 flex items-center gap-2">
          <PriorityBadge
            :priority="ticket.priority"
            :label="ticket.priority_label"
          />
          <span class="text-xs text-slate-400">#{{ ticket.id }}</span>
        </div>
        <h1 class="text-xl font-bold text-slate-900">{{ ticket.title }}</h1>
        <p class="mt-1 text-sm text-slate-500">
          {{ ticket.category?.name }} · {{ ticket.unit?.code }}
        </p>
      </div>

      <!-- Status timeline -->
      <Card>
        <div
          v-if="ticket.status === 'closed'"
          class="mb-3 text-sm font-semibold text-slate-500"
        >
          Segnalazione chiusa
        </div>
        <ol class="space-y-4">
          <li
            v-for="(step, i) in steps"
            :key="step.key"
            class="flex items-start gap-3"
          >
            <div class="flex flex-col items-center">
              <span
                class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold"
                :class="
                  i <= currentStepIndex
                    ? 'bg-emerald-500 text-white'
                    : 'bg-slate-200 text-slate-400'
                "
              >
                <span v-if="i <= currentStepIndex">✓</span>
              </span>
              <span
                v-if="i < steps.length - 1"
                class="mt-1 h-6 w-0.5"
                :class="
                  i < currentStepIndex ? 'bg-emerald-500' : 'bg-slate-200'
                "
              />
            </div>
            <span
              class="pt-0.5 text-sm"
              :class="
                i <= currentStepIndex
                  ? 'font-semibold text-slate-900'
                  : 'text-slate-400'
              "
            >
              {{ step.label }}
            </span>
          </li>
        </ol>
      </Card>

      <Card>
        <h2 class="mb-2 text-sm font-semibold text-slate-900">Descrizione</h2>
        <p class="whitespace-pre-line text-sm text-slate-600">
          {{ ticket.description }}
        </p>
        <p v-if="ticket.location" class="mt-2 text-xs text-slate-500">
          📍 {{ ticket.location }}
        </p>
      </Card>

      <Card v-if="ticket.attachments?.length">
        <h2 class="mb-2 text-sm font-semibold text-slate-900">Foto allegate</h2>
        <div class="grid grid-cols-3 gap-2">
          <a
            v-for="att in ticket.attachments"
            :key="att.id"
            :href="att.download_url"
            target="_blank"
            rel="noopener"
            class="flex aspect-square items-center justify-center rounded-lg bg-slate-100 text-2xl"
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
            class="rounded-xl bg-slate-50 p-3"
          >
            <p class="text-xs font-semibold text-slate-700">
              {{ comment.user?.name }}
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
          <Button size="sm" :loading="sendingComment" @click="sendComment"
            >Invia</Button
          >
        </div>
      </Card>
    </template>
  </div>
</template>
