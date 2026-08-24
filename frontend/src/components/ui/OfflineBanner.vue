<script setup>
import { onMounted, onUnmounted, ref } from "vue";

const online = ref(navigator.onLine);

function update() {
  online.value = navigator.onLine;
}

onMounted(() => {
  window.addEventListener("online", update);
  window.addEventListener("offline", update);
});
onUnmounted(() => {
  window.removeEventListener("online", update);
  window.removeEventListener("offline", update);
});
</script>

<template>
  <div
    v-if="!online"
    class="fixed inset-x-0 top-0 z-[200] bg-slate-900 px-4 py-1.5 text-center text-xs font-medium text-white"
  >
    Sei offline — alcune funzioni non sono disponibili finché la connessione non
    torna.
  </div>
</template>
