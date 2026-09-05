<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Category } from '@/types/finance';
import { Head, Link } from '@inertiajs/vue3';
import TransactionForm from './Partials/TransactionForm.vue';

type AccountOption = { id: number; name: string; currency: string };
defineProps<{ accounts: AccountOption[]; categories: Category[] }>();
</script>

<template>
  <Head title="Novo lancamento" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Fluxo de caixa</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Novo lancamento</h1>
    <p v-if="accounts.length" class="mt-2 text-sm text-stone-500 dark:text-slate-400">Registre uma receita, despesa ou transferencia entre contas.</p>
    <div v-else class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-200">Cadastre uma conta antes de criar lancamentos. <Link :href="route('accounts.create')" class="font-bold underline">Criar conta</Link></div>
    <TransactionForm :accounts="accounts" :categories="categories" />
  </AuthenticatedLayout>
</template>
