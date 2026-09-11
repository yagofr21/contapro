<script setup lang="ts">
defineProps<{
    kicker?: string;
    title: string;
    subtitle?: string;
}>();
</script>

<template>
  <section class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
      <p v-if="kicker" class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-400">{{ kicker }}</p>
      <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
        {{ title }}
        <slot name="title-suffix" />
      </h1>
      <p v-if="subtitle" class="mt-2 text-sm text-stone-500 dark:text-slate-400">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2"><slot name="actions" /></div>
  </section>
</template>