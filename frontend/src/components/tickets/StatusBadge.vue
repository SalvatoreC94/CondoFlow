<script setup>
import { computed } from "vue";

const props = defineProps({
  status: { type: String, required: true },
  label: { type: String, default: null },
});

const styles = {
  new: "bg-slate-100 text-slate-700",
  taken_in_charge: "bg-blue-100 text-blue-700",
  in_progress: "bg-amber-100 text-amber-800",
  waiting_supplier: "bg-purple-100 text-purple-700",
  resolved: "bg-emerald-100 text-emerald-700",
  closed: "bg-slate-200 text-slate-600",
};

const classes = computed(() => styles[props.status] || styles.new);
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
    :class="classes"
  >
    {{ label || status }}
  </span>
</template>
