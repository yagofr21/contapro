<script setup lang="ts">
defineProps<{
    value?: string;
}>();
</script>

<template>
  <label class="block text-sm font-semibold text-stone-700 dark:text-slate-200">
    <span v-if="value">{{ value }}</span>
    <span v-else><slot /></span>
  </label>
</template>
