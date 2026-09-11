<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney, formatMonth } from '@/lib/format';
import type { Account, Category, ExpectedIncome } from '@/types/finance';
import { CanvasRenderer } from 'echarts/renderers';
import { BarChart, PieChart } from 'echarts/charts';
import { GridComponent, LegendComponent, TooltipComponent } from 'echarts/components';
import { use } from 'echarts/core';
import VChart from 'vue-echarts';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, BellRing, CalendarClock, CandlestickChart, CheckCircle2, Gauge, Landmark, Plus, TrendingUp } from '@lucide/vue';
import { computed, onUnmounted, ref } from 'vue';
import ExpectedIncomeForm from './ExpectedIncomes/Partials/ExpectedIncomeForm.vue';
import TransactionForm from './Transactions/Partials/TransactionForm.vue';

use([CanvasRenderer, BarChart, PieChart, TooltipComponent, LegendComponent, GridComponent]);

type Summary = { currency: string; balance: string; income: string; expenses: string; net: string };
type MonthlyTrend = { currency: string; months: { month: string; income: string; expenses: string }[] };
type InvestmentSummary = { currency: string; cost: string; current_value: string; market_return: string; market_return_percentage: string; realized_profit_loss: string; net_income: string; total_return: string; unpriced_holdings: number; price_date: string | null };
type RecentTransaction = { id: number; description: string; type: string; amount: string; currency: string; date: string; account: string; category: string | null; color: string | null };
type CategoryExpense = { name: string; color: string; total: string; currency: string };
type Attention = { pending_expected_incomes: number; due_events: number; budgets_over_limit: number; unpriced_holdings: number };

const props = defineProps<{
    financialSummaries: Summary[];
    monthlyTrends: MonthlyTrend[];
    expectedIncomes: ExpectedIncome[];
    investments: InvestmentSummary[];
    accounts: Account[];
    recentTransactions: RecentTransaction[];
    categoryExpenses: CategoryExpense[];
    categories: Category[];
    attention: Attention;
}>();
const selectedCurrency = ref(props.financialSummaries.find((summary) => summary.currency === 'BRL')?.currency ?? props.financialSummaries[0]?.currency ?? 'BRL');
const modalOpen = ref(false);
const incomeModalOpen = ref(false);
const receiving = ref<number | null>(null);
const isDesktop = ref(false);
const desktopQuery = window.matchMedia('(min-width: 640px)');
isDesktop.value = desktopQuery.matches;
const onDesktopChange = (event: MediaQueryListEvent) => { isDesktop.value = event.matches; };
if (typeof desktopQuery.addEventListener === 'function') desktopQuery.addEventListener('change', onDesktopChange);
else desktopQuery.addListener(onDesktopChange);
onUnmounted(() => {
    if (typeof desktopQuery.removeEventListener === 'function') desktopQuery.removeEventListener('change', onDesktopChange);
    else desktopQuery.removeListener(onDesktopChange);
});
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
const closeIncomeModal = () => {
    incomeModalOpen.value = false;
};
const receiveIncome = (income: ExpectedIncome) => router.post(route('expected-incomes.receive', income.id), {}, {
    onStart: () => receiving.value = income.id,
    onFinish: () => receiving.value = null,
});
const summary = computed(() => props.financialSummaries.find((item) => item.currency === selectedCurrency.value) ?? { currency: selectedCurrency.value, balance: '0', income: '0', expenses: '0', net: '0' });
const investment = computed(() => props.investments.find((item) => item.currency === selectedCurrency.value));
const selectedCategoryExpenses = computed(() => props.categoryExpenses.filter((item) => item.currency === selectedCurrency.value));

const chartOption = computed(() => ({
    tooltip: { trigger: 'item', valueFormatter: (value: number) => formatMoney(String(value), selectedCurrency.value) },
    series: [{
        type: 'pie',
        radius: ['48%', '72%'],
        center: ['50%', '50%'],
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
const sortedCategoryExpenses = computed(() => [...selectedCategoryExpenses.value].sort((a, b) => Number(b.total) - Number(a.total)));

const selectedMonthlyTrend = computed(() => props.monthlyTrends.find((item) => item.currency === selectedCurrency.value) ?? { currency: selectedCurrency.value, months: [] });
const hasMonthlyData = computed(() => selectedMonthlyTrend.value.months.some((month) => Number(month.income) > 0 || Number(month.expenses) > 0));
const monthlyChartOption = computed(() => ({
    tooltip: { trigger: 'axis', valueFormatter: (value: number) => formatMoney(String(value), selectedCurrency.value) },
    legend: { bottom: 0, icon: 'circle', textStyle: { color: '#78716c' } },
    grid: { left: 8, right: 8, top: 24, bottom: 32, containLabel: true },
    xAxis: { type: 'category', data: selectedMonthlyTrend.value.months.map((month) => formatMonth(month.month)), axisLabel: { color: '#a8a29e' }, axisLine: { lineStyle: { color: '#e7e5e4' } }, axisTick: { show: false } },
    yAxis: { type: 'value', axisLabel: { color: '#a8a29e', formatter: (value: number) => (Math.abs(value) >= 1000 ? `${value / 1000}k` : String(value)) }, splitLine: { lineStyle: { color: '#f5f5f4' } } },
    series: [
        { name: 'Receitas', type: 'bar', barMaxWidth: 14, data: selectedMonthlyTrend.value.months.map((month) => Number(month.income)), itemStyle: { color: '#10b981', borderRadius: [6, 6, 0, 0] } },
        { name: 'Despesas', type: 'bar', barMaxWidth: 14, data: selectedMonthlyTrend.value.months.map((month) => Number(month.expenses)), itemStyle: { color: '#f43f5e', borderRadius: [6, 6, 0, 0] } },
    ],
}));

const maxMonthlyValue = computed(() => Math.max(0, ...selectedMonthlyTrend.value.months.map((month) => Math.max(Number(month.income), Number(month.expenses)))));
const monthWidth = (value: string) => `${maxMonthlyValue.value > 0 ? Math.max(2, (Number(value) / maxMonthlyValue.value) * 100) : 0}%`;

const attentionItems = computed(() => {
    const items: { key: string; count: number; label: string; href: string; icon: typeof ArrowDownLeft; pill: string }[] = [];
    if (props.attention.pending_expected_incomes > 0) {
        items.push({ key: 'expected', count: props.attention.pending_expected_incomes, label: 'receitas a receber', href: route('expected-incomes.index'), icon: ArrowDownLeft, pill: 'text-emerald-700 ring-emerald-200 hover:bg-emerald-50 dark:text-emerald-300 dark:ring-emerald-900/60 dark:hover:bg-emerald-950/40' });
    }
    if (props.attention.due_events > 0) {
        items.push({ key: 'due', count: props.attention.due_events, label: 'vencimentos de hoje', href: route('agenda.index'), icon: CalendarClock, pill: 'text-rose-700 ring-rose-200 hover:bg-rose-50 dark:text-rose-300 dark:ring-rose-900/60 dark:hover:bg-rose-950/40' });
    }
    if (props.attention.budgets_over_limit > 0) {
        items.push({ key: 'budgets', count: props.attention.budgets_over_limit, label: 'orcamentos no limite', href: route('budgets.index'), icon: Gauge, pill: 'text-amber-700 ring-amber-200 hover:bg-amber-50 dark:text-amber-300 dark:ring-amber-900/60 dark:hover:bg-amber-950/40' });
    }
    if (props.attention.unpriced_holdings > 0) {
        items.push({ key: 'prices', count: props.attention.unpriced_holdings, label: 'posicoes sem cotacao', href: route('assets.index'), icon: CandlestickChart, pill: 'text-amber-700 ring-amber-200 hover:bg-amber-50 dark:text-amber-300 dark:ring-amber-900/60 dark:hover:bg-amber-950/40' });
    }
    return items;
});
const hasAttention = computed(() => attentionItems.value.length > 0);
const allGood = computed(() => !hasAttention.value);
const user = computed(() => (usePage().props.auth?.user as { name: string } | undefined)?.name ?? '');
const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Bom dia';
    if (h < 18) return 'Boa tarde';
    return 'Boa noite';
});
</script>

<template>
  <Head title="Visao geral" />
  <AuthenticatedLayout>
    <section class="rounded-3xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-600 dark:text-brand-400">{{ greeting }}, {{ user?.split(' ')[0] }}</p>
          <h1 class="mt-1 text-xl font-bold text-stone-900 dark:text-white">Visao geral</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <div class="flex gap-1 rounded-full bg-stone-100 p-1 dark:bg-slate-800">
            <button v-for="item in financialSummaries" :key="item.currency" type="button" class="rounded-full px-3 py-1.5 text-xs font-bold transition" :class="selectedCurrency === item.currency ? 'bg-white text-brand-700 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-stone-500 hover:text-stone-800 dark:text-slate-400 dark:hover:text-slate-200'" @click="selectedCurrency = item.currency">{{ item.currency }}</button>
          </div>
          <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700" @click="openCreate"><Plus :size="18" />Lancamento</button>
        </div>
      </div>

      <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-5 text-white shadow-lg shadow-brand-700/20 sm:col-span-3 sm:p-6">
          <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-[radial-gradient(closest-side_at_50%_0%,rgba(255,255,255,0.18),transparent)]" aria-hidden="true" />
          <div class="relative">
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-200">Saldo em contas</p>
            <p class="mt-2 text-2xl font-bold tracking-tight sm:text-4xl">{{ formatMoney(summary.balance, selectedCurrency) }}</p>
            <p class="mt-2 text-sm text-brand-200">Consolidado em todas as contas deste mes</p>
          </div>
        </article>
        <article class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100 dark:bg-emerald-950/40 dark:ring-emerald-900/60">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300"><ArrowDownLeft :size="18" /></span>
          <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wider text-emerald-700/70 dark:text-emerald-400/70">Receitas no mes</p><p class="mt-0.5 truncate text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ formatMoney(summary.income, selectedCurrency) }}</p></div>
        </article>
        <article class="flex items-center gap-3 rounded-2xl bg-rose-50 p-4 ring-1 ring-rose-100 dark:bg-rose-950/40 dark:ring-rose-900/60">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300"><ArrowUpRight :size="18" /></span>
          <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wider text-rose-700/70 dark:text-rose-400/70">Despesas no mes</p><p class="mt-0.5 truncate text-lg font-bold text-rose-700 dark:text-rose-300">{{ formatMoney(summary.expenses, selectedCurrency) }}</p></div>
        </article>
        <article class="flex items-center gap-3 rounded-2xl bg-stone-50 p-4 ring-1 ring-stone-200 dark:bg-slate-800/60 dark:ring-slate-700/60">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-stone-200 text-stone-700 dark:bg-slate-700 dark:text-slate-200"><TrendingUp :size="18" /></span>
          <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-slate-400">Resultado no mes</p><p class="mt-0.5 truncate text-lg font-bold" :class="Number(summary.net) >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'">{{ formatMoney(summary.net, selectedCurrency) }}</p></div>
        </article>
      </div>
    </section>

    <section class="mt-5 rounded-2xl p-4 sm:p-5 transition-colors" :class="hasAttention ? 'border border-amber-200/70 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-950/30' : 'border border-emerald-200/60 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-950/20'">
      <div class="flex items-center gap-2">
        <CheckCircle2 v-if="allGood" :size="16" class="text-emerald-600 dark:text-emerald-400" />
        <BellRing v-else :size="16" class="text-amber-600 dark:text-amber-400" />
        <h2 class="text-sm font-bold" :class="allGood ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-950 dark:text-amber-200'">{{ allGood ? 'Tudo em dia' : 'Precisa da sua atencao' }}</h2>
      </div>
      <div v-if="hasAttention" class="mt-3 flex flex-wrap gap-2">
        <Link v-for="item in attentionItems" :key="item.key" :href="item.href" class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold ring-1 transition dark:bg-slate-900" :class="item.pill">
          <component :is="item.icon" :size="15" />
          <span>{{ item.count }}</span>
          <span>{{ item.label }}</span>
        </Link>
      </div>
      <p v-else class="mt-1.5 text-xs text-emerald-600/80 dark:text-emerald-400/70">Nenhum vencimento, orcamento estourado ou posicao sem cotacao.</p>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
      <article class="rounded-3xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <header class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold">Receitas x despesas</h2><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">Ultimos 6 meses · {{ selectedCurrency }}</p></div><Link :href="route('reports.index', { currency: selectedCurrency })" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950/40">Ver relatorio</Link></header>
        <VChart v-if="hasMonthlyData && isDesktop" class="mt-4 h-72" :option="monthlyChartOption" autoresize />
        <div v-else-if="hasMonthlyData" class="mt-4 space-y-3">
          <div v-for="month in selectedMonthlyTrend.months" :key="month.month" class="rounded-2xl bg-stone-50 px-3 py-2.5 dark:bg-slate-950">
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400">{{ formatMonth(month.month) }}</p>
            <div class="space-y-1.5">
              <div class="flex items-center gap-2">
                <span class="w-16 shrink-0 text-[10px] font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Receita</span>
                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-950/60"><div class="h-full rounded-full bg-emerald-500" :style="{ width: monthWidth(month.income) }" /></div>
                <span class="shrink-0 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">{{ formatMoney(month.income, selectedCurrency) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-16 shrink-0 text-[10px] font-bold uppercase tracking-wide text-rose-600 dark:text-rose-400">Despesa</span>
                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-rose-100 dark:bg-rose-950/60"><div class="h-full rounded-full bg-rose-500" :style="{ width: monthWidth(month.expenses) }" /></div>
                <span class="shrink-0 text-[11px] font-semibold text-rose-700 dark:text-rose-400">{{ formatMoney(month.expenses, selectedCurrency) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="grid h-48 place-items-center text-center text-sm text-stone-400 dark:text-slate-500 sm:h-72">Sem lancamentos nos ultimos 6 meses</div>
      </article>

      <article class="flex flex-col rounded-3xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <header class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold">Receitas futuras</h2><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">Confirme quando cair na conta</p></div><Link :href="route('expected-incomes.index')" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950/40">Ver todas</Link></header>
        <div v-if="expectedIncomes.length" class="mt-3 flex-1">
          <div v-for="income in expectedIncomes" :key="income.id" class="flex items-center gap-3 border-b border-stone-100 py-3 last:border-0 dark:border-slate-800">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"><ArrowDownLeft :size="17" /></span>
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ income.description }}</p><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">previsto {{ formatDate(income.expected_date) }}</p></div>
            <p class="whitespace-nowrap text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ formatMoney(income.amount, income.currency) }}</p>
            <button type="button" :disabled="receiving === income.id" class="shrink-0 rounded-xl px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" :class="receiving === income.id ? 'bg-stone-300 dark:bg-slate-700' : 'bg-emerald-600 hover:bg-emerald-700'" title="Marcar como recebido" @click="receiveIncome(income)"><CheckCircle2 :size="15" /></button>
          </div>
        </div>
        <div v-else class="grid flex-1 place-items-center py-8 text-center text-sm text-stone-400 dark:text-slate-500">Nenhuma receita prevista<br /><Link :href="route('expected-incomes.index')" class="mt-2 inline-flex items-center justify-center gap-2 rounded-xl border border-brand-200 px-4 py-2 text-xs font-semibold text-brand-700 dark:border-brand-800 dark:text-brand-300"><Plus :size="15" />Planejar entradas</Link></div>
        <button type="button" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 sm:w-auto" @click="incomeModalOpen = true"><Plus :size="17" />Nova receita futura</button>
      </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
      <article class="rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-stone-100 px-4 py-4 sm:px-5 dark:border-slate-800"><div><h2 class="font-semibold">Movimentacoes recentes</h2><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">Ultimos registros do fluxo de caixa</p></div><Link :href="route('transactions.index')" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950/40">Ver todas</Link></header>
        <div v-if="recentTransactions.length">
          <div v-for="transaction in recentTransactions" :key="transaction.id" class="flex items-center gap-3 border-b border-stone-100 px-4 py-3.5 transition last:border-0 hover:bg-stone-50/80 sm:px-5 sm:py-4 dark:border-slate-800 dark:hover:bg-slate-800/40">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl ring-1 ring-black/5" :class="transaction.type === 'income' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : transaction.type === 'transfer_out' ? 'bg-brand-100 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'"><ArrowDownLeft v-if="transaction.type === 'income'" :size="17" /><ArrowLeftRight v-else-if="transaction.type === 'transfer_out'" :size="17" /><ArrowUpRight v-else :size="17" /></span>
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ transaction.description }}</p><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">{{ transaction.account }} · {{ formatDate(transaction.date) }}</p></div>
            <p class="whitespace-nowrap text-sm font-bold" :class="transaction.type === 'income' ? 'text-emerald-700 dark:text-emerald-400' : transaction.type === 'transfer_out' ? 'text-brand-700 dark:text-brand-300' : 'text-rose-600 dark:text-rose-400'">{{ transaction.type === 'income' ? '+' : transaction.type === 'expense' ? '-' : '' }}{{ formatMoney(transaction.amount, transaction.currency) }}</p>
          </div>
        </div>
        <div v-else class="px-6 py-14 text-center text-sm text-stone-400 dark:text-slate-500">Seus primeiros lancamentos aparecerao aqui</div>
      </article>

      <article class="rounded-3xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <header><h2 class="font-semibold">Despesas por categoria</h2><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">Distribuicao no mes atual</p></header>
        <div v-if="sortedCategoryExpenses.length" class="mt-3">
          <VChart class="h-48 sm:h-56" :option="chartOption" autoresize />
          <ul class="mt-3 space-y-2.5 border-t border-stone-100 pt-3 dark:border-slate-800">
            <li v-for="category in sortedCategoryExpenses" :key="category.name" class="flex items-center gap-2.5">
              <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: category.color }" />
              <span class="min-w-0 flex-1 truncate text-sm text-stone-600 dark:text-slate-300">{{ category.name }}</span>
              <span class="whitespace-nowrap text-sm font-bold">{{ formatMoney(category.total, selectedCurrency) }}</span>
            </li>
          </ul>
        </div>
        <div v-else class="grid h-48 place-items-center text-center text-sm text-stone-400 dark:text-slate-500 sm:h-56">Categorize despesas para visualizar a distribuicao</div>
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

    <Modal :show="incomeModalOpen" max-width="2xl" title="Nova receita futura" @close="closeIncomeModal">
      <ExpectedIncomeForm v-if="incomeModalOpen" :accounts="accounts" :categories="categories" embedded @cancel="closeIncomeModal" />
    </Modal>
  </AuthenticatedLayout>
</template>