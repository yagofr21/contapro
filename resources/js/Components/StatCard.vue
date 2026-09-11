<script setup lang="ts">
import type { Component } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string;
        value: string;
        icon?: Component;
        tone?: 'default' | 'positive' | 'negative' | 'brand' | 'muted';
        chipClass?: string;
        barClass?: string;
        valueClass?: string;
    }>(),
    {
        tone: 'default',
        icon: undefined,
        chipClass: '',
        barClass: '',
        valueClass: undefined,
    },
);

const toneClass = {
    default: '',
    positive: 'text-emerald-600 dark:text-emerald-400',
    negative: 'text-rose-600 dark:text-rose-400',
    brand: 'text-brand-600 dark:text-brand-400',
    muted: 'text-stone-400 dark:text-slate-500',
}[props.tone];
</script>

<template>
  <article class="group relative overflow-hidden rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
    <span v-if="barClass" class="absolute inset-x-0 top-0 h-1" :class="barClass" />
    <div class="flex items-start justify-between gap-3">
      <p class="min-w-0 text-[11px] font-semibold uppercase tracking-wider text-stone-500 dark:text-slate-400">{{ label }}</p>
      <span v-if="icon" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="chipClass"><component :is="icon" :size="18" /></span>
    </div>
    <p class="mt-2 text-xl font-bold tracking-tight sm:text-2xl" :class="valueClass ?? toneClass">{{ value }}</p>
    <slot name="footer" />
    <slot name="action" />
  </article>
</template>