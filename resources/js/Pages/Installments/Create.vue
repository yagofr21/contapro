<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Category, Option } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import InstallmentForm from './Partials/InstallmentForm.vue';

type AccountOption = { id: number; name: string; currency: string };
defineProps<{ accounts: AccountOption[]; categories: Category[]; types?: Option[] }>();
</script>

<template>
  <Head title="Novas parcelas" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Agenda</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Novas parcelas</h1>
    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Divida um valor em lançamentos mensais sequenciais.</p>
    <InstallmentForm :accounts="accounts" :categories="categories" :types="types" />
  </AuthenticatedLayout>
</template>
