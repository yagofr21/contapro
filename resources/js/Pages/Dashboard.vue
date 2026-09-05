<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import type { Account } from '@/types/finance';
import { CanvasRenderer } from 'echarts/renderers';
import { PieChart } from 'echarts/charts';
import { LegendComponent, TooltipComponent } from 'echarts/components';
import { use } from 'echarts/core';
import VChart from 'vue-echarts';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, Landmark, Plus, TrendingUp, WalletCards } from '@lucide/vue';
import { computed, ref } from 'vue';

use([CanvasRenderer, PieChart, TooltipComponent, LegendComponent]);

type Summary = { currency: string; balance: string; income: string; expenses: string; net: string };
type InvestmentSummary = { currency: string; cost: string; current_value: string; market_return: string; market_return_percentage: string; net_income: string; unpriced_holdings: number; price_date: string | null };
type RecentTransaction = { id: number; description: string; type: string; amount: string; currency: string; date: string; account: string; category: string | null; color: string | null };
type CategoryExpense = { name: string; color: string; total: string; currency: string };

const props = defineProps<{
    financialSummaries: Summary[];
    investments: InvestmentSummary[];
    accounts: Account[];
    recentTransactions: RecentTransaction[];
    categoryExpenses: CategoryExpense[];
}>();
const selectedCurrency = ref(props.financialSummaries.find((summary) => summary.currency === 'BRL')?.currency ?? props.financialSummaries[0]?.currency ?? 'BRL');
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
        itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 3 },
        label: { show: false },
        data: selectedCategoryExpenses.value.map((category) => ({
            name: category.name,
            value: Number(category.total),
            itemStyle: { color: category.color },
        })),
    }],
}));

const summaryCards = computed(() => [
    { label: 'Saldo total', value: summary.value.balance, icon: WalletCards, tone: 'bg-brand-50 text-brand-700 dark:bg-brand-950/50 dark:text-brand-300' },
    { label: 'Receitas no mes', value: summary.value.income, icon: ArrowDownLeft, tone: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' },
    { label: 'Despesas no mes', value: summary.value.expenses, icon: ArrowUpRight, tone: 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' },
    { label: 'Resultado mensal', value: summary.value.net, icon: TrendingUp, tone: 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' },
]);
</script>

<template>
  <Head title="Visao geral" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Painel financeiro</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Uma leitura clara do seu mes.</h1>
        <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Saldos consolidados e movimentacoes mais recentes.</p>
      </div>
      <Link :href="route('transactions.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Novo lancamento</Link>
    </section>

    <div class="mt-6 flex flex-wrap gap-2"><button v-for="item in financialSummaries" :key="item.currency" class="rounded-full px-4 py-2 text-xs font-bold transition" :class="selectedCurrency === item.currency ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950' : 'border border-stone-200 bg-white text-stone-500 dark:border-slate-700 dark:bg-slate-900'" @click="selectedCurrency = item.currency">{{ item.currency }}</button></div>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article v-for="card in summaryCards" :key="card.label" class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start justify-between"><p class="text-sm font-medium text-stone-500 dark:text-slate-400">{{ card.label }}</p><span class="grid h-9 w-9 place-items-center rounded-xl" :class="card.tone"><component :is="card.icon" :size="18" /></span></div>
        <p class="mt-5 text-2xl font-bold tracking-tight" :class="card.label === 'Resultado mensal' && Number(card.value) < 0 ? 'text-rose-600' : ''">{{ formatMoney(card.value, selectedCurrency) }}</p>
      </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
      <article class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="flex items-center justify-between border-b border-stone-100 px-5 py-4 dark:border-slate-800"><div><h2 class="font-semibold">Movimentacoes recentes</h2><p class="text-xs text-stone-400">Ultimos registros do fluxo de caixa</p></div><Link :href="route('transactions.index')" class="text-xs font-bold text-brand-600">Ver todas</Link></header>
        <div v-if="recentTransactions.length">
          <div v-for="transaction in recentTransactions" :key="transaction.id" class="flex items-center gap-3 border-b border-stone-100 px-5 py-4 last:border-0 dark:border-slate-800">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="transaction.type === 'income' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50' : transaction.type === 'transfer_out' ? 'bg-brand-50 text-brand-600 dark:bg-brand-950/50' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/50'"><ArrowDownLeft v-if="transaction.type === 'income'" :size="17" /><ArrowLeftRight v-else-if="transaction.type === 'transfer_out'" :size="17" /><ArrowUpRight v-else :size="17" /></span>
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ transaction.description }}</p><p class="mt-0.5 text-xs text-stone-400">{{ transaction.account }} · {{ formatDate(transaction.date) }}</p></div>
            <p class="text-sm font-bold" :class="transaction.type === 'income' ? 'text-emerald-600' : transaction.type === 'transfer_out' ? 'text-brand-600' : 'text-rose-600'">{{ transaction.type === 'income' ? '+' : transaction.type === 'expense' ? '-' : '' }}{{ formatMoney(transaction.amount, transaction.currency) }}</p>
          </div>
        </div>
        <div v-else class="px-6 py-14 text-center text-sm text-stone-400">Seus primeiros lancamentos aparecerao aqui.</div>
      </article>

      <article class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header><h2 class="font-semibold">Despesas por categoria</h2><p class="text-xs text-stone-400">Distribuicao no mes atual</p></header>
        <VChart v-if="selectedCategoryExpenses.length" class="mt-2 h-72" :option="chartOption" autoresize />
        <div v-else class="grid h-72 place-items-center text-center text-sm text-stone-400">Categorize despesas para visualizar a distribuicao.</div>
      </article>
    </section>

    <section class="mt-6 rounded-3xl border border-stone-200 bg-slate-950 p-6 text-white shadow-sm dark:border-slate-800"><div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-400">Investimentos · {{ selectedCurrency }}</p><h2 class="mt-2 text-xl font-bold">Posicao consolidada</h2><p class="mt-1 text-xs text-slate-400">Valores atuais sem conversao entre moedas.</p></div><Link :href="route('reports.index', { currency: selectedCurrency })" class="text-sm font-bold text-brand-400">Abrir relatorios</Link></div><div v-if="investment" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><div v-for="item in [{ label: 'Custo', value: investment.cost }, { label: 'Valor atual', value: investment.current_value }, { label: 'Resultado de mercado', value: investment.market_return }, { label: 'Proventos liquidos', value: investment.net_income }]" :key="item.label" class="rounded-2xl bg-slate-900 p-4"><p class="text-xs text-slate-500">{{ item.label }}</p><p class="mt-2 text-lg font-bold">{{ formatMoney(item.value, selectedCurrency) }}</p></div></div><p v-else class="mt-6 text-sm text-slate-500">Nenhuma carteira nesta moeda.</p><p v-if="investment?.unpriced_holdings" class="mt-4 text-xs text-amber-400">{{ investment.unpriced_holdings }} posicao(oes) sem cotacao, avaliada(s) pelo custo medio.</p></section>

    <section class="mt-6 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <header class="mb-4 flex items-center justify-between"><div><h2 class="font-semibold">Contas</h2><p class="text-xs text-stone-400">Saldos atualizados por movimentacao</p></div><Link :href="route('accounts.index')" class="text-xs font-bold text-brand-600">Gerenciar</Link></header>
      <div v-if="accounts.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="account in accounts" :key="account.id" class="flex items-center gap-3 rounded-2xl bg-stone-50 p-4 dark:bg-slate-950"><span class="grid h-10 w-10 place-items-center rounded-xl bg-white text-brand-600 shadow-sm dark:bg-slate-900"><Landmark :size="18" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ account.name }}</p><p class="text-xs text-stone-400">{{ account.currency }}</p></div><p class="text-sm font-bold">{{ formatMoney(account.balance ?? account.initial_balance, account.currency) }}</p></div>
      </div>
      <p v-else class="py-8 text-center text-sm text-stone-400">Cadastre uma conta para iniciar seu painel.</p>
    </section>
  </AuthenticatedLayout>
</template>
