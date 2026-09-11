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
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, BellRing, CalendarClock, CandlestickChart, CheckCircle2, Gauge, Landmark, Plus, TrendingUp, WalletCards } from '@lucide/vue';
import { computed, ref } from 'vue';
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
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-brand-950 p-6 text-slate-100 shadow-xl sm:p-8">
      <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-[radial-gradient(closest-side_at_50%_0%,rgba(51,141,255,0.22),transparent)]" aria-hidden="true" />
      <div class="pointer-events-none absolute bottom-0 right-0 h-48 w-48 bg-[radial-gradient(closest-side_at_100%_100%,rgba(51,141,255,0.12),transparent)]" aria-hidden="true" />
      <div class="relative">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-300">{{ greeting }}, {{ user?.split(' ')[0] }}</p>
            <div class="mt-3 flex items-baseline gap-3">
              <p class="text-3xl font-bold tracking-tight sm:text-4xl">{{ formatMoney(summary.balance, selectedCurrency) }}</p>
              <span
                v-if="Number(summary.net) !== 0"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"
                :class="Number(summary.net) >= 0 ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/30' : 'bg-rose-500/20 text-rose-300 ring-1 ring-rose-500/30'"
              >
                <TrendingUp v-if="Number(summary.net) >= 0" :size="13" />
                <ArrowUpRight v-else :size="13" />
                {{ formatMoney(summary.net, selectedCurrency) }} no mes
              </span>
            </div>
            <p class="mt-2 text-sm text-slate-400">Saldo consolidado em todas as contas</p>
          </div>
          <div class="flex items-center gap-2">
            <div class="flex gap-1.5">
              <button v-for="item in financialSummaries" :key="item.currency" class="rounded-full px-3 py-1.5 text-xs font-bold transition" :class="selectedCurrency === item.currency ? 'bg-white/15 text-white ring-1 ring-white/20' : 'text-slate-400 hover:text-white'" @click="selectedCurrency = item.currency">{{ item.currency }}</button>
            </div>
            <button type="button" class="ml-2 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition hover:bg-brand-400" @click="openCreate"><Plus :size="18" />Lancamento</button>
          </div>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div v-for="item in [{ label: 'Receitas', value: summary.income, icon: ArrowDownLeft, tone: 'text-emerald-400' }, { label: 'Despesas', value: summary.expenses, icon: ArrowUpRight, tone: 'text-rose-400' }]" :key="item.label" class="rounded-xl bg-white/5 p-3 ring-1 ring-white/5">
            <div class="flex items-center gap-1.5">
              <component :is="item.icon" :size="14" :class="item.tone" />
              <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ item.label }}</p>
            </div>
            <p class="mt-1.5 text-lg font-bold" :class="item.tone">{{ formatMoney(item.value, selectedCurrency) }}</p>
          </div>
          <div class="rounded-xl bg-white/5 p-3 ring-1 ring-white/5">
            <div class="flex items-center gap-1.5">
              <WalletCards :size="14" class="text-brand-400" />
              <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Saldo</p>
            </div>
            <p class="mt-1.5 text-lg font-bold">{{ formatMoney(summary.balance, selectedCurrency) }}</p>
          </div>
          <div class="rounded-xl bg-white/5 p-3 ring-1 ring-white/5">
            <div class="flex items-center gap-1.5">
              <TrendingUp :size="14" class="text-amber-400" />
              <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Resultado</p>
            </div>
            <p class="mt-1.5 text-lg font-bold" :class="Number(summary.net) >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ formatMoney(summary.net, selectedCurrency) }}</p>
          </div>
        </div>
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
      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="flex items-center justify-between"><div><h2 class="font-semibold">Receitas x despesas</h2><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">Ultimos 6 meses · {{ selectedCurrency }}</p></div><Link :href="route('reports.index', { currency: selectedCurrency })" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950/40">Ver relatorio</Link></header>
        <VChart v-if="hasMonthlyData" class="mt-4 h-56 sm:h-72" :option="monthlyChartOption" autoresize />
        <div v-else class="grid h-56 place-items-center text-center text-sm text-stone-400 dark:text-slate-500 sm:h-72">Sem lancamentos nos ultimos 6 meses</div>
      </article>

      <article class="flex flex-col rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="flex items-center justify-between"><div><h2 class="font-semibold">Receitas futuras</h2><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">Confirme quando cair na conta</p></div><Link :href="route('expected-incomes.index')" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950/40">Ver todas</Link></header>
        <div v-if="expectedIncomes.length" class="mt-3 flex-1">
          <div v-for="income in expectedIncomes" :key="income.id" class="flex items-center gap-3 border-b border-stone-100 py-3 last:border-0 dark:border-slate-800">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"><ArrowDownLeft :size="17" /></span>
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ income.description }}</p><p class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">previsto {{ formatDate(income.expected_date) }}</p></div>
            <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ formatMoney(income.amount, income.currency) }}</p>
            <button type="button" :disabled="receiving === income.id" class="rounded-xl px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" :class="receiving === income.id ? 'bg-stone-300 dark:bg-slate-700' : 'bg-emerald-600 hover:bg-emerald-700'" title="Marcar como recebido" @click="receiveIncome(income)"><CheckCircle2 :size="15" /></button>
          </div>
        </div>
        <div v-else class="grid flex-1 place-items-center py-8 text-center text-sm text-stone-400 dark:text-slate-500">Nenhuma receita prevista<br /><Link :href="route('expected-incomes.index')" class="mt-2 inline-flex items-center justify-center gap-2 rounded-xl border border-brand-200 px-4 py-2 text-xs font-semibold text-brand-700 dark:border-brand-800 dark:text-brand-300"><Plus :size="15" />Planejar entradas</Link></div>
        <button type="button" class="mt-3 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700" @click="incomeModalOpen = true"><Plus :size="17" />Nova receita futura</button>
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
        <VChart v-if="selectedCategoryExpenses.length" class="mt-2 h-56 sm:h-72" :option="chartOption" autoresize />
        <div v-else class="grid h-56 place-items-center text-center text-sm text-stone-400 dark:text-slate-500 sm:h-72">Categorize despesas para visualizar a distribuicao</div>
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