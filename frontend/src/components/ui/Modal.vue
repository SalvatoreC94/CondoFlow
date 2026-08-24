<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: "" },
});
defineEmits(["close"]);
</script>

<template>
  <teleport to="body">
    <transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-0 sm:items-center sm:p-4"
        @click.self="$emit('close')"
      >
        <div
          class="max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-lg sm:rounded-2xl"
        >
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">{{ title }}</h2>
            <button
              type="button"
              class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600"
              @click="$emit('close')"
            >
              ✕
            </button>
          </div>
          <slot />
        </div>
      </div>
    </transition>
  </teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
