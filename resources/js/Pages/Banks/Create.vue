<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import BankForm from './Partials/BankForm.vue';
</script>

<template>
  <Head title="Novo banco" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700 dark:text-brand-300">Financeiro</p>
    <h1 class="mt-2 text-3xl font-bold">Novo banco</h1>
    <p class="mt-2 text-sm text-stone-600 dark:text-slate-400">Adicione uma instituição ao catálogo global de identificação visual.</p>
    <BankForm />
  </AuthenticatedLayout>
</template>