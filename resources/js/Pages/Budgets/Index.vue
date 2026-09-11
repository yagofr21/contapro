<script setup lang="ts">
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
    <PageHeader kicker="Planejamento" title="Orcamentos" subtitle="Transforme limites em decisoes visiveis durante o mes.">
      <template #actions>
        <Link :href="route('budgets.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Novo orcamento</Link>
      </template>
    </PageHeader>

    <div v-if="budgets.length" class="mt-8 grid gap-4 lg:grid-cols-2">
      <Card v-for="budget in budgets" :key="budget.id" :title="budget.category_name" :subtitle="periodLabel(budget)">
        <template #actions>
          <Link :href="route('budgets.edit', budget.id)" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-brand-600 dark:hover:bg-slate-800"><Pencil :size="15" /></Link>
          <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" @click="remove(budget)"><Trash2 :size="15" /></button>
        </template>
        <div class="p-5">
          <div class="flex items-end justify-between"><div><p class="text-xs text-stone-400">Utilizado</p><p class="mt-1 text-xl font-bold">{{ formatMoney(budget.spent) }}</p></div><p class="text-sm font-semibold text-stone-500">de {{ formatMoney(budget.limit_amount) }}</p></div>
          <div class="mt-4 h-2 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"><div class="h-full rounded-full transition-all" :class="percentage(budget) >= 100 ? 'bg-rose-500' : percentage(budget) >= 80 ? 'bg-amber-500' : 'bg-brand-500'" :style="{ width: `${percentage(budget)}%` }" /></div>
          <p class="mt-2 text-right text-xs font-semibold" :class="percentage(budget) >= 100 ? 'text-rose-600' : 'text-stone-400'">{{ percentage(budget).toFixed(0) }}%</p>
        </div>
      </Card>
    </div>
    <EmptyState v-else class="mt-8" :icon="Gauge" title="Planeje antes de gastar" description="Crie limites para suas categorias de despesa." />
  </AuthenticatedLayout>
</template>
