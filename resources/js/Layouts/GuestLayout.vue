<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChartNoAxesCombined, LockKeyhole, ShieldCheck, WalletCards } from '@lucide/vue';
</script>

<template>
  <div class="min-h-screen bg-stone-50 text-stone-950 dark:bg-slate-950 dark:text-white">
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
      <aside class="relative hidden overflow-hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between xl:p-16">
        <div class="absolute inset-0 opacity-50" style="background: radial-gradient(circle at 15% 20%, rgba(51, 141, 255, 0.3), transparent 35%), radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.2), transparent 35%)" />
        <div class="relative">
          <Link :href="route('login')" class="inline-flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-500 shadow-xl shadow-brand-500/30"><WalletCards :size="23" /></span>
            <span class="text-xl font-bold tracking-tight">Conta Pro</span>
          </Link>
        </div>

        <div class="relative max-w-xl">
          <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-300">Controle sem ruido</p>
          <h1 class="mt-5 text-5xl font-bold leading-[1.08] tracking-tight xl:text-6xl">Seu dinheiro,<br><span class="text-brand-400">mais legivel.</span></h1>
          <p class="mt-6 max-w-lg text-base leading-7 text-slate-400">Organize contas, acompanhe o fluxo mensal e entenda seus investimentos em um unico lugar.</p>
          <div class="mt-10 grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 backdrop-blur"><ChartNoAxesCombined :size="20" class="text-emerald-400" /><p class="mt-3 text-sm font-semibold">Visao consolidada</p><p class="mt-1 text-xs leading-5 text-slate-500">Financas e patrimonio juntos.</p></div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 backdrop-blur"><ShieldCheck :size="20" class="text-brand-400" /><p class="mt-3 text-sm font-semibold">Dados protegidos</p><p class="mt-1 text-xs leading-5 text-slate-500">Acesso isolado por usuario.</p></div>
          </div>
        </div>

        <p class="relative text-xs text-slate-600">Conta Pro · Gestao financeira pessoal</p>
      </aside>

      <main class="relative flex items-center justify-center px-5 py-10 sm:px-10 lg:px-16">
        <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-brand-50 to-transparent dark:from-brand-950/20 lg:hidden" />
        <div class="relative w-full max-w-md">
          <Link :href="route('login')" class="mb-10 flex items-center justify-center gap-3 lg:hidden">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/20"><WalletCards :size="21" /></span>
            <span class="text-xl font-bold">Conta Pro</span>
          </Link>
          <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-xl shadow-stone-200/50 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20 sm:p-8">
            <slot />
          </div>
          <p class="mt-6 flex items-center justify-center gap-2 text-center text-xs text-stone-400"><LockKeyhole :size="13" />Conexao segura e dados privados</p>
        </div>
      </main>
    </div>
  </div>
</template>
