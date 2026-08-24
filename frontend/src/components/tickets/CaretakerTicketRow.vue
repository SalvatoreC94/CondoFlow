<script setup>
import { computed } from "vue";
import { RouterLink } from "vue-router";

import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
import PriorityBadge from "@/components/tickets/PriorityBadge.vue";
import StatusBadge from "@/components/tickets/StatusBadge.vue";

const props = defineProps({
  ticket: { type: Object, required: true },
  acting: { type: [Number, String, null], default: null },
});
defineEmits(["act"]);

const actionLabels = {
  taken_in_charge: "Prendi in carico",
  in_progress: "In lavorazione",
  resolved: "Risolto",
};

const nextAction = computed(() => {
  const order = ["taken_in_charge", "in_progress", "resolved"];
  return order.find((s) => props.ticket.allowed_next_statuses.includes(s));
});
</script>

<template>
  <Card class="flex items-center gap-3">
    <RouterLink
      :to="`/custode/segnalazioni/${ticket.id}`"
      class="min-w-0 flex-1"
    >
      <p class="truncate text-sm font-semibold text-slate-900">
        {{ ticket.title }}
      </p>
      <p class="truncate text-xs text-slate-500">
        {{ ticket.unit?.code }} · {{ ticket.category?.name }}
      </p>
      <div class="mt-1.5 flex items-center gap-2">
        <PriorityBadge
          :priority="ticket.priority"
          :label="ticket.priority_label"
        />
        <StatusBadge :status="ticket.status" :label="ticket.status_label" />
      </div>
    </RouterLink>
    <Button
      v-if="nextAction"
      size="sm"
      variant="secondary"
      :loading="acting === ticket.id"
      @click="$emit('act', ticket, nextAction)"
    >
      {{ actionLabels[nextAction] }}
    </Button>
  </Card>
</template>
