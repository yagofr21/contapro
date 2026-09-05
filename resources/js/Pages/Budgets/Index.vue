<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';
import { Gauge, Pencil, Plus, Trash2 } from '@lucide/vue';

type Budget = {
    id: number;
    category_id: number;
    category_name: string;
    category_color: string | null;
    limit_amount: string;
    spent: string;
    period: string;
    starts_on: string;
    ends_on: string | null;
};

defineProps<{ budgets: Budget[] }>();

const percentage = (budget: Budget) => Math.min((Number(budget.spent) / Number(budget.limit_amount)) * 100, 100);
const periodLabel = (budget: Budget) => {
    if (budget.period === 'monthly') return `Mes iniciado em ${formatDate(budget.starts_on)}`;
    if (budget.period === 'yearly') return `Ano iniciado em ${formatDate(budget.starts_on)}`;
    return `${formatDate(budget.starts_on)} a ${budget.ends_on ? formatDate(budget.ends_on) : ''}`;
};
const remove = (budget: Budget) => {
    if (confirm(`Remover o orcamento de ${budget.category_name}?`)) {
        router.delete(route('budgets.destroy', budget.id));
    }
};
</script>

<template>
  <Head title="Orcamentos" />
  <AuthenticatedLayout>
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Planejamento</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Orcamentos</h1><p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Transforme limites em decisoes visiveis durante o mes.</p></div>
      <Link :href="route('budgets.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Novo orcamento</Link>
    </section>

    <div v-if="budgets.length" class="mt-8 grid gap-4 lg:grid-cols-2">
      <article v-for="budget in budgets" :key="budget.id" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start gap-3"><span class="mt-1 h-3 w-3 rounded-full ring-4 ring-stone-100 dark:ring-slate-800" :style="{ backgroundColor: budget.category_color ?? '#94a3b8' }" /><div class="min-w-0 flex-1"><h2 class="font-semibold">{{ budget.category_name }}</h2><p class="mt-0.5 text-xs text-stone-400">{{ periodLabel(budget) }}</p></div><Link :href="route('budgets.edit', budget.id)" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-brand-600 dark:hover:bg-slate-800"><Pencil :size="15" /></Link><button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" @click="remove(budget)"><Trash2 :size="15" /></button></div>
        <div class="mt-6 flex items-end justify-between"><div><p class="text-xs text-stone-400">Utilizado</p><p class="mt-1 text-xl font-bold">{{ formatMoney(budget.spent) }}</p></div><p class="text-sm font-semibold text-stone-500">de {{ formatMoney(budget.limit_amount) }}</p></div>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"><div class="h-full rounded-full transition-all" :class="percentage(budget) >= 100 ? 'bg-rose-500' : percentage(budget) >= 80 ? 'bg-amber-500' : 'bg-brand-500'" :style="{ width: `${percentage(budget)}%` }" /></div>
        <p class="mt-2 text-right text-xs font-semibold" :class="percentage(budget) >= 100 ? 'text-rose-600' : 'text-stone-400'">{{ percentage(budget).toFixed(0) }}%</p>
      </article>
    </div>
    <div v-else class="mt-8 rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900"><Gauge :size="36" class="mx-auto text-stone-300 dark:text-slate-600" /><h2 class="mt-4 text-lg font-semibold">Planeje antes de gastar</h2><p class="mt-1 text-sm text-stone-500">Crie limites para suas categorias de despesa.</p></div>
  </AuthenticatedLayout>
</template>
