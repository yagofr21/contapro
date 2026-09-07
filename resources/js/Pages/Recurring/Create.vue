<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Category, Option } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import RecurringForm from './Partials/RecurringForm.vue';

type AccountOption = { id: number; name: string; currency: string };
defineProps<{ accounts: AccountOption[]; categories: Category[]; frequencies?: Option[]; types?: Option[] }>();
</script>

<template>
  <Head title="Nova recorrencia" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Agenda</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Nova recorrencia</h1>
    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Lancamentos que se repetem automaticamente na data agendada.</p>
    <RecurringForm :accounts="accounts" :categories="categories" :frequencies="frequencies" :types="types" />
  </AuthenticatedLayout>
</template>
