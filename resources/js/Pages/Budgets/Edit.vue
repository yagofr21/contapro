<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Option } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import BudgetForm from './Partials/BudgetForm.vue';

type CategoryOption = { id: number; name: string; color: string | null };
type Budget = { id: number; category_id: number; limit_amount: string; period: string; starts_on: string; ends_on: string | null };
defineProps<{ budget: Budget; categories: CategoryOption[]; periods: Option[] }>();
</script>

<template>
  <Head title="Editar orcamento" /><AuthenticatedLayout><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Planejamento</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Editar orcamento</h1><p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Ajuste o limite ou periodo mantendo o historico.</p><BudgetForm :budget="budget" :categories="categories" :periods="periods" /></AuthenticatedLayout>
</template>
