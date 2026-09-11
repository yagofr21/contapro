<script setup lang="ts">
withDefaults(defineProps<{ title?: string; subtitle?: string }>(), {
    title: '',
    subtitle: '',
});
</script>

<template>
  <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <header
      v-if="title || subtitle || $slots.actions"
      class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4 dark:border-slate-800"
    >
      <div class="min-w-0">
        <h2 v-if="title" class="font-semibold">{{ title }}</h2>
        <p v-if="subtitle" class="mt-0.5 text-xs text-stone-400 dark:text-slate-500">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2"><slot name="actions" /></div>
    </header>
    <div><slot /></div>
  </section>
</template>