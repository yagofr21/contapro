<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { BankOption } from '@/lib/banks';
import type { Option } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import AccountForm from './Partials/AccountForm.vue';

defineProps<{ types: Option[]; currencies: Option[]; banks: BankOption[] }>();
</script>

<template>
  <Head title="Nova conta" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Contas</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Nova conta</h1>
    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Cadastre o ponto de partida para organizar seus lancamentos.</p>
    <AccountForm :types="types" :currencies="currencies" :banks="banks" />
  </AuthenticatedLayout>
</template>
