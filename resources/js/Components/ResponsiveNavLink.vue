<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'block w-full border-l-4 border-brand-500 bg-brand-50 py-2 pe-4 ps-3 text-start text-base font-semibold text-brand-700 transition duration-150 ease-in-out focus:outline-none focus:border-brand-700 dark:bg-brand-950/40 dark:text-brand-300'
        : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-stone-600 transition duration-150 ease-in-out hover:border-stone-300 hover:bg-stone-50 hover:text-stone-900 focus:outline-none focus:border-brand-500 focus:bg-brand-50 focus:text-brand-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200',
);
</script>

<template>
  <Link :href="href" :class="classes">
    <slot />
  </Link>
</template>
