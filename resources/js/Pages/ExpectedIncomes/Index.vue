<script setup lang="ts">
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import type { Account, Category, ExpectedIncome } from '@/types/finance';
import { Head, router } from '@inertiajs/vue3';
import { ArrowDownLeft, CheckCircle2, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ExpectedIncomeForm from './Partials/ExpectedIncomeForm.vue';

const props = defineProps<{
    expectedIncomes: ExpectedIncome[];
    accounts: Account[];
    categories: Category[];
}>();

const modalOpen = ref(false);
const receiving = ref<number | null>(null);
const pending = computed(() => props.expectedIncomes.filter((income) => !income.received));
const received = computed(() => props.expectedIncomes.filter((income) => income.received));
const totalPending = (currency: string) => pending.value
    .filter((income) => income.currency === currency)
    .reduce((sum, income) => sum + Number(income.amount), 0);

const receive = (income: ExpectedIncome) => router.post(route('expected-incomes.receive', income.id), {}, {
    onStart: () => receiving.value = income.id,
    onFinish: () => receiving.value = null,
});
const remove = (income: ExpectedIncome) => {
    if (confirm(`Remover a receita futura "${income.description}"?`)) {
        router.delete(route('expected-incomes.destroy', income.id));
    }
};
</script>

<template>
  <Head title="Receitas futuras" />
  <AuthenticatedLayout>
    <PageHeader kicker="Financeiro" title="Receitas futuras" subtitle="Preveja entradas, confirme com um clique quando o dinheiro cair.">
      <template #actions>
        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700" @click="modalOpen = true"><Plus :size="18" />Nova receita</button>
      </template>
    </PageHeader>

    <div v-if="pending.length" class="mt-6 grid gap-3 lg:grid-cols-4">
      <div v-for="group in [...new Set(pending.map((income) => income.currency))]" :key="group" class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4 dark:border-brand-900 dark:bg-brand-950/30">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">A receber · {{ group }}</p>
        <p class="mt-1 text-xl font-bold">{{ formatMoney(String(totalPending(group)), group) }}</p>
      </div>
    </div>

    <Card v-if="expectedIncomes.length" class="mt-6">
      <div v-for="income in pending" :key="income.id" class="flex flex-col gap-4 border-b border-stone-100 px-5 py-4 last:border-0 hover:bg-stone-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40 sm:flex-row sm:items-center">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"><ArrowDownLeft :size="18" /></span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold">{{ income.description }}</p>
          <p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">{{ income.account_name }} · previsto {{ formatDate(income.expected_date) }}<template v-if="income.category_name"> · {{ income.category_name }}</template></p>
        </div>
        <p class="text-base font-bold text-emerald-700 dark:text-emerald-400">{{ formatMoney(income.amount, income.currency) }}</p>
        <div class="flex items-center gap-2">
          <button type="button" :disabled="receiving === income.id" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50" @click="receive(income)"><CheckCircle2 :size="16" />Recebido</button>
          <button type="button" class="rounded-xl p-2.5 text-stone-400 transition hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" title="Remover" @click="remove(income)"><Trash2 :size="16" /></button>
        </div>
      </div>

      <div v-if="received.length" class="border-t border-stone-100 px-5 py-4 dark:border-slate-800">
        <p class="text-xs font-semibold uppercase tracking-wider text-stone-400 dark:text-slate-500">Ja recebidas</p>
        <div v-for="income in received" :key="income.id" class="mt-2 flex items-center gap-3 opacity-70">
          <p class="min-w-0 flex-1 truncate text-sm">{{ income.description }}</p>
          <p class="shrink-0 whitespace-nowrap text-xs text-stone-400">{{ formatMoney(income.amount, income.currency) }} · {{ formatDate(income.received_at ?? income.expected_date) }}</p>
        </div>
      </div>
    </Card>

    <EmptyState v-else class="mt-8" :icon="ArrowDownLeft" title="Preveja suas proximas entradas" description="Cadastre o salario, adiantamentos, emprestimos a amigos e outras receitas previstas. Ao receber, clique em &quot;Recebido&quot; e vira um lancamento normal." />

    <Modal :show="modalOpen" max-width="2xl" title="Nova receita futura" @close="modalOpen = false">
      <ExpectedIncomeForm v-if="modalOpen" :accounts="accounts" :categories="categories" embedded @cancel="modalOpen = false" />
    </Modal>
  </AuthenticatedLayout>
</template>