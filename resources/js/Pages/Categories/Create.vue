<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Category, Option } from '@/types/finance';
import { Head } from '@inertiajs/vue3';
import CategoryForm from './Partials/CategoryForm.vue';

defineProps<{ types: Option[]; parentOptions: Category[] }>();
</script>

<template>
  <Head title="Nova categoria" />
  <AuthenticatedLayout>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Categorias</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Nova categoria</h1>
    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">Defina como este grupo aparecera nos relatorios.</p>
    <CategoryForm :types="types" :parent-options="parentOptions" />
  </AuthenticatedLayout>
</template>
