<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Option } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import GoalForm from './Partials/GoalForm.vue';

type AccountOption = { id: number; name: string; currency: string };
defineProps<{ accounts: AccountOption[]; currencies: Option[] }>();
</script>

<template>
  <Head title="Nova meta" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Planejamento</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Nova meta</h1>
    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Defina o que deseja economizar e acompanhe o progresso mês a mes.</p>
    <GoalForm :accounts="accounts" :currencies="currencies" />
  </AuthenticatedLayout>
</template>