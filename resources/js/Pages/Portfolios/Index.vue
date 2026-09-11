<script setup lang="ts">
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatMoney } from '@/lib/format';
import type { PortfolioSummary } from '@/types/investment';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, PieChart, Plus, Trash2, TrendingDown, TrendingUp } from '@lucide/vue';

defineProps<{ portfolios: PortfolioSummary[] }>();
const remove = (portfolio: PortfolioSummary) => {
    if (confirm(`Remover a carteira "${portfolio.name}" e todo o historico dela?`)) router.delete(route('portfolios.destroy', portfolio.id));
};
</script>

<template>
  <Head title="Carteiras" /><AuthenticatedLayout>
    <PageHeader kicker="Patrimonio" title="Carteiras de investimentos" subtitle="Posicoes, custo medio e resultado em um unico retrato.">
      <template #actions>
        <Link :href="route('portfolios.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20"><Plus :size="18" />Nova carteira</Link>
      </template>
    </PageHeader>
    <div v-if="portfolios.length" class="mt-8 grid gap-5 lg:grid-cols-2">
      <article v-for="portfolio in portfolios" :key="portfolio.id" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950/50"><PieChart :size="21" /></span><button class="rounded-lg p-2 text-stone-300 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30" @click="remove(portfolio)"><Trash2 :size="16" /></button></div><div class="mt-5"><h2 class="text-lg font-bold">{{ portfolio.name }}</h2><p class="text-xs text-stone-400">{{ portfolio.holdings_count }} posicoes · {{ portfolio.currency }}</p></div><div class="mt-6 grid grid-cols-2 gap-4"><div><p class="text-xs text-stone-400">Valor atual</p><p class="mt-1 text-xl font-bold">{{ formatMoney(portfolio.current_value, portfolio.currency) }}</p></div><div><p class="text-xs text-stone-400">Retorno total</p><p class="mt-1 flex items-center gap-1 text-xl font-bold" :class="Number(portfolio.total_return) >= 0 ? 'text-emerald-600' : 'text-rose-600'"><TrendingUp v-if="Number(portfolio.total_return) >= 0" :size="17" /><TrendingDown v-else :size="17" />{{ formatMoney(portfolio.total_return, portfolio.currency) }}</p></div></div><p class="mt-4 text-xs text-stone-400">Realizado {{ formatMoney(portfolio.realized_profit_loss, portfolio.currency) }} · Proventos {{ formatMoney(portfolio.net_income, portfolio.currency) }}</p><Link :href="route('portfolios.show', portfolio.id)" class="mt-4 flex items-center justify-between border-t border-stone-100 pt-4 text-sm font-semibold text-brand-600 dark:border-slate-800">Abrir carteira <ArrowRight :size="17" /></Link></article>
    </div>
    <EmptyState v-else class="mt-8" :icon="PieChart" title="Sua primeira carteira" description="Crie uma carteira e registre a primeira compra." />
  </AuthenticatedLayout>
</template>
