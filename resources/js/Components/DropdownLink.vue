<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>

<template>
  <Link
    :href="href"
    class="block w-full px-4 py-2.5 text-start text-sm font-medium leading-5 text-stone-700 transition duration-150 ease-in-out hover:bg-brand-50 hover:text-brand-700 focus:bg-brand-50 focus:text-brand-700 focus:outline-none dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-brand-300 dark:focus:bg-slate-800"
  >
    <slot />
  </Link>
</template>
