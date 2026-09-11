<script setup lang="ts">
const tones = {
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    rose: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    amber: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    sky: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    brand: 'bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300',
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    slate: 'bg-stone-100 text-stone-600 dark:bg-slate-800 dark:text-slate-300',
} as const;

withDefaults(
    defineProps<{
        tone?: keyof typeof tones;
        label?: string;
    }>(),
    {
        tone: 'slate',
        label: undefined,
    },
);
</script>

<template>
  <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="tones[tone]">
    <slot />
    <template v-if="label">{{ label }}</template>
  </span>
</template>