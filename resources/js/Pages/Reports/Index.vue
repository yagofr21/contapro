<script setup lang="ts">
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SelectInput from '@/Components/SelectInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatMoney, formatMonth } from '@/lib/format';
import { BarChart, PieChart } from 'echarts/charts';
import { GridComponent, LegendComponent, TooltipComponent } from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { Head, useForm } from '@inertiajs/vue3';
import { Download, Filter, TrendingUp } from '@lucide/vue';
import { computed } from 'vue';
import VChart from 'vue-echarts';

use([CanvasRenderer, BarChart, PieChart, GridComponent, TooltipComponent, LegendComponent]);

type Filters = { from: string; to: string; currency: string };
type Summary = { income: string; expenses: string; net: string; transaction_count: number; net_investment_income: string; realized_profit_loss: string; net_investment_result: string };
type Monthly = { month: string; income: string; expenses: string };
type Category = { name: string; color: string; total: string };
type InvestmentSummary = { current_value: string; market_return: string; realized_profit_loss: string; net_income: string; total_return: string; unpriced_holdings: number };
type Allocation = { type: string; value: string };

const props = defineProps<{
    filters: Filters;
    currencies: string[];
    summary: Summary;
    monthly: Monthly[];
    categories: Category[];
    investmentSummary: InvestmentSummary | null;
    allocation: Allocation[];
}>();
const form = useForm({ ...props.filters });
const submit = () => form.get(route('reports.index'), { preserveState: true, preserveScroll: true });
const exportUrl = computed(() => route('reports.transactions.export', {
    from: form.from,
    to: form.to,
    currency: form.currency,
}));
const cashFlowOption = computed(() => ({
    tooltip: { trigger: 'axis', valueFormatter: (value: number) => formatMoney(String(value), props.filters.currency) },
    legend: { bottom: 0, textStyle: { color: '#78716c' } },
    grid: { left: 8, right: 8, top: 24, bottom: 44, containLabel: true },
    xAxis: { type: 'category', data: props.monthly.map((item) => formatMonth(item.month)), axisLabel: { color: '#78716c' } },
    yAxis: { type: 'value', axisLabel: { color: '#78716c', formatter: (value: number) => (Math.abs(value) >= 1000 ? `${value / 1000}k` : String(value)) }, splitLine: { lineStyle: { color: '#e7e5e4' } } },
    series: [
        { name: 'Receitas', type: 'bar', barMaxWidth: 14, data: props.monthly.map((item) => Number(item.income)), itemStyle: { color: '#10b981', borderRadius: [6, 6, 0, 0] } },
        { name: 'Despesas', type: 'bar', barMaxWidth: 14, data: props.monthly.map((item) => Number(item.expenses)), itemStyle: { color: '#f43f5e', borderRadius: [6, 6, 0, 0] } },
    ],
}));
const categoryOption = computed(() => ({
    tooltip: { trigger: 'item', valueFormatter: (value: number) => formatMoney(String(value), props.filters.currency) },
    legend: { bottom: 0, icon: 'circle', textStyle: { color: '#78716c' } },
    series: [{ type: 'pie', radius: ['48%', '72%'], center: ['50%', '42%'], label: { show: false }, itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 3 }, data: props.categories.map((item) => ({ name: item.name, value: Number(item.total), itemStyle: { color: item.color } })) }],
}));
const typeLabels: Record<string, string> = { stock: 'Acoes', fii: 'FIIs', etf: 'ETFs', bond: 'Renda fixa', reit: 'REITs', crypto: 'Cripto' };
const allocationOption = computed(() => ({
    tooltip: { trigger: 'item', valueFormatter: (value: number) => formatMoney(String(value), props.filters.currency) },
    legend: { bottom: 0, icon: 'circle', textStyle: { color: '#78716c' } },
    series: [{ type: 'pie', radius: ['45%', '70%'], center: ['50%', '42%'], label: { show: false }, data: props.allocation.map((item) => ({ name: typeLabels[item.type] ?? item.type, value: Number(item.value) })) }],
}));
</script>

<template>
  <Head title="Relatorios" />
  <AuthenticatedLayout>
    <PageHeader kicker="Analise financeira" title="Relatorios" subtitle="Fluxo de caixa e investimentos sem misturar moedas.">
      <template #actions>
        <a :href="exportUrl" class="inline-flex items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-600 shadow-sm hover:border-brand-300 hover:text-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"><Download :size="17" />Exportar CSV</a>
      </template>
    </PageHeader>

    <form class="mt-7 grid gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_160px_auto] lg:items-end" @submit.prevent="submit">
      <label><span class="mb-2 block text-xs font-semibold text-stone-500">De</span><input v-model="form.from" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /></label>
      <label><span class="mb-2 block text-xs font-semibold text-stone-500">Ate</span><input v-model="form.to" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" /></label>
      <label><span class="mb-2 block text-xs font-semibold text-stone-500">Moeda</span><SelectInput v-model="form.currency"><option v-for="currency in currencies" :key="currency" :value="currency">{{ currency }}</option></SelectInput></label>
      <button :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"><Filter :size="17" />Aplicar</button>
    </form>

    <section class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6"><article v-for="item in [{ label: 'Receitas', value: summary.income, tone: 'text-emerald-600' }, { label: 'Despesas', value: summary.expenses, tone: 'text-rose-600' }, { label: 'Fluxo liquido', value: summary.net, tone: Number(summary.net) >= 0 ? 'text-brand-600' : 'text-rose-600' }, { label: 'Proventos', value: summary.net_investment_income, tone: 'text-amber-600' }, { label: 'Resultado realizado', value: summary.realized_profit_loss, tone: Number(summary.realized_profit_loss) >= 0 ? 'text-emerald-600' : 'text-rose-600' }]" :key="item.label" class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-[11px] text-stone-400">{{ item.label }}</p><p class="mt-1.5 text-lg font-bold sm:text-xl" :class="item.tone">{{ formatMoney(item.value, filters.currency) }}</p></article><article class="rounded-2xl bg-slate-950 p-4 text-white"><p class="text-[11px] text-slate-500">Lancamentos</p><p class="mt-1.5 text-lg font-bold sm:text-xl">{{ summary.transaction_count }}</p></article></section>

    <Card class="mt-6" title="Fluxo mensal" subtitle="Receitas e despesas no periodo">
      <div class="p-5"><VChart class="mt-4 h-64 sm:h-80" :option="cashFlowOption" autoresize /></div>
    </Card>

    <section class="mt-6 grid gap-6 xl:grid-cols-2"><Card title="Despesas por categoria" subtitle="Distribuicao do periodo"><div class="p-5"><VChart v-if="categories.length" class="h-56 sm:h-72" :option="categoryOption" autoresize /><EmptyState v-else title="Sem despesas no periodo." /></div></Card><Card title="Alocacao atual" subtitle="Posicao por tipo de ativo em {{ filters.currency }}"><div class="p-5"><VChart v-if="allocation.length" class="h-56 sm:h-72" :option="allocationOption" autoresize /><EmptyState v-else title="Sem investimentos nesta moeda." /><p v-if="investmentSummary?.unpriced_holdings" class="mt-2 text-xs text-amber-600">{{ investmentSummary.unpriced_holdings }} posicao(oes) sem cotacao atual.</p></div></Card></section>

    <section v-if="investmentSummary" class="mt-6 grid gap-5 rounded-3xl bg-slate-950 p-6 text-white sm:grid-cols-2 lg:grid-cols-4"><div><p class="text-xs uppercase tracking-[0.18em] text-brand-400">Posicao atual</p><p class="mt-2 text-2xl font-bold">{{ formatMoney(investmentSummary.current_value, filters.currency) }}</p></div><div><p class="text-xs text-slate-500">Nao realizado</p><p class="mt-1 text-lg font-bold" :class="Number(investmentSummary.market_return) >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ formatMoney(investmentSummary.market_return, filters.currency) }}</p></div><div><p class="text-xs text-slate-500">Resultado realizado</p><p class="mt-1 text-lg font-bold" :class="Number(investmentSummary.realized_profit_loss) >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ formatMoney(investmentSummary.realized_profit_loss, filters.currency) }}</p><p class="mt-1 text-xs text-slate-500">Proventos: {{ formatMoney(investmentSummary.net_income, filters.currency) }}</p></div><div class="lg:text-right"><p class="text-xs text-slate-500">Retorno total</p><p class="mt-1 text-lg font-bold" :class="Number(investmentSummary.total_return) >= 0 ? 'text-emerald-400' : 'text-rose-400'"><TrendingUp :size="17" class="mr-1 inline" />{{ formatMoney(investmentSummary.total_return, filters.currency) }}</p></div></section>
  </AuthenticatedLayout>
</template>
