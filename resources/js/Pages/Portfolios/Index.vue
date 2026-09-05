<script setup lang="ts">
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
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Patrimonio</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Carteiras de investimentos</h1><p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Posicoes, custo medio e resultado em um unico retrato.</p></div><Link :href="route('portfolios.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20"><Plus :size="18" />Nova carteira</Link></section>
    <div v-if="portfolios.length" class="mt-8 grid gap-5 lg:grid-cols-2">
      <article v-for="portfolio in portfolios" :key="portfolio.id" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950/50"><PieChart :size="21" /></span><button class="rounded-lg p-2 text-stone-300 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30" @click="remove(portfolio)"><Trash2 :size="16" /></button></div><div class="mt-5"><h2 class="text-lg font-bold">{{ portfolio.name }}</h2><p class="text-xs text-stone-400">{{ portfolio.holdings_count }} posicoes · {{ portfolio.currency }}</p></div><div class="mt-6 grid grid-cols-2 gap-4"><div><p class="text-xs text-stone-400">Valor atual</p><p class="mt-1 text-xl font-bold">{{ formatMoney(portfolio.current_value, portfolio.currency) }}</p></div><div><p class="text-xs text-stone-400">Resultado</p><p class="mt-1 flex items-center gap-1 text-xl font-bold" :class="Number(portfolio.return) >= 0 ? 'text-emerald-600' : 'text-rose-600'"><TrendingUp v-if="Number(portfolio.return) >= 0" :size="17" /><TrendingDown v-else :size="17" />{{ formatMoney(portfolio.return, portfolio.currency) }}</p></div></div><Link :href="route('portfolios.show', portfolio.id)" class="mt-6 flex items-center justify-between border-t border-stone-100 pt-4 text-sm font-semibold text-brand-600 dark:border-slate-800">Abrir carteira <ArrowRight :size="17" /></Link></article>
    </div>
    <div v-else class="mt-8 rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900"><PieChart :size="36" class="mx-auto text-stone-300" /><h2 class="mt-4 text-lg font-semibold">Sua primeira carteira</h2><p class="mt-1 text-sm text-stone-500">Crie uma carteira e registre a primeira compra.</p></div>
  </AuthenticatedLayout>
</template>
