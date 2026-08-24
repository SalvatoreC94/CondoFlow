<script setup>
defineProps({
  rows: { type: Number, default: 3 },
});
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="row in rows"
      :key="row"
      class="h-16 animate-pulse rounded-2xl border border-slate-200 bg-slate-100"
    />
  </div>
</template>
