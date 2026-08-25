import { createRouter, createWebHistory } from "vue-router";

import { useAuthStore } from "@/stores/auth";

const routes = [
  {
    path: "/login",
    component: () => import("@/views/auth/Login.vue"),
    meta: { guestOnly: true },
  },
  {
    path: "/accetta-invito/:token",
    component: () => import("@/views/auth/AcceptInvite.vue"),
    meta: { guestOnly: true },
  },
  {
    path: "/password-dimenticata",
    component: () => import("@/views/auth/ForgotPassword.vue"),
    meta: { guestOnly: true },
  },
  {
    path: "/reimposta-password",
    component: () => import("@/views/auth/ResetPassword.vue"),
    meta: { guestOnly: true },
  },

  // Condomino
  {
    path: "/app",
    component: () => import("@/layouts/CondominoLayout.vue"),
    meta: { requiresAuth: true, roles: ["condomino"] },
    children: [
      {
        path: "",
        name: "condomino.home",
        component: () => import("@/views/condomino/Home.vue"),
      },
      {
        path: "segnalazioni",
        name: "condomino.tickets",
        component: () => import("@/views/condomino/Tickets.vue"),
      },
      {
        path: "segnalazioni/nuova",
        name: "condomino.ticket-create",
        component: () => import("@/views/condomino/TicketCreate.vue"),
      },
      {
        path: "segnalazioni/:id",
        name: "condomino.ticket-detail",
        component: () => import("@/views/condomino/TicketDetail.vue"),
        props: true,
      },
      {
        path: "comunicazioni",
        name: "condomino.announcements",
        component: () => import("@/views/condomino/Announcements.vue"),
      },
      {
        path: "comunicazioni/:id",
        name: "condomino.announcement-detail",
        component: () => import("@/views/condomino/AnnouncementDetail.vue"),
        props: true,
      },
      {
        path: "documenti",
        name: "condomino.documents",
        component: () => import("@/views/condomino/Documents.vue"),
      },
      {
        path: "spese",
        name: "condomino.finance",
        component: () => import("@/views/condomino/Finance.vue"),
      },
      {
        path: "assemblee",
        name: "condomino.assemblies",
        component: () => import("@/views/condomino/Assemblies.vue"),
      },
      {
        path: "assemblee/:id",
        name: "condomino.assembly-detail",
        component: () => import("@/views/condomino/AssemblyDetail.vue"),
        props: true,
      },
      {
        path: "profilo",
        name: "condomino.profile",
        component: () => import("@/views/condomino/Profile.vue"),
      },
    ],
  },

  // Administrator
  {
    path: "/admin",
    component: () => import("@/layouts/AdminLayout.vue"),
    meta: { requiresAuth: true, roles: ["administrator"] },
    children: [
      {
        path: "",
        name: "admin.dashboard",
        component: () => import("@/views/admin/Dashboard.vue"),
      },
      {
        path: "condomini",
        name: "admin.condominiums",
        component: () => import("@/views/admin/Condominiums.vue"),
      },
      {
        path: "condomini/:id",
        name: "admin.condominium-detail",
        component: () => import("@/views/admin/CondominiumDetail.vue"),
        props: true,
      },
      {
        path: "segnalazioni",
        name: "admin.tickets",
        component: () => import("@/views/admin/Tickets.vue"),
      },
      {
        path: "segnalazioni/:id",
        name: "admin.ticket-detail",
        component: () => import("@/views/admin/TicketDetail.vue"),
        props: true,
      },
      {
        path: "comunicazioni",
        name: "admin.announcements",
        component: () => import("@/views/admin/Announcements.vue"),
      },
      {
        path: "fornitori",
        name: "admin.suppliers",
        component: () => import("@/views/admin/Suppliers.vue"),
      },
      {
        path: "contabilita",
        name: "admin.finance",
        component: () => import("@/views/admin/Finance.vue"),
      },
      {
        path: "assemblee",
        name: "admin.assemblies",
        component: () => import("@/views/admin/Assemblies.vue"),
      },
      {
        path: "assemblee/:id",
        name: "admin.assembly-detail",
        component: () => import("@/views/admin/AssemblyDetail.vue"),
        props: true,
      },
      {
        path: "documenti",
        name: "admin.documents",
        component: () => import("@/views/admin/Documents.vue"),
      },
      {
        path: "profilo",
        name: "admin.profile",
        component: () => import("@/views/condomino/Profile.vue"),
      },
    ],
  },

  // Caretaker
  {
    path: "/custode",
    component: () => import("@/layouts/CaretakerLayout.vue"),
    meta: { requiresAuth: true, roles: ["caretaker"] },
    children: [
      {
        path: "",
        name: "caretaker.dashboard",
        component: () => import("@/views/caretaker/Dashboard.vue"),
      },
      {
        path: "segnalazioni/:id",
        name: "caretaker.ticket-detail",
        component: () => import("@/views/admin/TicketDetail.vue"),
        props: true,
      },
      {
        path: "profilo",
        name: "caretaker.profile",
        component: () => import("@/views/condomino/Profile.vue"),
      },
    ],
  },

  { path: "/", redirect: "/login" },
  { path: "/:pathMatch(.*)*", redirect: "/login" },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();

  if (!auth.loaded) {
    await auth.fetchUser();
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return auth.homeRoute;
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return "/login";
  }

  if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
    return auth.homeRoute;
  }

  return true;
});

export default router;
