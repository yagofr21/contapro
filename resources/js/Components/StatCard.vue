<script setup lang="ts">
import { computed, type Component } from 'vue';
const props = withDefaults(defineProps<{
    label: string;
    value: string;
    icon?: Component;
    tone?: 'default' | 'positive' | 'negative' | 'brand' | 'muted';
    chipClass?: string;
    barClass?: string;
    valueClass?: string;
}>(), { tone: 'default', icon: undefined, chipClass: '', barClass: '', valueClass: undefined });
const toneClass = computed(() => ({
    default: 'text-stone-900 dark:text-white',
    positive: 'text-emerald-700 dark:text-emerald-300',
    negative: 'text-rose-700 dark:text-rose-300',
    brand: 'text-brand-700 dark:text-brand-300',
    muted: 'text-stone-600 dark:text-slate-400 dark:text-slate-400',
}[props.tone]));
</script>

<template>
  <article class="cp-card relative min-w-0 p-5">
    <span v-if="barClass" class="absolute inset-x-5 top-0 h-0.5 rounded-full" :class="barClass" aria-hidden="true" />
    <div class="flex items-start justify-between gap-3">
      <p class="min-w-0 text-sm font-medium text-stone-600 dark:text-slate-300">{{ label }}</p>
      <span v-if="icon" class="shrink-0 text-stone-600 dark:text-slate-400" :class="chipClass" aria-hidden="true"><component :is="icon" :size="18" /></span>
    </div>
    <p class="financial-value mt-3 text-2xl font-bold" :class="valueClass ?? toneClass">{{ value }}</p>
    <div v-if="$slots.footer" class="mt-2 text-xs leading-5 text-stone-600 dark:text-slate-400"><slot name="footer" /></div>
    <slot name="action" />
  </article>
</template>
