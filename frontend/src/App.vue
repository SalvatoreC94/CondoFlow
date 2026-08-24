<script setup>
import OfflineBanner from "@/components/ui/OfflineBanner.vue";
import ToastContainer from "@/components/ui/ToastContainer.vue";
</script>

<template>
  <OfflineBanner />
  <router-view />
  <ToastContainer />
</template>
