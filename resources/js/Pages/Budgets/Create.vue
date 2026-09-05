<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Option } from '@/types/finance';
import { Head, Link } from '@inertiajs/vue3';
import BudgetForm from './Partials/BudgetForm.vue';

type CategoryOption = { id: number; name: string; color: string | null };
defineProps<{ categories: CategoryOption[]; periods: Option[] }>();
</script>

<template>
  <Head title="Novo orcamento" /><AuthenticatedLayout><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Planejamento</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Novo orcamento</h1><p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Defina um limite e acompanhe o consumo no periodo.</p><div v-if="!categories.length" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-200">Crie uma categoria de despesa primeiro. <Link :href="route('categories.create')" class="font-bold underline">Criar categoria</Link></div><BudgetForm :categories="categories" :periods="periods" /></AuthenticatedLayout>
</template>
