<script setup lang="ts">
import StatCard from '@/Components/StatCard.vue';
import CashFlowValues from '@/Components/CashFlowValues.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useChartPalette } from '@/lib/chartTheme';
import { formatDate, formatMoney, formatMonth } from '@/lib/format';
import { bankMetaFrom, type BankOption } from '@/lib/banks';
import type { Account, Category, ExpectedIncome } from '@/types/finance';
import { CanvasRenderer } from 'echarts/renderers';
import { BarChart, PieChart } from 'echarts/charts';
import { GridComponent, LegendComponent, TooltipComponent } from 'echarts/components';
import { use } from 'echarts/core';
import VChart from 'vue-echarts';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, BellRing, CalendarClock, CheckCircle2, Gauge, Landmark, Plus, TrendingUp } from '@lucide/vue';
import { computed, onUnmounted, ref } from 'vue';
import ExpectedIncomeForm from './ExpectedIncomes/Partials/ExpectedIncomeForm.vue';
import TransactionForm from './Transactions/Partials/TransactionForm.vue';

use([CanvasRenderer, BarChart, PieChart, TooltipComponent, LegendComponent, GridComponent]);

type Summary = { currency: string; balance: string; available_balance: string; investments: string; current_invoices: string; credit_card_debt: string; net_worth: string; income: string; expenses: string; net: string; planned_income: string; planned_expenses: string; planned_net: string };
type MonthlyTrend = { currency: string; months: { month: string; income: string; expenses: string }[] };
type InvestmentSummary = { currency: string; cost: string; current_value: string; market_return: string; market_return_percentage: string; realized_profit_loss: string; net_income: string; total_return: string; unpriced_holdings: number; price_date: string | null };
type RecentTransaction = { id: number; description: string; type: string; amount: string; currency: string; date: string; account: string; category: string | null; color: string | null };
type CategoryExpense = { name: string; color: string; total: string; currency: string };
type Attention = { pending_expected_incomes: number; due_events: number; budgets_over_limit: number; unpriced_holdings: number; items: { key: string; label: string; href: string; tone: string }[] };
type NextEvent = { date: string; description: string; account_name: string | null; currency: string | null; kind: string; type: string; amount: string };

const chartPalette = useChartPalette();
const props = defineProps<{
    financialSummaries: Summary[];
    monthlyTrends: MonthlyTrend[];
    expectedIncomes: ExpectedIncome[];
    investments: InvestmentSummary[];
    accounts: Account[];
    creditCards: NonNullable<Account['credit_card']>[];
    banks: BankOption[];
    recentTransactions: RecentTransaction[];
    nextEvents: NextEvent[];
    categoryExpenses: CategoryExpense[];
    categories: Category[];
    attention: Attention;
}>();
const selectedCurrency = ref(props.financialSummaries.find((summary) => summary.currency === 'BRL')?.currency ?? props.financialSummaries[0]?.currency ?? 'BRL');
const modalOpen = ref(false);
const incomeModalOpen = ref(false);
const modalTone = ref<false | 'brand' | 'green' | 'rose'>('rose');
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
        .map((account) => ({ id: account.id, name: account.name, currency: account.currency, type: account.type })),
);
const accountTone = (account: Account) => {
    const meta = bankMetaFrom(props.banks, account.bank);
    const color = account.color ?? meta?.color ?? '#1b6ef5';
    return { color, initials: meta?.initials ?? null, isBank: Boolean(meta) };
};
const openCreate = () => {
    modalTone.value = 'rose';
    modalOpen.value = true;
};
const closeModal = () => {
    modalOpen.value = false;
};
const onTypeChange = (type: string) => {
    modalTone.value = type === 'income' ? 'green' : type === 'transfer' ? 'brand' : 'rose';
};
const closeIncomeModal = () => {
    incomeModalOpen.value = false;
};
const receiveIncome = (income: ExpectedIncome) => router.post(route('expected-incomes.receive', income.id), {}, {
    onStart: () => receiving.value = income.id,
    onFinish: () => receiving.value = null,
});
const summary = computed(() => props.financialSummaries.find((item) => item.currency === selectedCurrency.value) ?? { currency: selectedCurrency.value, balance: '0', available_balance: '0', investments: '0', current_invoices: '0', credit_card_debt: '0', net_worth: '0', income: '0', expenses: '0', net: '0', planned_income: '0', planned_expenses: '0', planned_net: '0' });
const investment = computed(() => props.investments.find((item) => item.currency === selectedCurrency.value));
const selectedCategoryExpenses = computed(() => props.categoryExpenses.filter((item) => item.currency === selectedCurrency.value));
const selectedCreditCards = computed(() => props.creditCards.filter((card) => card.currency === selectedCurrency.value));
const selectedAccounts = computed(() => props.accounts.filter((account) => account.currency === selectedCurrency.value && account.type !== 'credit_card' && !account.is_archived));
const selectedNextEvents = computed(() => props.nextEvents.filter((event) => event.currency === selectedCurrency.value));

const chartOption = computed(() => ({
    tooltip: { confine: true, backgroundColor: chartPalette.value.surface, textStyle: { color: chartPalette.value.text }, trigger: 'item', valueFormatter: (value: number) => formatMoney(String(value), selectedCurrency.value) },
    series: [{
        type: 'pie',
        radius: ['48%', '72%'],
        center: ['50%', '50%'],
        avoidLabelOverlap: true,
        itemStyle: { borderRadius: 0, borderColor: chartPalette.value.surface, borderWidth: 2 },
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
    tooltip: { confine: true, backgroundColor: chartPalette.value.surface, textStyle: { color: chartPalette.value.text }, trigger: 'axis', valueFormatter: (value: number) => formatMoney(String(value), selectedCurrency.value) },
    legend: { bottom: 0, icon: 'circle', textStyle: { color: chartPalette.value.text } },
    grid: { left: 8, right: 8, top: 24, bottom: 32, containLabel: true },
    xAxis: { type: 'category', data: selectedMonthlyTrend.value.months.map((month) => formatMonth(month.month)), axisLabel: { color: chartPalette.value.text }, axisLine: { lineStyle: { color: chartPalette.value.grid } }, axisTick: { show: false } },
    yAxis: { name: selectedCurrency.value, type: 'value', axisLabel: { color: chartPalette.value.text, formatter: (value: number) => (Math.abs(value) >= 1000 ? `${value / 1000}k` : String(value)) }, splitLine: { lineStyle: { color: chartPalette.value.grid } } },
    series: [
        { name: 'Receitas', type: 'bar', barMaxWidth: 14, data: selectedMonthlyTrend.value.months.map((month) => Number(month.income)), itemStyle: { color: chartPalette.value.income, borderRadius: [6, 6, 0, 0] } },
        { name: 'Despesas', type: 'bar', barMaxWidth: 14, data: selectedMonthlyTrend.value.months.map((month) => Number(month.expenses)), itemStyle: { color: chartPalette.value.expense, borderRadius: [6, 6, 0, 0] } },
    ],
}));

const maxMonthlyValue = computed(() => Math.max(0, ...selectedMonthlyTrend.value.months.map((month) => Math.max(Number(month.income), Number(month.expenses)))));
const monthWidth = (value: string) => `${maxMonthlyValue.value > 0 ? Math.max(2, (Number(value) / maxMonthlyValue.value) * 100) : 0}%`;

const attentionItems = computed(() => {
    const tones: Record<string, string> = {
        rose: 'text-rose-700 ring-rose-200 hover:bg-rose-50 dark:text-rose-300 dark:ring-rose-900/60 dark:hover:bg-rose-950/40',
        amber: 'text-amber-700 ring-amber-200 hover:bg-amber-50 dark:text-amber-300 dark:ring-amber-900/60 dark:hover:bg-amber-950/40',
        emerald: 'text-emerald-700 ring-emerald-200 hover:bg-emerald-50 dark:text-emerald-300 dark:ring-emerald-900/60 dark:hover:bg-emerald-950/40',
        brand: 'text-brand-700 ring-brand-200 hover:bg-brand-50 dark:text-brand-300 dark:ring-brand-900/60 dark:hover:bg-brand-950/40',
    };

    return props.attention.items.map((item) => ({ ...item, icon: item.tone === 'rose' || item.tone === 'amber' ? CalendarClock : item.tone === 'emerald' ? ArrowDownLeft : Gauge, pill: tones[item.tone] ?? tones.brand }));
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
const currentMonthLabel = computed(() => {
    const month = selectedMonthlyTrend.value.months.at(-1)?.month;
    return month ? new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(new Date(`${month}-01T12:00:00Z`)) : new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' }).format(new Date());
});
</script>

<template>
  <Head title="Visão geral" />
  <AuthenticatedLayout>
    <header class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-sm font-medium text-stone-600 dark:text-slate-400">{{ greeting }}, {{ user?.split(' ')[0] }}</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Visão geral</h1>
        <p class="mt-2 text-sm text-stone-600 dark:text-slate-400">Suas finanças em <span class="font-medium capitalize">{{ currentMonthLabel }}</span></p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex rounded-lg border border-stone-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" role="group" aria-label="Moeda dos indicadores">
          <button v-for="item in financialSummaries" :key="item.currency" type="button" :aria-pressed="selectedCurrency === item.currency" class="min-h-10 rounded-md px-3 text-xs font-semibold transition" :class="selectedCurrency === item.currency ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300' : 'text-stone-600 dark:text-slate-400'" @click="selectedCurrency = item.currency">{{ item.currency }}</button>
        </div>
        <button type="button" class="cp-button" @click="openCreate"><Plus :size="18" aria-hidden="true" />Novo lançamento</button>
      </div>
    </header>

    <section aria-label="Resumo financeiro do mês" class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard label="Patrimônio líquido" :value="formatMoney(summary.net_worth, selectedCurrency)" tone="brand" bar-class="bg-brand-600">
        <template #footer>Disponível + investimentos − dívida dos cartões</template>
      </StatCard>
      <StatCard label="Receitas realizadas" :value="formatMoney(summary.income, selectedCurrency)" :icon="ArrowDownLeft" tone="positive">
        <template #footer>A receber: {{ formatMoney(summary.planned_income, selectedCurrency) }}</template>
      </StatCard>
      <StatCard label="Despesas realizadas" :value="formatMoney(summary.expenses, selectedCurrency)" :icon="ArrowUpRight" tone="negative">
        <template #footer>A pagar: {{ formatMoney(summary.planned_expenses, selectedCurrency) }}</template>
      </StatCard>
      <StatCard label="Saldo do mês" :value="formatMoney(summary.net, selectedCurrency)" :icon="TrendingUp" :tone="Number(summary.net) >= 0 ? 'positive' : 'negative'">
        <template #footer>Saldo dos lançamentos futuros: {{ formatMoney(summary.planned_net, selectedCurrency) }}</template>
      </StatCard>
    </section>

    <section aria-label="Composição do patrimônio" class="cp-card mt-4 grid divide-y divide-stone-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0 dark:divide-slate-800">
      <div class="min-w-0 p-4"><p class="text-xs text-stone-600 dark:text-slate-400">Saldo disponível</p><p class="financial-value mt-1 text-lg font-semibold">{{ formatMoney(summary.available_balance, selectedCurrency) }}</p><p class="mt-1 text-xs text-stone-600 dark:text-slate-400">Dinheiro, corrente e poupança</p></div>
      <div class="min-w-0 p-4"><p class="text-xs text-stone-600 dark:text-slate-400">Investimentos</p><p class="financial-value mt-1 text-lg font-semibold">{{ formatMoney(summary.investments, selectedCurrency) }}</p><p class="mt-1 text-xs text-stone-600 dark:text-slate-400">Contas de investimento e carteiras</p></div>
      <div class="min-w-0 p-4"><p class="text-xs text-stone-600 dark:text-slate-400">Faturas atuais</p><p class="financial-value mt-1 text-lg font-semibold text-rose-700 dark:text-rose-300">{{ formatMoney(summary.current_invoices, selectedCurrency) }}</p><p class="mt-1 text-xs text-stone-600 dark:text-slate-400">Dívida total: {{ formatMoney(summary.credit_card_debt, selectedCurrency) }}</p></div>
    </section>

    <section class="mt-5 rounded-2xl p-4 sm:p-5 transition-colors" :class="hasAttention ? 'border border-amber-200/70 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-950/30' : 'border border-emerald-200/60 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-950/20'">
      <div class="flex items-center gap-2">
        <CheckCircle2 v-if="allGood" :size="16" class="text-emerald-700 dark:text-emerald-300 " />
        <BellRing v-else :size="16" class="text-amber-600 dark:text-amber-400" />
        <h2 class="text-sm font-bold" :class="allGood ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-950 dark:text-amber-200'">{{ allGood ? 'Tudo em dia' : 'Precisa da sua atenção' }}</h2>
      </div>
      <div v-if="hasAttention" class="mt-3 flex flex-wrap gap-2">
        <Link v-for="item in attentionItems" :key="item.key" :href="item.href" class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold ring-1 transition dark:bg-slate-900" :class="item.pill">
          <component :is="item.icon" :size="15" />
          <span>{{ item.label }}</span>
        </Link>
      </div>
      <p v-else class="mt-1.5 text-xs text-emerald-600/80 dark:text-emerald-400/70">Nenhum vencimento, orçamento estourado ou posição sem cotação.</p>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
      <article class="min-w-0 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <header class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold">Receitas x despesas</h2><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">Últimos 6 meses · {{ selectedCurrency }}</p></div><Link :href="route('reports.index', { currency: selectedCurrency })" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 dark:text-brand-300 transition hover:bg-brand-50 dark:hover:bg-brand-950/40">Ver relatório</Link></header>
        <div v-if="hasMonthlyData && isDesktop" class="mt-4 h-72 min-w-0"><VChart aria-label="Comparação de receitas e despesas dos últimos seis meses" :option="monthlyChartOption" autoresize style="width: 100%; height: 100%;" /></div>
        <div v-else-if="hasMonthlyData" class="mt-4 space-y-3">
          <div v-for="month in selectedMonthlyTrend.months" :key="month.month" class="rounded-2xl bg-stone-50 px-3 py-2.5 dark:bg-slate-950">
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-stone-600 dark:text-slate-400">{{ formatMonth(month.month) }}</p>
            <div class="space-y-1.5">
              <div class="flex items-center gap-2">
                <span class="w-16 shrink-0 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300 ">Receita</span>
                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-950/60"><div class="h-full rounded-full bg-emerald-500" :style="{ width: monthWidth(month.income) }" /></div>
                <span class="shrink-0 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">{{ formatMoney(month.income, selectedCurrency) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-16 shrink-0 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300 ">Despesa</span>
                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-rose-100 dark:bg-rose-950/60"><div class="h-full rounded-full bg-rose-500" :style="{ width: monthWidth(month.expenses) }" /></div>
                <span class="shrink-0 text-[11px] font-semibold text-rose-700 dark:text-rose-400">{{ formatMoney(month.expenses, selectedCurrency) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="grid h-48 place-items-center text-center text-sm text-stone-600 dark:text-slate-400 sm:h-72">Sem lançamentos nos últimos 6 meses</div>
        <CashFlowValues v-if="hasMonthlyData" :months="selectedMonthlyTrend.months" :currency="selectedCurrency" />
      </article>

      <article class="min-w-0 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <header><h2 class="font-semibold">Despesas por categoria</h2><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">Distribuição no mês atual</p></header>
        <div v-if="sortedCategoryExpenses.length" class="mt-3">
          <div class="h-48 sm:h-56"><VChart :option="chartOption" autoresize style="width: 100%; height: 100%;" /></div>
          <ul class="mt-3 space-y-2.5 border-t border-stone-100 pt-3 dark:border-slate-800">
            <li v-for="category in sortedCategoryExpenses" :key="category.name" class="flex items-center gap-2.5">
              <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: category.color }" />
              <span class="min-w-0 flex-1 truncate text-sm text-stone-600 dark:text-slate-300">{{ category.name }}</span>
              <span class="whitespace-nowrap text-sm font-bold">{{ formatMoney(category.total, selectedCurrency) }}</span><span class="w-10 shrink-0 text-right text-xs text-stone-600 dark:text-slate-400">{{ Number(summary.expenses) > 0 ? Math.round(Number(category.total) / Number(summary.expenses) * 100) : 0 }}%</span>
            </li>
          </ul>
          <Link :href="route('reports.index', { currency: selectedCurrency })" class="mt-3 inline-flex text-xs font-semibold text-brand-700 dark:text-brand-300 hover:text-brand-700">Ver todas</Link>
        </div>
        <div v-else class="grid h-48 place-items-center text-center text-sm text-stone-600 dark:text-slate-400 sm:h-56">Categorize despesas para visualizar a distribuição</div>
      </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
      <article class="min-w-0 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <header class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold">Cartões de crédito</h2><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">Fatura, vencimento e limite disponível</p></div><Link :href="route('accounts.index')" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 dark:text-brand-300 transition hover:bg-brand-50 dark:hover:bg-brand-950/40">Gerenciar</Link></header>
        <div v-if="selectedCreditCards.length" class="mt-4 grid gap-3 md:grid-cols-2">
          <article v-for="card in selectedCreditCards" :key="card.id" class="rounded-2xl bg-stone-50 p-4 dark:bg-slate-950">
            <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-bold">{{ card.name }}</p><p class="mt-1 text-xs text-stone-600 dark:text-slate-400">Vence em {{ formatDate(card.next_due) }}</p></div><span class="rounded-full bg-white px-2 py-1 text-[10px] font-bold uppercase text-stone-600 dark:text-slate-400 dark:bg-slate-900">{{ card.status === 'overdue' ? 'Atrasada' : card.status === 'due_soon' ? 'Vence em breve' : card.status === 'closing_soon' ? 'Fecha em breve' : 'Em aberto' }}</span></div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs"><div><p class="text-stone-600 dark:text-slate-400">Fatura atual</p><p class="font-bold text-rose-700 dark:text-rose-300">{{ formatMoney(card.current_invoice, selectedCurrency) }}</p></div><div><p class="text-stone-600 dark:text-slate-400">Próximas</p><p class="font-bold">{{ formatMoney(card.future_invoices, selectedCurrency) }}</p></div><div v-if="Number(card.overdue_balance) > 0"><p class="text-stone-600 dark:text-slate-400">Vencido</p><p class="font-bold text-rose-700 dark:text-rose-300">{{ formatMoney(card.overdue_balance, selectedCurrency) }}</p></div><div><p class="text-stone-600 dark:text-slate-400">Disponível</p><p class="font-bold text-emerald-700 dark:text-emerald-300">{{ card.has_limit ? formatMoney(card.available ?? '0', selectedCurrency) : 'Não informado' }}</p></div></div>
            <div v-if="card.has_limit" class="mt-3 h-2 overflow-hidden rounded-full bg-stone-200 dark:bg-slate-800"><div class="h-full rounded-full bg-brand-500" :style="{ width: Math.min(card.utilization ?? 0, 100) + '%' }" /></div>
            <p v-if="card.has_limit" class="mt-2 text-xs text-stone-600 dark:text-slate-400">{{ card.utilization }}% utilizado · limite {{ formatMoney(card.credit_limit ?? '0', selectedCurrency) }}</p>
            <p class="mt-2 text-xs text-stone-600 dark:text-slate-400">Fecha em {{ formatDate(card.next_closing) }}</p>
            <Link :href="route('accounts.show', card.id)" class="mt-3 inline-flex text-xs font-semibold text-brand-700 dark:text-brand-300 hover:text-brand-700">Ver cartão</Link>
          </article>
        </div>
        <EmptyState v-else class="mt-4" title="Nenhum cartão nesta moeda" description="Cadastre um cartão para acompanhar faturas e compromissos."><template #action><Link :href="route('accounts.index', { create: 1 })" class="cp-button">Cadastrar cartão</Link></template></EmptyState>
      </article>
      <article class="flex min-w-0 flex-col rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <header class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold">Receitas futuras</h2><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">Confirme quando cair na conta</p></div><Link :href="route('expected-incomes.index')" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 dark:text-brand-300 transition hover:bg-brand-50 dark:hover:bg-brand-950/40">Ver todas</Link></header>
        <div v-if="expectedIncomes.length" class="mt-3 flex-1">
          <div v-for="income in expectedIncomes" :key="income.id" class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 border-b border-stone-100 py-3 last:border-0 dark:border-slate-800">
            <span class="hidden h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"><ArrowDownLeft :size="17" /></span>
            <div class="min-w-0"><p class="break-words text-sm font-semibold">{{ income.description }}</p><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">previsto {{ formatDate(income.expected_date) }}</p></div>
            <p class="financial-value text-right text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ formatMoney(income.amount, income.currency) }}</p>
            <button type="button" :disabled="receiving === income.id" class="col-span-2 justify-self-end grid h-11 w-11 place-items-center rounded-lg text-white disabled:opacity-50" :class="receiving === income.id ? 'bg-stone-300 dark:bg-slate-700' : 'bg-emerald-600 hover:bg-emerald-700'" title="Marcar como recebido" :aria-label="`Marcar ${income.description} como recebido`" @click="receiveIncome(income)"><CheckCircle2 :size="15" /></button>
          </div>
        </div>
        <div v-else class="grid flex-1 place-items-center py-8 text-center text-sm text-stone-600 dark:text-slate-400">Nenhuma receita prevista<br /><Link :href="route('expected-incomes.index')" class="mt-2 inline-flex items-center justify-center gap-2 rounded-xl border border-brand-200 px-4 py-2 text-xs font-semibold text-brand-700 dark:border-brand-800 dark:text-brand-300"><Plus :size="15" />Planejar entradas</Link></div>
        <button type="button" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 sm:w-auto" @click="incomeModalOpen = true"><Plus :size="17" />Nova receita futura</button>
      </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
      <article class="min-w-0 rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-stone-100 px-4 py-4 sm:px-5 dark:border-slate-800"><div><h2 class="font-semibold">Movimentações recentes</h2><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">Últimos registros do fluxo de caixa</p></div><Link :href="route('transactions.index')" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 dark:text-brand-300 transition hover:bg-brand-50 dark:hover:bg-brand-950/40">Ver todas</Link></header>
        <div v-if="recentTransactions.length">
          <div v-for="transaction in recentTransactions" :key="transaction.id" class="flex items-center gap-3 border-b border-stone-100 px-4 py-3.5 transition last:border-0 hover:bg-stone-50/80 sm:px-5 sm:py-4 dark:border-slate-800 dark:hover:bg-slate-800/40">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl ring-1 ring-black/5" :class="transaction.type === 'income' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : transaction.type === 'transfer_out' ? 'bg-brand-100 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'"><ArrowDownLeft v-if="transaction.type === 'income'" :size="17" /><ArrowLeftRight v-else-if="transaction.type === 'transfer_out'" :size="17" /><ArrowUpRight v-else :size="17" /></span>
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ transaction.description }}</p><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">{{ transaction.account }} · {{ formatDate(transaction.date) }}</p></div>
            <p class="whitespace-nowrap text-sm font-bold" :class="transaction.type === 'income' ? 'text-emerald-700 dark:text-emerald-400' : transaction.type === 'transfer_out' ? 'text-brand-700 dark:text-brand-300' : 'text-rose-700 dark:text-rose-300'">{{ transaction.type === 'income' ? '+' : transaction.type === 'expense' ? '-' : '' }}{{ formatMoney(transaction.amount, transaction.currency) }}</p>
          </div>
        </div>
        <div v-else class="px-6 py-14 text-center text-sm text-stone-600 dark:text-slate-400">Seus primeiros lançamentos aparecerão aqui</div>
      </article>

      <article class="min-w-0 rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-stone-100 px-4 py-4 sm:px-5 dark:border-slate-800"><div><h2 class="font-semibold">Próximos lançamentos</h2><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">Agenda de receitas futuras, recorrências e parcelas</p></div><Link :href="route('agenda.index')" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 dark:text-brand-300 transition hover:bg-brand-50 dark:hover:bg-brand-950/40">Ver agenda</Link></header>
        <div v-if="selectedNextEvents.length">
          <div v-for="event in selectedNextEvents" :key="`${event.kind}-${event.date}-${event.description}`" class="flex items-center gap-3 border-b border-stone-100 px-4 py-3.5 last:border-0 sm:px-5 sm:py-4 dark:border-slate-800"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="event.type === 'income' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : event.type === 'transfer' ? 'bg-brand-100 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'"><ArrowDownLeft v-if="event.type === 'income'" :size="17" /><ArrowLeftRight v-else-if="event.type === 'transfer'" :size="17" /><ArrowUpRight v-else :size="17" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ event.description }}</p><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">{{ formatDate(event.date) }} · {{ event.account_name }} · {{ event.kind === 'expected_income' ? 'receita futura' : event.kind === 'installment' ? 'parcela' : 'recorrência' }}</p></div><p class="whitespace-nowrap text-sm font-bold" :class="event.type === 'income' ? 'text-emerald-700 dark:text-emerald-400' : event.type === 'transfer' ? 'text-brand-700 dark:text-brand-300' : 'text-rose-700 dark:text-rose-300'">{{ formatMoney(event.amount, selectedCurrency) }}</p></div>
        </div>
        <p v-else class="px-6 py-14 text-center text-sm text-stone-600 dark:text-slate-400">Sem próximos lançamentos no horizonte.</p>
      </article>
    </section>

    <section class="cp-card relative mt-6 p-5 text-stone-900 dark:text-slate-100 sm:p-6">
      <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700 dark:text-brand-300">Investimentos · {{ selectedCurrency }}</p>
          <h2 class="mt-2 text-xl font-bold">Posição consolidada</h2>
          <p class="mt-1 text-sm text-stone-600 dark:text-slate-400">Valores atuais sem conversão entre moedas</p>
        </div>
        <Link :href="route('portfolios.index')" class="rounded-xl px-3 py-1.5 text-xs font-semibold text-brand-700 dark:text-brand-300 ring-1 ring-brand-500/30 transition hover:bg-brand-500/10 hover:text-brand-300">Abrir carteiras</Link>
      </div>
      <div v-if="investment" class="relative mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="item in [{ label: 'Custo em aberto', value: investment.cost }, { label: 'Valor atual', value: investment.current_value }, { label: 'Não realizado', value: investment.market_return }, { label: 'Realizado', value: investment.realized_profit_loss }, { label: 'Proventos líquidos', value: investment.net_income }, { label: 'Retorno total', value: investment.total_return }]"
          :key="item.label"
          class="min-w-0 border-t border-stone-100 pt-4 dark:border-slate-800"
        >
          <p class="text-[11px] font-semibold uppercase tracking-wider text-stone-600 dark:text-slate-400">{{ item.label }}</p>
          <p class="financial-value mt-2 text-lg font-bold">{{ formatMoney(item.value, selectedCurrency) }}</p>
        </div>
      </div>
      <p v-else class="relative mt-6 text-sm text-stone-600 dark:text-slate-400">Nenhuma carteira nesta moeda.</p>
      <p v-if="investment?.price_date" class="mt-4 text-xs text-stone-600 dark:text-slate-400">Cotação mais recente entre as posições: {{ formatDate(investment.price_date) }}. Consulte Ativos para conferir a data de cada cotação.</p>
      <p v-if="investment?.unpriced_holdings" class="relative mt-4 text-xs text-amber-800 dark:text-amber-300">{{ investment.unpriced_holdings }} posição(ões) sem cotação, avaliada(s) pelo custo médio.</p>
    </section>

    <section class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <header class="mb-4 flex items-center justify-between"><div><h2 class="font-semibold">Contas</h2><p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400">Saldos atualizados por movimentação</p></div><Link :href="route('accounts.index')" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 dark:text-brand-300 transition hover:bg-brand-50 dark:hover:bg-brand-950/40">Gerenciar</Link></header>
      <div v-if="selectedAccounts.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="account in selectedAccounts" :key="account.id" class="rounded-2xl bg-stone-50 p-4 transition hover:bg-stone-100 dark:bg-slate-950">
          <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-white" :style="accountTone(account).isBank ? { backgroundColor: accountTone(account).color } : { backgroundColor: accountTone(account).color + '22', color: accountTone(account).color }"><span v-if="accountTone(account).isBank" class="text-sm font-extrabold">{{ accountTone(account).initials }}</span><Landmark v-else :size="18" /></span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold">{{ account.name }}</p>
              <p class="mt-0.5 text-xs text-stone-600 dark:text-slate-400"><span class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-brand-500 align-middle" />{{ account.currency }}</p>
            </div>
            <p class="text-sm font-bold">{{ formatMoney(account.balance ?? account.initial_balance, account.currency) }}</p>
          </div>
          <div v-if="account.credit_card?.has_limit" class="mt-3">
            <div class="mb-1 flex items-center justify-between text-[11px] text-stone-600 dark:text-slate-400">
              <span>Limite utilizado</span>
              <span :class="account.credit_card.over_limit ? 'font-bold text-rose-700 dark:text-rose-300 ' : ''">{{ account.credit_card.utilization }}%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-stone-200 dark:bg-slate-700">
              <div class="h-full rounded-full transition-all duration-300" :class="account.credit_card.over_limit ? 'bg-rose-500' : account.credit_card.utilization && account.credit_card.utilization > 75 ? 'bg-amber-400' : 'bg-brand-500'" :style="{ width: Math.min(account.credit_card.utilization ?? 0, 100) + '%' }" />
            </div>
            <div class="mt-1.5 flex items-center justify-between text-[11px] text-stone-600 dark:text-slate-400">
              <span>Fatura: {{ formatMoney(account.credit_card.current_invoice, account.currency) }}</span>
              <span>Disponível: {{ formatMoney(account.credit_card.available ?? '0', account.currency) }}</span>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="py-8 text-center text-sm text-stone-600 dark:text-slate-400">Cadastre uma conta para iniciar seu painel</p>
    </section>

    <Modal :show="modalOpen" max-width="2xl" :gradient="modalTone" title="Novo lançamento" @close="closeModal">
      <TransactionForm v-if="modalOpen" :accounts="formAccounts" :categories="categories" from-dashboard embedded @cancel="closeModal" @saved="closeModal" @type-change="onTypeChange" />
    </Modal>

    <Modal :show="incomeModalOpen" max-width="2xl" title="Nova receita futura" @close="closeIncomeModal">
      <ExpectedIncomeForm v-if="incomeModalOpen" :accounts="accounts" :categories="categories" embedded @cancel="closeIncomeModal" />
    </Modal>
  </AuthenticatedLayout>
</template>
