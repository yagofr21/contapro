<script setup lang="ts">
import CashFlowValues from '@/Components/CashFlowValues.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SelectInput from '@/Components/SelectInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useChartPalette } from '@/lib/chartTheme';
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

const chartPalette = useChartPalette();
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
const hasMonthlyData = computed(() => props.monthly.some((month) => Number(month.income) !== 0 || Number(month.expenses) !== 0));
const totalCategoryExpenses = computed(() => props.categories.reduce((sum, category) => sum + Number(category.total), 0));
const totalAllocation = computed(() => props.allocation.reduce((sum, item) => sum + Number(item.value), 0));
const cashFlowOption = computed(() => ({
    tooltip: { confine: true, backgroundColor: chartPalette.value.surface, textStyle: { color: chartPalette.value.text }, trigger: 'axis', axisPointer: { type: 'shadow' }, valueFormatter: (value: number) => formatMoney(String(value), props.filters.currency) },
    legend: { top: 0, right: 0, icon: 'circle', itemWidth: 10, itemHeight: 10, textStyle: { color: chartPalette.value.text, fontSize: 11 } },
    grid: { left: 4, right: 4, top: 30, bottom: 22, containLabel: true },
    xAxis: { type: 'category', data: props.monthly.map((item) => formatMonth(item.month)), axisLabel: { color: chartPalette.value.text, interval: 0, fontSize: 10 }, axisLine: { lineStyle: { color: chartPalette.value.grid } }, axisTick: { show: false } },
    yAxis: { name: props.filters.currency, type: 'value', axisLabel: { color: chartPalette.value.text, fontSize: 10, formatter: (value: number) => (Math.abs(value) >= 1000 ? `${value / 1000}k` : String(value)) }, splitLine: { lineStyle: { color: chartPalette.value.grid } } },
    series: [
        { name: 'Receitas', type: 'bar', barMaxWidth: 14, data: props.monthly.map((item) => Number(item.income)), itemStyle: { color: chartPalette.value.income, borderRadius: [6, 6, 0, 0] } },
        { name: 'Despesas', type: 'bar', barMaxWidth: 14, data: props.monthly.map((item) => Number(item.expenses)), itemStyle: { color: chartPalette.value.expense, borderRadius: [6, 6, 0, 0] } },
    ],
}));
const categoryOption = computed(() => ({
    tooltip: { confine: true, backgroundColor: chartPalette.value.surface, textStyle: { color: chartPalette.value.text }, trigger: 'item', valueFormatter: (value: number) => formatMoney(String(value), props.filters.currency) },
    series: [{ type: 'pie', radius: ['48%', '72%'], center: ['50%', '50%'], label: { show: false }, itemStyle: { borderRadius: 6, borderColor: chartPalette.value.surface, borderWidth: 3 }, data: props.categories.map((item) => ({ name: item.name, value: Number(item.total), itemStyle: { color: item.color } })) }],
}));
const sortedCategories = computed(() => [...props.categories].sort((a, b) => Number(b.total) - Number(a.total)));
const typeLabels: Record<string, string> = { stock: 'Ações', fii: 'FIIs', etf: 'ETFs', bond: 'Renda fixa', reit: 'REITs', crypto: 'Cripto' };
const allocationOption = computed(() => ({
    tooltip: { confine: true, backgroundColor: chartPalette.value.surface, textStyle: { color: chartPalette.value.text }, trigger: 'item', valueFormatter: (value: number) => formatMoney(String(value), props.filters.currency) },
    legend: { bottom: 0, icon: 'circle', textStyle: { color: chartPalette.value.text } },
    series: [{ type: 'pie', radius: ['45%', '70%'], center: ['50%', '42%'], label: { show: false }, data: props.allocation.map((item) => ({ name: typeLabels[item.type] ?? item.type, value: Number(item.value) })) }],
}));
</script>

<template>
  <Head title="Relatórios" />
  <AuthenticatedLayout>
    <PageHeader kicker="Análise financeira" title="Relatórios" subtitle="Fluxo de caixa e investimentos sem misturar moedas.">
      <template #actions>
        <a :href="exportUrl" class="inline-flex items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-600 shadow-sm hover:border-brand-300 hover:text-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"><Download :size="17" />Exportar CSV</a>
      </template>
    </PageHeader>

    <form class="mt-7 grid gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:gap-4 dark:border-slate-800 dark:bg-slate-900 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_160px_auto] lg:items-end" @submit.prevent="submit">
      <label><span class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-400">De</span><input v-model="form.from" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-3 text-sm dark:border-slate-700 dark:bg-slate-950" /></label>
      <label><span class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-400">Até</span><input v-model="form.to" type="date" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-3 text-sm dark:border-slate-700 dark:bg-slate-950" /></label>
      <label><span class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-400">Moeda</span><SelectInput v-model="form.currency"><option v-for="currency in currencies" :key="currency" :value="currency">{{ currency }}</option></SelectInput></label>
      <button :disabled="form.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50 sm:w-auto"><Filter :size="17" />Aplicar</button>
    </form>

    <section class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3"><article v-for="item in [{ label: 'Receitas', value: summary.income, tone: 'text-emerald-700 dark:text-emerald-300' }, { label: 'Despesas', value: summary.expenses, tone: 'text-rose-700 dark:text-rose-300' }, { label: 'Fluxo líquido', value: summary.net, tone: Number(summary.net) >= 0 ? 'text-brand-700 dark:text-brand-300' : 'text-rose-700 dark:text-rose-300' }, { label: 'Proventos', value: summary.net_investment_income, tone: 'text-amber-600' }, { label: 'Resultado realizado', value: summary.realized_profit_loss, tone: Number(summary.realized_profit_loss) >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }]" :key="item.label" class="min-w-0 rounded-2xl border border-stone-200 bg-white p-3 shadow-sm sm:p-4 dark:border-slate-800 dark:bg-slate-900"><p class="text-xs text-stone-600 dark:text-slate-400">{{ item.label }}</p><p class="financial-value mt-2 text-xl font-bold" :class="item.tone">{{ formatMoney(item.value, filters.currency) }}</p></article><article class="min-w-0 rounded-2xl border border-stone-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900 sm:p-4"><p class="text-xs text-stone-600 dark:text-slate-400">Lançamentos</p><p class="mt-1 text-base font-bold sm:text-lg">{{ summary.transaction_count }}</p></article></section>

    <Card class="mt-6" title="Fluxo mensal" subtitle="Receitas e despesas no período">
      <div class="p-4 sm:p-5">
        <div v-if="hasMonthlyData" class="h-64 sm:h-80"><VChart aria-label="Receitas e despesas por mês" :option="cashFlowOption" autoresize style="width: 100%; height: 100%;" /></div>
        <EmptyState v-else title="Sem movimentações no período" description="Escolha outro período ou registre um lançamento para acompanhar seu fluxo de caixa." />
        <CashFlowValues v-if="hasMonthlyData" :months="monthly" :currency="filters.currency" />
        <ul v-if="hasMonthlyData" class="mt-3 flex flex-wrap justify-center gap-4 border-t border-stone-100 pt-3 text-xs font-semibold text-stone-600 sm:hidden dark:border-slate-800 dark:text-slate-400">
          <li class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500" />Receitas: <span class="text-emerald-700 dark:text-emerald-300 ">{{ formatMoney(summary.income, filters.currency) }}</span></li>
          <li class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-rose-500" />Despesas: <span class="text-rose-700 dark:text-rose-300 ">{{ formatMoney(summary.expenses, filters.currency) }}</span></li>
        </ul>
      </div>
    </Card>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
      <Card title="Despesas por categoria" subtitle="Distribuição do período"><div class="p-4 sm:p-5"><div v-if="sortedCategories.length"><div class="h-48 sm:h-56"><VChart :option="categoryOption" autoresize style="width: 100%; height: 100%;" /></div><ul class="mt-3 space-y-2.5 border-t border-stone-100 pt-3 dark:border-slate-800"><li v-for="category in sortedCategories" :key="category.name" class="flex items-center gap-2.5"><span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: category.color }" /><span class="min-w-0 flex-1 truncate text-sm text-stone-600 dark:text-slate-300">{{ category.name }}</span><span class="whitespace-nowrap text-sm font-bold">{{ formatMoney(category.total, filters.currency) }}</span><span class="w-10 shrink-0 text-right text-xs text-stone-600 dark:text-slate-400">{{ totalCategoryExpenses > 0 ? Math.round(Number(category.total) / totalCategoryExpenses * 100) : 0 }}%</span></li></ul></div><EmptyState v-else title="Sem despesas no período." /></div></Card>
      <Card title="Alocação atual" :subtitle="`Posição por tipo de ativo em ${filters.currency}`"><div class="p-4 sm:p-5"><template v-if="allocation.length"><div class="h-48 sm:h-72"><VChart :option="allocationOption" autoresize style="width: 100%; height: 100%;" /></div><dl class="mt-4 divide-y divide-stone-100 dark:divide-slate-800"><div v-for="item in allocation" :key="item.type" class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm"><dt>{{ typeLabels[item.type] ?? item.type }}</dt><dd class="financial-value font-semibold">{{ formatMoney(item.value, filters.currency) }}<span class="ml-3 text-xs font-normal text-stone-600 dark:text-slate-400">{{ totalAllocation > 0 ? Math.round(Number(item.value) / totalAllocation * 100) : 0 }}%</span></dd></div></dl></template><EmptyState v-else title="Sem investimentos nesta moeda." /><p v-if="investmentSummary?.unpriced_holdings" class="mt-2 text-xs text-amber-600">{{ investmentSummary.unpriced_holdings }} posição(ões) sem cotação atual.</p></div></Card>
    </section>

    <section v-if="investmentSummary" class="mt-6 grid grid-cols-2 gap-3 rounded-2xl bg-slate-950 p-5 text-white sm:gap-4 sm:p-6 lg:grid-cols-4">
      <div class="col-span-2 sm:col-span-2 lg:col-span-1"><p class="text-xs uppercase tracking-[0.18em] text-brand-400">Posição atual</p><p class="mt-2 text-xl font-bold sm:text-2xl">{{ formatMoney(investmentSummary.current_value, filters.currency) }}</p></div>
      <div><p class="text-xs text-slate-400">Não realizado</p><p class="mt-1 text-base font-bold sm:text-lg" :class="Number(investmentSummary.market_return) >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ formatMoney(investmentSummary.market_return, filters.currency) }}</p></div>
      <div><p class="text-xs text-slate-400">Resultado realizado</p><p class="mt-1 text-base font-bold sm:text-lg" :class="Number(investmentSummary.realized_profit_loss) >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ formatMoney(investmentSummary.realized_profit_loss, filters.currency) }}</p><p class="mt-1 text-xs text-slate-400">Proventos: {{ formatMoney(investmentSummary.net_income, filters.currency) }}</p></div>
      <div class="col-span-2 sm:col-span-1 lg:text-right"><p class="text-xs text-slate-400">Retorno total</p><p class="mt-1 text-base font-bold sm:text-lg" :class="Number(investmentSummary.total_return) >= 0 ? 'text-emerald-400' : 'text-rose-400'"><TrendingUp :size="17" class="mr-1 inline" />{{ formatMoney(investmentSummary.total_return, filters.currency) }}</p></div>
    </section>
  </AuthenticatedLayout>
</template>
