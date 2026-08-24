<script setup>
defineProps({
  modelValue: { type: [String, Number], default: "" },
  label: { type: String, default: "" },
  options: { type: Array, default: () => [] }, // [{ value, label }]
  error: { type: String, default: "" },
  placeholder: { type: String, default: "Seleziona…" },
});
defineEmits(["update:modelValue"]);
</script>

<template>
  <label class="block">
    <span
      v-if="label"
      class="mb-1.5 block text-sm font-medium text-slate-700"
      >{{ label }}</span
    >
    <select
      :value="modelValue"
      class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2"
      :class="
        error
          ? 'border-red-300 focus:ring-red-500'
          : 'border-slate-300 focus:ring-primary-500'
      "
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
    <span v-if="error" class="mt-1 block text-xs text-red-600">{{
      error
    }}</span>
  </label>
</template>
