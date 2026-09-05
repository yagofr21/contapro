<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Account, Option } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import AccountForm from './Partials/AccountForm.vue';

defineProps<{ account: Account; types: Option[]; currencies: Option[] }>();
</script>

<template>
  <Head :title="`Editar ${account.name}`" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Contas</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Editar conta</h1>
    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Atualize os dados sem perder o historico financeiro.</p>
    <AccountForm :account="account" :types="types" :currencies="currencies" />
  </AuthenticatedLayout>
</template>
