<script setup>
defineProps({
  padded: { type: Boolean, default: true },
});
</script>

<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
    :class="padded ? 'p-4 sm:p-5' : ''"
  >
    <slot />
  </div>
</template>
