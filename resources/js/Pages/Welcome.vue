<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, ChartNoAxesCombined, ShieldCheck, WalletCards } from '@lucide/vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();
</script>

<template>
  <Head title="Conta Pro" />

  <main class="relative min-h-screen overflow-hidden px-4 py-8 text-stone-950 dark:text-white sm:px-6">
    <div class="aurora-surface absolute inset-0" />
    <div class="aurora-blob aurora-blob-1" />
    <div class="aurora-blob aurora-blob-2" />
    <div class="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl flex-col">
      <header class="flex items-center justify-between gap-4 py-4">
        <div class="flex items-center gap-3">
          <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-600 text-white shadow-xl shadow-brand-600/30"><WalletCards :size="23" /></span>
          <div>
            <p class="text-lg font-bold tracking-tight">Conta Pro</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-stone-400 dark:text-slate-500">Finanças claras</p>
          </div>
        </div>

        <nav v-if="canLogin" class="flex items-center gap-2">
          <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700">Abrir painel <ArrowRight :size="16" /></Link>
          <template v-else>
            <Link :href="route('login')" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-stone-600 hover:bg-white/70 hover:text-brand-700 dark:text-slate-300 dark:hover:bg-slate-900">Entrar</Link>
            <Link v-if="canRegister" :href="route('register')" class="hidden rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700 sm:inline-flex">Criar conta</Link>
          </template>
        </nav>
      </header>

      <section class="grid flex-1 items-center gap-10 py-12 lg:grid-cols-[1fr_0.85fr]">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600 dark:text-brand-400">Gestão financeira pessoal</p>
          <h1 class="mt-5 max-w-3xl text-4xl font-bold leading-tight tracking-tight sm:text-6xl">Controle contas, cartões e investimentos com clareza profissional.</h1>
          <p class="mt-6 max-w-2xl text-base leading-7 text-stone-500 dark:text-slate-400">O Conta Pro reúne fluxo de caixa, faturas, compromissos e patrimônio em uma experiência organizada para decisões financeiras do dia a dia.</p>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <Link :href="route('login')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700">Acessar conta <ArrowRight :size="17" /></Link>
            <Link v-if="canRegister" :href="route('register')" class="inline-flex items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white/80 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-700 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-200">Criar cadastro</Link>
          </div>
        </div>

        <div class="cp-card p-5 sm:p-7">
          <div class="rounded-3xl bg-slate-950 p-6 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-400">Central financeira</p>
            <div class="mt-6 grid gap-3">
              <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><ChartNoAxesCombined :size="18" class="text-brand-300" /><p class="mt-3 text-sm font-semibold">Fluxo de caixa organizado</p><p class="mt-1 text-xs text-slate-500">Receitas e despesas com leitura rápida.</p></div>
              <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><WalletCards :size="18" class="text-emerald-300" /><p class="mt-3 text-sm font-semibold">Cartões e faturas sob controle</p><p class="mt-1 text-xs text-slate-500">Vencimentos, limites e compromissos próximos.</p></div>
              <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><ShieldCheck :size="18" class="text-amber-300" /><p class="mt-3 text-sm font-semibold">Dados privados e foco em segurança</p><p class="mt-1 text-xs text-slate-500">Interface clara para uma rotina financeira confiável.</p></div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>
</template>
