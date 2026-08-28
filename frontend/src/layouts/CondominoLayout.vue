<script setup>
import { computed, onMounted } from "vue";
import { RouterLink, useRoute } from "vue-router";

import { useAuthStore } from "@/stores/auth";
import { useNotificationsStore } from "@/stores/notifications";

const auth = useAuthStore();
const notifications = useNotificationsStore();
const route = useRoute();

const brandColor = computed(
  () => auth.user?.units?.[0]?.condominium?.brand_color,
);

onMounted(() => {
  notifications.fetchUnreadCount();
});

const navItems = [
  { to: "/app", label: "Home", icon: "🏠", exact: true },
  { to: "/app/segnalazioni", label: "Segnalazioni", icon: "🔧" },
  { to: "/app/comunicazioni", label: "Comunicazioni", icon: "📣" },
  { to: "/app/documenti", label: "Documenti", icon: "📄" },
  { to: "/app/spese", label: "Spese", icon: "💶" },
  { to: "/app/profilo", label: "Profilo", icon: "👤" },
];

function isActive(item) {
  return item.exact ? route.path === item.to : route.path.startsWith(item.to);
}
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-lg flex-col bg-slate-50">
    <main class="flex-1 pb-24">
      <router-view />
    </main>

    <nav
      class="safe-bottom fixed inset-x-0 bottom-0 z-40 mx-auto max-w-lg border-t border-slate-200 bg-white/95 backdrop-blur"
    >
      <div class="grid grid-cols-6">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium transition"
          :class="
            isActive(item) && !brandColor ? 'text-primary-600' : 'text-slate-400'
          "
          :style="isActive(item) && brandColor ? { color: brandColor } : {}"
        >
          <span class="text-xl leading-none">{{ item.icon }}</span>
          {{ item.label }}
        </RouterLink>
      </div>
    </nav>
  </div>
</template>
