<script setup lang="ts">
import { WalletCards } from '@lucide/vue';
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-background px-4 py-8 text-stone-950 dark:text-white sm:px-6 sm:py-12">
    <div class="cp-card grid w-full max-w-5xl overflow-hidden lg:grid-cols-2">
      <section class="hidden flex-col justify-between bg-slate-950 p-10 text-white lg:flex">
        <div>
          <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600" aria-hidden="true"><WalletCards :size="22" /></span>
            <span class="text-xl font-bold tracking-tight">Conta Pro</span>
          </div>
          <h1 class="mt-12 max-w-sm text-3xl font-semibold leading-tight tracking-tight">Seu dinheiro,<br />com mais clareza.</h1>
          <p class="mt-4 max-w-sm text-sm leading-6 text-slate-300">Contas, compromissos e investimentos organizados para acompanhar suas finanças todos os dias.</p>
        </div>
        <img src="/images/contapro/auth/login-finance.webp" width="960" height="640" alt="" loading="lazy" decoding="async" class="mt-8 aspect-[3/2] w-full rounded-xl object-cover" />
        <p class="mt-6 text-xs text-slate-400">Organização para o presente. Visão para o futuro.</p>
      </section>
      <div class="flex flex-col justify-center px-6 py-8 sm:px-10 sm:py-12">
        <div class="mb-8 flex items-center gap-3 lg:hidden">
          <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600 text-white" aria-hidden="true"><WalletCards :size="22" /></span>
          <span class="text-xl font-bold tracking-tight">Conta Pro</span>
        </div>
        <slot />
      </div>
    </div>
  </div>
</template>
