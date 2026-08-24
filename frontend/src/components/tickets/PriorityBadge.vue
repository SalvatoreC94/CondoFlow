<script setup>
import { computed } from "vue";

const props = defineProps({
  priority: { type: String, required: true },
  label: { type: String, default: null },
});

const styles = {
  low: "bg-slate-100 text-slate-600",
  medium: "bg-blue-50 text-blue-700",
  high: "bg-orange-100 text-orange-700",
  urgent: "bg-red-100 text-red-700",
};

const classes = computed(() => styles[props.priority] || styles.medium);
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"
    :class="classes"
  >
    <span
      v-if="priority === 'urgent'"
      class="h-1.5 w-1.5 rounded-full bg-red-600"
    />
    {{ label || priority }}
  </span>
</template>
