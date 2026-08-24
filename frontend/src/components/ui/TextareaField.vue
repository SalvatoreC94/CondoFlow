<script setup>
defineProps({
  modelValue: { type: String, default: "" },
  label: { type: String, default: "" },
  placeholder: { type: String, default: "" },
  error: { type: String, default: "" },
  rows: { type: Number, default: 4 },
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
    <textarea
      :value="modelValue"
      :placeholder="placeholder"
      :rows="rows"
      class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2"
      :class="
        error
          ? 'border-red-300 focus:ring-red-500'
          : 'border-slate-300 focus:ring-primary-500'
      "
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <span v-if="error" class="mt-1 block text-xs text-red-600">{{
      error
    }}</span>
  </label>
</template>
