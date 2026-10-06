<script setup lang="ts">
defineProps<{ title: string; description: string; kicker?: string }>();
</script>
<template>
  <section class="relative mt-6 overflow-hidden rounded-3xl border border-stone-200 bg-[#f5eee3] dark:border-slate-700">
    <img src="/images/finance/banking-studio.webp" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover object-right" width="1536" height="1024" decoding="async" />
    <div class="absolute inset-0 bg-gradient-to-r from-[#f5eee3] via-[#f5eee3]/90 to-transparent sm:via-[#f5eee3]/60" />
    <div class="relative max-w-xl p-6 pr-16 sm:p-8 sm:pr-20">
      <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-teal-800">{{ kicker ?? 'Seu dinheiro, com clareza' }}</p>
      <h2 class="mt-3 max-w-sm text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">{{ title }}</h2>
      <p class="mt-3 max-w-sm text-sm leading-relaxed text-stone-700">{{ description }}</p>
      <div class="mt-5"><slot /></div>
    </div>
  </section>
</template>
