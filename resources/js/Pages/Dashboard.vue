<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import type { Account, Category } from '@/types/finance';
import { CanvasRenderer } from 'echarts/renderers';
import { PieChart } from 'echarts/charts';
import { LegendComponent, TooltipComponent } from 'echarts/components';
import { use } from 'echarts/core';
import VChart from 'vue-echarts';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, Landmark, Plus, TrendingUp, WalletCards } from '@lucide/vue';
import { computed, ref } from 'vue';
import TransactionForm from './Transactions/Partials/TransactionForm.vue';

use([CanvasRenderer, PieChart, TooltipComponent, LegendComponent]);

type Summary = { currency: string; balance: string; income: string; expenses: string; net: string };
type InvestmentSummary = { currency: string; cost: string; current_value: string; market_return: string; market_return_percentage: string; realized_profit_loss: string; net_income: string; total_return: string; unpriced_holdings: number; price_date: string | null };
type RecentTransaction = { id: number; description: string; type: string; amount: string; currency: string; date: string; account: string; category: string | null; color: string | null };
type CategoryExpense = { name: string; color: string; total: string; currency: string };

const props = defineProps<{
    financialSummaries: Summary[];
    investments: InvestmentSummary[];
    accounts: Account[];
    recentTransactions: RecentTransaction[];
    categoryExpenses: CategoryExpense[];
    categories: Category[];
}>();
const selectedCurrency = ref(props.financialSummaries.find((summary) => summary.currency === 'BRL')?.currency ?? props.financialSummaries[0]?.currency ?? 'BRL');
const modalOpen = ref(false);
const formAccounts = computed(() =>
    props.accounts
        .filter((account) => !account.is_archived)
        .map((account) => ({ id: account.id, name: account.name, currency: account.currency })),
);
const openCreate = () => {
    modalOpen.value = true;
};
const closeModal = () => {
    modalOpen.value = false;
};
const summary = computed(() => props.financialSummaries.find((item) => item.currency === selectedCurrency.value) ?? { currency: selectedCurrency.value, balance: '0', income: '0', expenses: '0', net: '0' });
const investment = computed(() => props.investments.find((item) => item.currency === selectedCurrency.value));
const selectedCategoryExpenses = computed(() => props.categoryExpenses.filter((item) => item.currency === selectedCurrency.value));

const chartOption = computed(() => ({
    tooltip: { trigger: 'item', valueFormatter: (value: number) => formatMoney(String(value), selectedCurrency.value) },
    legend: { bottom: 0, icon: 'circle', textStyle: { color: '#78716c' } },
    series: [{
        type: 'pie',
        radius: ['48%', '72%'],
        center: ['50%', '42%'],
        avoidLabelOverlap: true,
        itemStyle: { borderRadius: 0, borderColor: '#fafaf8', borderWidth: 2 },
        label: { show: false },
        data: selectedCategoryExpenses.value.map((category) => ({
            name: category.name,
            value: Number(category.total),
            itemStyle: { color: category.color },
        })),
    }],
}));

const summaryCards = computed(() => [
    { label: 'Saldo total', value: summary.value.balance, icon: WalletCards, bar: 'bg-brand-500', chip: 'bg-gradient-to-br from-brand-500/15 to-brand-500/5 text-brand-700 ring-1 ring-black/5 dark:text-brand-300' },
    { label: 'Receitas no mes', value: summary.value.income, icon: ArrowDownLeft, bar: 'bg-emerald-500', chip: 'bg-gradient-to-br from-emerald-500/15 to-emerald-500/5 text-emerald-700 ring-1 ring-black/5 dark:text-emerald-300' },
    { label: 'Despesas no mes', value: summary.value.expenses, icon: ArrowUpRight, bar: 'bg-rose-500', chip: 'bg-gradient-to-br from-rose-500/15 to-rose-500/5 text-rose-700 ring-1 ring-black/5 dark:text-rose-300' },
    { label: 'Resultado mensal', value: summary.value.net, icon: TrendingUp, bar: 'bg-amber-500', chip: 'bg-gradient-to-br from-amber-500/15 to-amber-500/5 text-amber-700 ring-1 ring-black/5 dark:text-amber-300' },
]);
</script>

<template>
  <Head title="Visao geral" />
  <AuthenticatedLayout>
    <section class="relative overflow-hidden rounded-3xl border border-stone-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
      <div class="pointer-events-none absolute inset-x-0 top-0 h-48 bg-[radial-gradient(closest-side_at_50%_0%,rgba(51,141,255,0.14),transparent)] dark:bg-[radial-gradient(closest-side_at_50%_0%,rgba(51,141,255,0.18),transparent)]" aria-hidden="true" />
      <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-400">Painel financeiro</p>
          <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Uma leitura clara do seu mes.</h1>
          <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Saldos consolidados e movimentacoes mais recentes.</p>
        </div>
        <button type="button" class="relative inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:from-brand-400 hover:to-brand-600" @click="openCreate"><Plus :size="18" />Novo lancamento</button>
      </div>
    </section>

    <div class="relative mt-5 flex flex-wrap gap-2">
      <button v-for="item in financialSummaries" :key="item.currency" class="rounded-full text-xs font-bold transition" :class="selectedCurrency === item.currency ? 'bg-gradient-to-br from-brand-500 to-brand-600 px-4 py-1.5 text-white shadow-lg shadow-brand-500/25' : 'bg-white px-4 py-1.5 text-stone-600 ring-1 ring-stone-200 hover:bg-stone-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800'" @click="selectedCurrency = item.currency">{{ item.currency }}</button>
    </div>

    <section class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article v-for="card in summaryCards" :key="card.label" class="group relative overflow-hidden rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
        <span class="absolute inset-x-0 top-0 h-1" :class="card.bar" />
        <div class="flex items-start justify-between">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-slate-400">{{ card.label }}</p>
          <span class="grid h-9 w-9 place-items-center rounded-xl transition group-hover:-translate-y-0.5" :class="card.chip"><component :is="card.icon" :size="18" /></span>
        </div>
        <p class="mt-4 text-2xl font-bold tracking-tight" :class="card.label === 'Resultado mensal' && Number(card.value) < 0 ? 'text-rose-600 dark:text-rose-400' : card.label === 'Resultado mensal' ? 'text-emerald-700 dark:text-emerald-400' : ''">{{ formatMoney(card.value, selectedCurrency) }}</p>
      </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
      <article class="rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="flex items-center justify-between border-b border-stone-100 px-5 py-4 dark:border-slate-800"><div><h2 class="font-semibold">Movimentacoes recentes</h2><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">Ultimos registros do fluxo de caixa</p></div><Link :href="route('transactions.index')" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950/40">Ver todas</Link></header>
        <div v-if="recentTransactions.length">
          <div v-for="transaction in recentTransactions" :key="transaction.id" class="flex items-center gap-3 border-b border-stone-100 px-5 py-4 transition last:border-0 hover:bg-stone-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl ring-1 ring-black/5" :class="transaction.type === 'income' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : transaction.type === 'transfer_out' ? 'bg-brand-100 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'"><ArrowDownLeft v-if="transaction.type === 'income'" :size="17" /><ArrowLeftRight v-else-if="transaction.type === 'transfer_out'" :size="17" /><ArrowUpRight v-else :size="17" /></span>
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ transaction.description }}</p><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">{{ transaction.account }} · {{ formatDate(transaction.date) }}</p></div>
            <p class="text-sm font-bold" :class="transaction.type === 'income' ? 'text-emerald-700 dark:text-emerald-400' : transaction.type === 'transfer_out' ? 'text-brand-700 dark:text-brand-300' : 'text-rose-600 dark:text-rose-400'">{{ transaction.type === 'income' ? '+' : transaction.type === 'expense' ? '-' : '' }}{{ formatMoney(transaction.amount, transaction.currency) }}</p>
          </div>
        </div>
        <div v-else class="px-6 py-14 text-center text-sm text-stone-400 dark:text-slate-500">Seus primeiros lancamentos aparecerao aqui</div>
      </article>

      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header><h2 class="font-semibold">Despesas por categoria</h2><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">Distribuicao no mes atual</p></header>
        <VChart v-if="selectedCategoryExpenses.length" class="mt-2 h-72" :option="chartOption" autoresize />
        <div v-else class="grid h-72 place-items-center text-center text-sm text-stone-400 dark:text-slate-500">Categorize despesas para visualizar a distribuicao</div>
      </article>
    </section>

    <section class="relative mt-6 overflow-hidden rounded-3xl bg-slate-950 p-6 text-slate-100 shadow-sm sm:p-8">
      <div class="pointer-events-none absolute inset-x-0 top-0 h-48 bg-[radial-gradient(closest-side_at_50%_0%,rgba(51,141,255,0.22),transparent)]" aria-hidden="true" />
      <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-400">Investimentos · {{ selectedCurrency }}</p>
          <h2 class="mt-2 text-xl font-bold">Posicao consolidada</h2>
          <p class="mt-1 text-sm text-slate-400">Valores atuais sem conversao entre moedas</p>
        </div>
        <Link :href="route('reports.index', { currency: selectedCurrency })" class="rounded-xl px-3 py-1.5 text-xs font-semibold text-brand-400 ring-1 ring-brand-500/30 transition hover:bg-brand-500/10 hover:text-brand-300">Abrir relatorios</Link>
      </div>
      <div v-if="investment" class="relative mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="item in [{ label: 'Custo em aberto', value: investment.cost }, { label: 'Valor atual', value: investment.current_value }, { label: 'Nao realizado', value: investment.market_return }, { label: 'Realizado', value: investment.realized_profit_loss }, { label: 'Proventos liquidos', value: investment.net_income }, { label: 'Retorno total', value: investment.total_return }]"
          :key="item.label"
          class="rounded-2xl border border-white/5 bg-slate-900/80 p-4 transition hover:border-brand-500/30"
        >
          <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ item.label }}</p>
          <p class="mt-2 text-lg font-bold">{{ formatMoney(item.value, selectedCurrency) }}</p>
        </div>
      </div>
      <p v-else class="relative mt-6 text-sm text-slate-400">Nenhuma carteira nesta moeda.</p>
      <p v-if="investment?.unpriced_holdings" class="relative mt-4 text-xs text-amber-400">{{ investment.unpriced_holdings }} posicao(oes) sem cotacao, avaliada(s) pelo custo medio.</p>
    </section>

    <section class="mt-6 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <header class="mb-4 flex items-center justify-between"><div><h2 class="font-semibold">Contas</h2><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">Saldos atualizados por movimentacao</p></div><Link :href="route('accounts.index')" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950/40">Gerenciar</Link></header>
      <div v-if="accounts.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="account in accounts" :key="account.id" class="flex items-center gap-3 rounded-2xl bg-stone-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-slate-950"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500/15 to-brand-500/5 text-brand-700 ring-1 ring-black/5 dark:text-brand-300"><Landmark :size="18" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ account.name }}</p><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500"><span class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-brand-500 align-middle" />{{ account.currency }}</p></div><p class="text-sm font-bold">{{ formatMoney(account.balance ?? account.initial_balance, account.currency) }}</p></div>
      </div>
      <p v-else class="py-8 text-center text-sm text-stone-400 dark:text-slate-500">Cadastre uma conta para iniciar seu painel</p>
    </section>

    <Modal :show="modalOpen" max-width="2xl" title="Novo lancamento" @close="closeModal">
      <TransactionForm v-if="modalOpen" :accounts="formAccounts" :categories="categories" from-dashboard embedded @cancel="closeModal" />
    </Modal>
  </AuthenticatedLayout>
</template>