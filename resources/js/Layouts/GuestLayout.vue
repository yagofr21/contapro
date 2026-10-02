<script setup lang="ts">
import { ChartNoAxesCombined, LockKeyhole, ShieldCheck, TrendingUp, WalletCards } from '@lucide/vue';
</script>

<template>
  <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10 text-stone-950 sm:px-6 dark:text-white">
    <div class="aurora-surface absolute inset-0" />
    <div class="aurora-blob aurora-blob-1" />
    <div class="aurora-blob aurora-blob-2" />
    <div class="aurora-blob aurora-blob-3" />

    <div class="relative grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-white/70 bg-white/70 shadow-[0_30px_90px_-35px_rgba(20,40,87,0.45)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/70 dark:shadow-black/50 lg:grid-cols-[1.1fr_0.9fr]">
      <section class="relative hidden min-h-[620px] overflow-hidden bg-slate-950 p-10 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(closest-side_at_28%_20%,rgba(51,141,255,0.3),transparent_60%),radial-gradient(closest-side_at_75%_75%,rgba(16,185,129,0.16),transparent_65%)]" />
        <div class="relative">
          <div class="flex items-center gap-3">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-xl shadow-brand-600/30"><WalletCards :size="24" /></span>
            <div>
              <p class="text-xl font-bold tracking-tight">Conta Pro</p>
              <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-500">Finanças claras</p>
            </div>
          </div>
          <h1 class="mt-16 max-w-xl text-4xl font-bold leading-tight tracking-tight">Organize sua vida financeira com clareza, controle e segurança.</h1>
          <p class="mt-5 max-w-md text-sm leading-6 text-slate-400">Acompanhe contas, cartões, faturas, investimentos e compromissos em uma experiência pensada para decisões financeiras diárias.</p>
        </div>

        <div class="relative grid gap-3">
          <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
            <div class="flex items-center justify-between text-sm text-slate-400"><span>Patrimônio monitorado</span><TrendingUp :size="18" class="text-emerald-400" /></div>
            <p class="mt-3 text-3xl font-bold tracking-tight">Controle completo</p>
            <div class="mt-4 h-2 rounded-full bg-slate-800"><div class="h-2 w-2/3 rounded-full bg-gradient-to-r from-brand-500 to-emerald-400" /></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><ChartNoAxesCombined :size="18" class="text-brand-300" /><p class="mt-3 text-xs text-slate-500">Relatórios claros</p></div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><ShieldCheck :size="18" class="text-emerald-300" /><p class="mt-3 text-xs text-slate-500">Ambiente protegido</p></div>
          </div>
        </div>
      </section>

      <div class="flex min-h-[620px] flex-col justify-center p-6 sm:p-10">
        <div class="mb-8 flex items-center justify-center gap-3 lg:hidden">
          <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-600 text-white shadow-xl shadow-brand-600/30"><WalletCards :size="23" /></span>
          <span class="text-xl font-bold tracking-tight">Conta Pro</span>
        </div>

        <div class="rounded-3xl border border-stone-200 bg-white p-7 shadow-[0_25px_60px_-15px_rgba(20,40,87,0.25)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/40 sm:p-9">
          <slot />
        </div>

        <p class="mt-6 flex items-center justify-center gap-2 text-center text-xs text-stone-400 dark:text-slate-500"><LockKeyhole :size="13" />Conexão segura e dados privados</p>
      </div>
    </div>
  </div>
</template>
