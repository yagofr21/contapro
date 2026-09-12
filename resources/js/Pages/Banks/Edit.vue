<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Bank } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import BankForm from './Partials/BankForm.vue';

defineProps<{ bank: Bank }>();
</script>

<template>
  <Head title="Editar banco" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Financeiro</p>
    <h1 class="mt-2 text-3xl font-bold">Editar banco</h1>
    <BankForm :bank="bank" />
  </AuthenticatedLayout>
</template>