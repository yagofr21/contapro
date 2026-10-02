<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center border-b-2 border-brand-500 px-1 pt-1 text-sm font-semibold leading-5 text-brand-700 transition duration-150 ease-in-out focus:outline-none focus:border-brand-700 dark:text-brand-300'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-stone-600 dark:text-slate-400 transition duration-150 ease-in-out hover:border-stone-300 hover:text-stone-800 focus:outline-none focus:border-brand-500 focus:text-brand-700 dark:text-slate-400 dark:hover:text-slate-200',
);
</script>

<template>
  <Link :href="href" :class="classes">
    <slot />
  </Link>
</template>
