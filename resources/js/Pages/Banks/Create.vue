<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import BankForm from './Partials/BankForm.vue';
</script>

<template>
  <Head title="Novo banco" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Financeiro</p>
    <h1 class="mt-2 text-3xl font-bold">Novo banco</h1>
    <p class="mt-2 text-sm text-stone-500">Adicione uma instituicao ao catalogo global de identificacao visual.</p>
    <BankForm />
  </AuthenticatedLayout>
</template>