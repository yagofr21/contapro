<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon?: Component;
    title: string;
    description?: string;
}>();
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
    <component :is="icon" v-if="icon" :size="34" class="text-stone-300 dark:text-slate-600" />
    <h2 class="text-base font-semibold">{{ title }}</h2>
    <p v-if="description" class="mx-auto max-w-md text-sm text-stone-500 dark:text-slate-400">{{ description }}</p>
    <div v-if="$slots.action" class="mt-1"><slot name="action" /></div>
  </div>
</template>