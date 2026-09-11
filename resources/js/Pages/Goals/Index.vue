<script setup lang="ts">
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Target, Trash2 } from '@lucide/vue';

type Goal = {
    id: number;
    name: string;
    description: string | null;
    target_amount: string;
    currency: string;
    account_id: number | null;
    account_name: string | null;
    target_date: string;
    saved_amount: string;
    progress: string;
    remaining: string;
    is_achieved: boolean;
};

defineProps<{ goals: Goal[] }>();

const percentage = (goal: Goal) => Number(goal.progress);
const scope = (goal: Goal) =>
    goal.account_name ? `Conta: ${goal.account_name}` : 'Todas as contas na moeda';
const remainingLabel = (goal: Goal) =>
    goal.is_achieved ? 'Meta atingida' : `Faltam ${formatMoney(goal.remaining, goal.currency)}`;
const remove = (goal: Goal) => {
    if (confirm(`Remover a meta "${goal.name}"?`)) {
        router.delete(route('goals.destroy', goal.id));
    }
};
</script>

<template>
  <Head title="Metas" />
  <AuthenticatedLayout>
    <PageHeader kicker="Planejamento" title="Metas" subtitle="Transforme objetivos em economia com progresso mensal.">
      <template #actions>
        <Link :href="route('goals.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Nova meta</Link>
      </template>
    </PageHeader>

    <div v-if="goals.length" class="mt-8 grid gap-4 lg:grid-cols-2">
      <Card v-for="goal in goals" :key="goal.id" :title="goal.name" :subtitle="`${scope(goal)} · prazo ${formatDate(goal.target_date)}`">
        <template #actions>
          <Link :href="route('goals.edit', goal.id)" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-brand-600 dark:hover:bg-slate-800"><Pencil :size="15" /></Link>
          <button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" @click="remove(goal)"><Trash2 :size="15" /></button>
        </template>
        <div class="p-5">
          <p v-if="goal.description" class="text-sm text-stone-500 dark:text-slate-400">{{ goal.description }}</p>
          <div class="mt-5 flex items-end justify-between"><div><p class="text-xs text-stone-400">Guardado</p><p class="mt-1 text-xl font-bold">{{ formatMoney(goal.saved_amount, goal.currency) }}</p></div><p class="text-sm font-semibold text-stone-500">de {{ formatMoney(goal.target_amount, goal.currency) }}</p></div>
          <div class="mt-4 h-2 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"><div class="h-full rounded-full transition-all" :class="goal.is_achieved ? 'bg-emerald-500' : 'bg-brand-500'" :style="{ width: `${percentage(goal)}%` }" /></div>
          <div class="mt-2 flex items-center justify-between text-xs"><p class="font-semibold text-stone-500">{{ remainingLabel(goal) }}</p><p class="font-bold" :class="goal.is_achieved ? 'text-emerald-600' : 'text-stone-400'">{{ percentage(goal).toFixed(1) }}%</p></div>
        </div>
      </Card>
    </div>
    <EmptyState v-else class="mt-8" :icon="Target" title="Trace suas metas de economia" description="Defina um valor, um prazo e acompanhe o progresso pelo saldo das suas contas." />
  </AuthenticatedLayout>
</template>
