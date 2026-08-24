<script setup>
defineProps({
  modelValue: { type: [String, Number], default: "" },
  label: { type: String, default: "" },
  type: { type: String, default: "text" },
  placeholder: { type: String, default: "" },
  error: { type: String, default: "" },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  autocomplete: { type: String, default: "off" },
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
    <input
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :autocomplete="autocomplete"
      class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 disabled:bg-slate-50 disabled:text-slate-400"
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
