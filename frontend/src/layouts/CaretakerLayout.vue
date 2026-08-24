<script setup>
import { onMounted } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";

import Avatar from "@/components/ui/Avatar.vue";
import { useAuthStore } from "@/stores/auth";
import { useTenantStore } from "@/stores/tenant";

const auth = useAuthStore();
const tenant = useTenantStore();
const route = useRoute();
const router = useRouter();

onMounted(() => {
  tenant.fetchCondominiums();
});

async function logout() {
  await auth.logout();
  router.push("/login");
}
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-lg flex-col bg-slate-50">
    <header
      class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3"
    >
      <div class="flex items-center gap-2">
        <div
          class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600 text-sm font-bold text-white"
        >
          C
        </div>
        <span class="font-bold text-slate-900">CondoFlow</span>
      </div>
      <RouterLink
        v-if="route.name !== 'caretaker.profile'"
        to="/custode/profilo"
      >
        <Avatar :name="auth.user?.name" size="sm" />
      </RouterLink>
      <button
        v-else
        type="button"
        class="text-sm font-medium text-slate-500"
        @click="logout"
      >
        Esci
      </button>
    </header>

    <div
      v-if="tenant.condominiums.length > 1"
      class="border-b border-slate-200 bg-white px-4 py-2"
    >
      <select
        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-sm font-medium text-slate-700"
        :value="tenant.selectedId"
        @change="tenant.select(Number($event.target.value))"
      >
        <option v-for="c in tenant.condominiums" :key="c.id" :value="c.id">
          {{ c.name }}
        </option>
      </select>
    </div>

    <main class="flex-1 p-4 pb-10">
      <router-view />
    </main>
  </div>
</template>
