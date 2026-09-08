<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Option } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import GoalForm from './Partials/GoalForm.vue';

type AccountOption = { id: number; name: string; currency: string };
type Goal = { id: number; name: string; description: string | null; target_amount: string; currency: string; account_id: number | null; target_date: string };
defineProps<{ goal: Goal; accounts: AccountOption[]; currencies: Option[] }>();
</script>

<template>
  <Head title="Editar meta" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Planejamento</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Editar meta</h1>
    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Ajuste o valor, prazo ou conta mantendo o historico.</p>
    <GoalForm :goal="goal" :accounts="accounts" :currencies="currencies" />
  </AuthenticatedLayout>
</template>