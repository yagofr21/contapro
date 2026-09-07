<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Category, Option } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import RecurringForm from './Partials/RecurringForm.vue';

type AccountOption = { id: number; name: string; currency: string };
type Schedule = {
    id: number;
    type: string;
    account_id: number;
    destination_account_id: number | null;
    category_id: number | null;
    amount: string;
    frequency: string;
    starts_on: string;
    ends_on: string | null;
    description: string | null;
    is_active: boolean;
};
defineProps<{ schedule: Schedule; accounts: AccountOption[]; categories: Category[]; frequencies?: Option[]; types?: Option[] }>();
</script>

<template>
  <Head title="Editar recorrencia" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Agenda</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Editar recorrencia</h1>
    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Ajuste a regra mantendo o historico ja lancado.</p>
    <RecurringForm :schedule="schedule" :accounts="accounts" :categories="categories" :frequencies="frequencies" :types="types" />
  </AuthenticatedLayout>
</template>
