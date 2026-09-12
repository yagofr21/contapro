<script setup lang="ts">
import { LockKeyhole, WalletCards } from '@lucide/vue';
</script>

<template>
  <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10 text-stone-950 sm:px-6 dark:text-white">
    <div class="aurora-surface absolute inset-0" />
    <div class="aurora-blob aurora-blob-1" />
    <div class="aurora-blob aurora-blob-2" />
    <div class="aurora-blob aurora-blob-3" />

    <div class="relative w-full max-w-md">
      <div class="mb-8 flex items-center justify-center gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-600 text-white shadow-xl shadow-brand-600/30"><WalletCards :size="23" /></span>
        <span class="text-xl font-bold tracking-tight">Conta Pro</span>
      </div>

      <div class="rounded-3xl border border-stone-200 bg-white p-7 shadow-[0_25px_60px_-15px_rgba(20,40,87,0.25)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/40 sm:p-9">
        <slot />
      </div>

      <p class="mt-6 flex items-center justify-center gap-2 text-center text-xs text-stone-400 dark:text-slate-500"><LockKeyhole :size="13" />Conexao segura e dados privados</p>
    </div>
  </div>
</template>