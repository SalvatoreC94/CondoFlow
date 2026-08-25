<script setup>
import { storeToRefs } from "pinia";
import { computed, onMounted, ref, watch } from "vue";

import Button from "@/components/ui/Button.vue";
import Card from "@/components/ui/Card.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Modal from "@/components/ui/Modal.vue";
import SelectField from "@/components/ui/SelectField.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import TextField from "@/components/ui/TextField.vue";
import TextareaField from "@/components/ui/TextareaField.vue";
import api from "@/lib/api";
import { parseApiError } from "@/lib/errors";
import { useTenantStore } from "@/stores/tenant";
import { useToastStore } from "@/stores/toast";

const tenant = useTenantStore();
const { selectedId } = storeToRefs(tenant);
const toast = useToastStore();

const tab = ref("expenses");
const loading = ref(true);
const expenses = ref([]);
const installments = ref([]);
const suppliers = ref([]);

const expandedInstallmentId = ref(null);
const expandedCharges = ref([]);
const expandedLoading = ref(false);

function currency(amount) {
  return new Intl.NumberFormat("it-IT", {
    style: "currency",
    currency: "EUR",
  }).format(Number(amount ?? 0));
}

async function load() {
  if (!selectedId.value) return;
  loading.value = true;
  const [expensesRes, installmentsRes, suppliersRes] = await Promise.all([
    api.get(`/api/condominiums/${selectedId.value}/expenses`, {
      params: { per_page: 100 },
    }),
    api.get(`/api/condominiums/${selectedId.value}/installments`, {
      params: { per_page: 100 },
    }),
    api.get("/api/suppliers", {
      params: { condominium_id: selectedId.value, per_page: 100 },
    }),
  ]);
  expenses.value = expensesRes.data.data;
  installments.value = installmentsRes.data.data;
  suppliers.value = suppliersRes.data.data;
  expandedInstallmentId.value = null;
  loading.value = false;
}

onMounted(async () => {
  if (!tenant.loaded) await tenant.fetchCondominiums();
  await load();
});
watch(selectedId, load);

const totalExpenses = computed(() =>
  expenses.value.reduce((sum, e) => sum + Number(e.amount), 0),
);

// -- Spese --------------------------------------------------------------

const expenseModalOpen = ref(false);
const expenseForm = ref(emptyExpenseForm());
const expenseErrors = ref({});
const savingExpense = ref(false);

function emptyExpenseForm() {
  return {
    supplier_id: "",
    category: "",
    description: "",
    amount: "",
    expense_date: new Date().toISOString().slice(0, 10),
    notes: "",
  };
}

async function createExpense() {
  savingExpense.value = true;
  expenseErrors.value = {};
  try {
    await api.post(`/api/condominiums/${selectedId.value}/expenses`, {
      ...expenseForm.value,
      supplier_id: expenseForm.value.supplier_id || null,
    });
    expenseModalOpen.value = false;
    expenseForm.value = emptyExpenseForm();
    toast.success("Spesa registrata.");
    await load();
  } catch (error) {
    expenseErrors.value = parseApiError(error).errors;
  } finally {
    savingExpense.value = false;
  }
}

// -- Rate -----------------------------------------------------------------

const installmentModalOpen = ref(false);
const installmentForm = ref(emptyInstallmentForm());
const installmentErrors = ref({});
const savingInstallment = ref(false);

function emptyInstallmentForm() {
  return {
    title: "",
    description: "",
    total_amount: "",
    split_method: "millesimi",
    due_date: "",
  };
}

async function createInstallment() {
  savingInstallment.value = true;
  installmentErrors.value = {};
  try {
    await api.post(
      `/api/condominiums/${selectedId.value}/installments`,
      installmentForm.value,
    );
    installmentModalOpen.value = false;
    installmentForm.value = emptyInstallmentForm();
    toast.success("Rata creata e ripartita tra le unità.");
    await load();
  } catch (error) {
    installmentErrors.value = parseApiError(error).errors;
  } finally {
    savingInstallment.value = false;
  }
}

async function toggleExpand(installment) {
  if (expandedInstallmentId.value === installment.id) {
    expandedInstallmentId.value = null;
    return;
  }
  expandedInstallmentId.value = installment.id;
  expandedLoading.value = true;
  const { data } = await api.get(`/api/installments/${installment.id}`);
  expandedCharges.value = data.data.charges;
  expandedLoading.value = false;
}

async function toggleCharge(charge) {
  const { data } = await api.patch(`/api/installment-charges/${charge.id}`, {
    paid: !charge.paid,
  });
  const index = expandedCharges.value.findIndex((c) => c.id === charge.id);
  if (index !== -1) expandedCharges.value[index] = data.data;

  // Refresh this installment's paid-units summary in the list card.
  const { data: installmentData } = await api.get(
    `/api/installments/${charge.installment_id}`,
  );
  const listIndex = installments.value.findIndex(
    (i) => i.id === charge.installment_id,
  );
  if (listIndex !== -1) installments.value[listIndex] = installmentData.data;
}
</script>

<template>
  <div class="space-y-4">
    <h1 class="text-xl font-bold text-slate-900">Contabilità</h1>

    <div class="flex gap-2 border-b border-slate-200">
      <button
        type="button"
        class="border-b-2 px-3 py-2 text-sm font-semibold"
        :class="
          tab === 'expenses'
            ? 'border-primary-600 text-primary-700'
            : 'border-transparent text-slate-500'
        "
        @click="tab = 'expenses'"
      >
        Spese ({{ expenses.length }})
      </button>
      <button
        type="button"
        class="border-b-2 px-3 py-2 text-sm font-semibold"
        :class="
          tab === 'installments'
            ? 'border-primary-600 text-primary-700'
            : 'border-transparent text-slate-500'
        "
        @click="tab = 'installments'"
      >
        Rate ({{ installments.length }})
      </button>
    </div>

    <Skeleton v-if="loading" :rows="4" />

    <template v-else-if="tab === 'expenses'">
      <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">
          Totale spese registrate:
          <strong>{{ currency(totalExpenses) }}</strong>
        </p>
        <Button size="sm" @click="expenseModalOpen = true"
          >+ Nuova spesa</Button
        >
      </div>

      <EmptyState
        v-if="expenses.length === 0"
        icon="🧾"
        title="Nessuna spesa registrata"
      />

      <div v-else class="space-y-2">
        <Card
          v-for="e in expenses"
          :key="e.id"
          class="flex items-center justify-between gap-3"
        >
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900">
              {{ e.description }}
            </p>
            <p class="text-xs text-slate-500">
              {{ e.category }} · {{ e.expense_date }}
              <span v-if="e.supplier"> · {{ e.supplier.name }}</span>
            </p>
          </div>
          <p class="whitespace-nowrap text-sm font-semibold text-slate-900">
            {{ currency(e.amount) }}
          </p>
        </Card>
      </div>
    </template>

    <template v-else>
      <div class="flex justify-end">
        <Button size="sm" @click="installmentModalOpen = true"
          >+ Nuova rata</Button
        >
      </div>

      <EmptyState
        v-if="installments.length === 0"
        icon="💶"
        title="Nessuna rata creata"
      />

      <div v-else class="space-y-2">
        <Card v-for="i in installments" :key="i.id" padded>
          <button
            type="button"
            class="flex w-full items-center justify-between gap-3 text-left"
            @click="toggleExpand(i)"
          >
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-slate-900">
                {{ i.title }}
              </p>
              <p class="text-xs text-slate-500">
                Scadenza {{ i.due_date }} · {{ i.split_method_label }} ·
                {{ i.paid_units_count }}/{{ i.units_count }} unità pagate
              </p>
            </div>
            <p class="whitespace-nowrap text-sm font-semibold text-slate-900">
              {{ currency(i.total_amount) }}
            </p>
          </button>

          <div
            v-if="expandedInstallmentId === i.id"
            class="mt-3 border-t border-slate-100 pt-3"
          >
            <p v-if="expandedLoading" class="text-sm text-slate-400">
              Caricamento quote…
            </p>
            <div v-else class="max-h-72 space-y-1 overflow-y-auto">
              <button
                v-for="c in expandedCharges"
                :key="c.id"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-left text-sm hover:bg-slate-50"
                @click="toggleCharge(c)"
              >
                <span class="text-slate-700"
                  >{{ c.unit?.code }} — {{ currency(c.amount) }}</span
                >
                <span
                  class="rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="
                    c.paid
                      ? 'bg-green-50 text-green-700'
                      : 'bg-amber-50 text-amber-700'
                  "
                >
                  {{ c.paid ? "Pagata" : "Da pagare" }}
                </span>
              </button>
            </div>
          </div>
        </Card>
      </div>
    </template>

    <Modal
      :open="expenseModalOpen"
      title="Nuova spesa"
      @close="expenseModalOpen = false"
    >
      <form class="space-y-3" @submit.prevent="createExpense">
        <TextField
          v-model="expenseForm.description"
          label="Descrizione"
          required
          :error="expenseErrors.description?.[0]"
        />
        <TextField
          v-model="expenseForm.category"
          label="Categoria"
          placeholder="Es. Manutenzione ordinaria"
          required
          :error="expenseErrors.category?.[0]"
        />
        <div class="grid grid-cols-2 gap-3">
          <TextField
            v-model="expenseForm.amount"
            type="number"
            label="Importo (€)"
            required
            :error="expenseErrors.amount?.[0]"
          />
          <TextField
            v-model="expenseForm.expense_date"
            type="date"
            label="Data"
            required
            :error="expenseErrors.expense_date?.[0]"
          />
        </div>
        <SelectField
          v-if="suppliers.length"
          v-model="expenseForm.supplier_id"
          label="Fornitore (opzionale)"
          :options="suppliers.map((s) => ({ value: s.id, label: s.name }))"
        />
        <TextareaField v-model="expenseForm.notes" label="Note (opzionale)" />
        <Button type="submit" block :loading="savingExpense"
          >Registra spesa</Button
        >
      </form>
    </Modal>

    <Modal
      :open="installmentModalOpen"
      title="Nuova rata"
      @close="installmentModalOpen = false"
    >
      <form class="space-y-3" @submit.prevent="createInstallment">
        <TextField
          v-model="installmentForm.title"
          label="Titolo"
          placeholder="Es. Rata ordinaria I trimestre 2026"
          required
          :error="installmentErrors.title?.[0]"
        />
        <TextareaField
          v-model="installmentForm.description"
          label="Descrizione (opzionale)"
        />
        <div class="grid grid-cols-2 gap-3">
          <TextField
            v-model="installmentForm.total_amount"
            type="number"
            label="Importo totale (€)"
            required
            :error="installmentErrors.total_amount?.[0]"
          />
          <TextField
            v-model="installmentForm.due_date"
            type="date"
            label="Scadenza"
            required
            :error="installmentErrors.due_date?.[0]"
          />
        </div>
        <SelectField
          v-model="installmentForm.split_method"
          label="Ripartizione"
          :options="[
            { value: 'millesimi', label: 'Per millesimi' },
            { value: 'equal', label: 'In parti uguali' },
          ]"
          :error="installmentErrors.split_method?.[0]"
        />
        <Button type="submit" block :loading="savingInstallment"
          >Crea e ripartisci</Button
        >
      </form>
    </Modal>
  </div>
</template>
