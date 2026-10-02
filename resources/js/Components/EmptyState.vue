<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon?: Component;
    title: string;
    description?: string;
}>();
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-solid border-stone-200 bg-white px-6 py-14 text-center dark:border-slate-800 dark:bg-slate-900">
    <span v-if="icon" class="grid h-14 w-14 place-items-center rounded-2xl bg-brand-50 text-brand-700 dark:text-brand-300 ring-1 ring-brand-100 dark:bg-brand-950/40 dark:ring-brand-900/50">
      <component :is="icon" :size="28" />
    </span>
    <h2 class="mt-2 text-base font-semibold">{{ title }}</h2>
    <p v-if="description" class="mx-auto max-w-md text-sm text-stone-600 dark:text-slate-400">{{ description }}</p>
    <div v-if="$slots.action" class="mt-1"><slot name="action" /></div>
  </div>
</template>
