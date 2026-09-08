<script setup lang="ts">
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
    <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Planejamento</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Metas</h1><p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Transforme objetivos em economia com progresso mensal.</p></div>
      <Link :href="route('goals.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700"><Plus :size="18" />Nova meta</Link>
    </section>

    <div v-if="goals.length" class="mt-8 grid gap-4 lg:grid-cols-2">
      <article v-for="goal in goals" :key="goal.id" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start gap-3"><span class="mt-1 rounded-lg bg-brand-50 p-2 text-brand-600 dark:bg-brand-950"><Target :size="16" /></span><div class="min-w-0 flex-1"><h2 class="font-semibold">{{ goal.name }}</h2><p class="mt-0.5 text-xs text-stone-400">{{ scope(goal) }} &middot; prazo {{ formatDate(goal.target_date) }}</p></div><Link :href="route('goals.edit', goal.id)" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-brand-600 dark:hover:bg-slate-800"><Pencil :size="15" /></Link><button class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-rose-600 dark:hover:bg-slate-800" @click="remove(goal)"><Trash2 :size="15" /></button></div>
        <p v-if="goal.description" class="mt-3 text-sm text-stone-500 dark:text-slate-400">{{ goal.description }}</p>
        <div class="mt-6 flex items-end justify-between"><div><p class="text-xs text-stone-400">Guardado</p><p class="mt-1 text-xl font-bold">{{ formatMoney(goal.saved_amount, goal.currency) }}</p></div><p class="text-sm font-semibold text-stone-500">de {{ formatMoney(goal.target_amount, goal.currency) }}</p></div>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"><div class="h-full rounded-full transition-all" :class="goal.is_achieved ? 'bg-emerald-500' : 'bg-brand-500'" :style="{ width: `${percentage(goal)}%` }" /></div>
        <div class="mt-2 flex items-center justify-between text-xs"><p class="font-semibold text-stone-500">{{ remainingLabel(goal) }}</p><p class="font-bold" :class="goal.is_achieved ? 'text-emerald-600' : 'text-stone-400'">{{ percentage(goal).toFixed(1) }}%</p></div>
      </article>
    </div>
    <div v-else class="mt-8 rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900"><Target :size="36" class="mx-auto text-stone-300 dark:text-slate-600" /><h2 class="mt-4 text-lg font-semibold">Trace suas metas de economia</h2><p class="mt-1 text-sm text-stone-500">Defina um valor, um prazo e acompanhe o progresso pelo saldo das suas contas.</p></div>
  </AuthenticatedLayout>
</template>