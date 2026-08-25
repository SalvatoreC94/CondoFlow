<script setup>
import { onMounted, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";

import Avatar from "@/components/ui/Avatar.vue";
import { useAuthStore } from "@/stores/auth";
import { useTenantStore } from "@/stores/tenant";

const auth = useAuthStore();
const tenant = useTenantStore();
const route = useRoute();
const router = useRouter();

const mobileMenuOpen = ref(false);

onMounted(() => {
  tenant.fetchCondominiums();
});

const navItems = [
  { to: "/admin", label: "Dashboard", icon: "📊", exact: true },
  { to: "/admin/segnalazioni", label: "Segnalazioni", icon: "🔧" },
  { to: "/admin/condomini", label: "Condomini", icon: "🏢" },
  { to: "/admin/comunicazioni", label: "Comunicazioni", icon: "📣" },
  { to: "/admin/fornitori", label: "Fornitori", icon: "🛠️" },
  { to: "/admin/documenti", label: "Documenti", icon: "📄" },
  { to: "/admin/contabilita", label: "Contabilità", icon: "💶" },
  { to: "/admin/assemblee", label: "Assemblee", icon: "🗳️" },
];

function isActive(item) {
  return item.exact ? route.path === item.to : route.path.startsWith(item.to);
}

async function logout() {
  await auth.logout();
  router.push("/login");
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 lg:flex">
    <!-- Desktop sidebar -->
    <aside
      class="hidden w-64 flex-shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col"
    >
      <div class="flex h-16 items-center gap-2 px-5">
        <div
          class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600 text-sm font-bold text-white"
        >
          C
        </div>
        <span class="text-lg font-bold text-slate-900">CondoFlow</span>
      </div>

      <nav class="flex-1 space-y-1 px-3 py-2">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
          :class="
            isActive(item)
              ? 'bg-primary-50 text-primary-700'
              : 'text-slate-600 hover:bg-slate-100'
          "
        >
          <span class="text-lg leading-none">{{ item.icon }}</span>
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="border-t border-slate-200 p-3">
        <RouterLink
          to="/admin/profilo"
          class="flex items-center gap-3 rounded-xl p-2 hover:bg-slate-100"
        >
          <Avatar :name="auth.user?.name" size="sm" />
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900">
              {{ auth.user?.name }}
            </p>
            <p class="truncate text-xs text-slate-500">Amministratore</p>
          </div>
        </RouterLink>
        <button
          type="button"
          class="mt-1 flex w-full items-center gap-2 rounded-xl px-2 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100"
          @click="logout"
        >
          Esci
        </button>
      </div>
    </aside>

    <div class="flex min-h-screen flex-1 flex-col">
      <!-- Mobile top bar -->
      <header
        class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:hidden"
      >
        <div class="flex items-center gap-2">
          <div
            class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600 text-sm font-bold text-white"
          >
            C
          </div>
          <span class="text-lg font-bold text-slate-900">CondoFlow</span>
        </div>
        <button
          type="button"
          class="rounded-lg p-2 hover:bg-slate-100"
          @click="mobileMenuOpen = true"
        >
          <span class="text-xl">☰</span>
        </button>
      </header>

      <!-- Mobile drawer -->
      <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 flex lg:hidden">
        <div
          class="fixed inset-0 bg-slate-900/50"
          @click="mobileMenuOpen = false"
        />
        <div class="relative z-10 flex w-72 flex-col bg-white shadow-xl">
          <div class="flex h-16 items-center justify-between px-4">
            <span class="text-lg font-bold text-slate-900">Menu</span>
            <button
              type="button"
              class="rounded-lg p-2 hover:bg-slate-100"
              @click="mobileMenuOpen = false"
            >
              ✕
            </button>
          </div>
          <nav class="flex-1 space-y-1 px-3">
            <RouterLink
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium"
              :class="
                isActive(item)
                  ? 'bg-primary-50 text-primary-700'
                  : 'text-slate-600 hover:bg-slate-100'
              "
              @click="mobileMenuOpen = false"
            >
              <span class="text-lg leading-none">{{ item.icon }}</span>
              {{ item.label }}
            </RouterLink>
          </nav>
          <div class="border-t border-slate-200 p-3">
            <button
              type="button"
              class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-500 hover:bg-slate-100"
              @click="logout"
            >
              Esci
            </button>
          </div>
        </div>
      </div>

      <!-- Condominium selector -->
      <div
        v-if="tenant.condominiums.length > 1"
        class="border-b border-slate-200 bg-white px-4 py-2 lg:px-8"
      >
        <select
          class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-sm font-medium text-slate-700"
          :value="tenant.selectedId"
          @change="tenant.select(Number($event.target.value))"
        >
          <option v-for="c in tenant.condominiums" :key="c.id" :value="c.id">
            {{ c.name }}
          </option>
        </select>
      </div>

      <main class="flex-1 p-4 lg:p-8">
        <router-view />
      </main>
    </div>
  </div>
</template>
