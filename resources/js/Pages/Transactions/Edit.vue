<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Category, Transaction } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import TransactionForm from './Partials/TransactionForm.vue';

type AccountOption = { id: number; name: string; currency: string };
defineProps<{ transaction: Transaction; accounts: AccountOption[]; categories: Category[] }>();
</script>

<template>
  <Head title="Editar lancamento" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Fluxo de caixa</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Editar lancamento</h1>
    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Transferencias mantem as duas pontas sincronizadas automaticamente.</p>
    <TransactionForm :transaction="transaction" :accounts="accounts" :categories="categories" />
  </AuthenticatedLayout>
</template>
