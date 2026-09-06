<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Broker } from '@/types/investment';
import { Head } from '@inertiajs/vue3';
import BrokerForm from './Partials/BrokerForm.vue';
defineProps<{ broker: Broker }>();
</script>

<template><Head title="Editar corretora" /><AuthenticatedLayout><p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Investimentos</p><h1 class="mt-2 text-3xl font-bold">Editar corretora</h1><BrokerForm :broker="broker" /></AuthenticatedLayout></template>
