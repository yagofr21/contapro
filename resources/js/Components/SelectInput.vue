<script setup lang="ts">
const model = defineModel<string>({ required: true });
</script>

<template>
  <select
    v-model="model"
    class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm transition focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
  >
    <slot />
  </select>
</template>
