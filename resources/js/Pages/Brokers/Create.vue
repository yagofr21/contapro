<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import BrokerForm from './Partials/BrokerForm.vue';
</script>

<template><Head title="Nova corretora" /><AuthenticatedLayout><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700 dark:text-brand-300">Investimentos</p><h1 class="mt-2 text-3xl font-bold">Nova corretora</h1><p class="mt-2 text-sm text-stone-600 dark:text-slate-400">Identifique onde cada operação foi executada.</p><BrokerForm /></AuthenticatedLayout></template>
