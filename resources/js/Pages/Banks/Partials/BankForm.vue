<script setup lang="ts">
import FinanceHero from '@/Components/FinanceHero.vue';
import InputError from '@/Components/InputError.vue';
import type { Bank } from '@/types/finance';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ bank?: Bank }>();

const form = useForm({
    code: props.bank?.code ?? '',
    label: props.bank?.label ?? '',
    color: props.bank?.color ?? '#1b6ef5',
    initials: props.bank?.initials ?? '',
    is_active: props.bank?.is_active ?? true,
});

const colorOptions = ['#1b6ef5', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#14b8a6', '#f97316', '#64748b'];

const previewInitials = computed(() => form.initials || 'BC');

const submit = () => (props.bank
    ? form.put(route('banks.update', props.bank.id))
    : form.post(route('banks.store')));
</script>

<template>
  <FinanceHero :title="bank ? 'Uma identidade que você reconhece.' : 'Dê personalidade ao seu banco.'" description="Nome, cor e iniciais aparecem nas suas contas. Veja a prévia enquanto cadastra." kicker="Identificação visual" />
  <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
    <form class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit">
      <label>
        <span class="mb-2 block text-sm font-semibold">Código de identificação</span>
        <input v-model="form.code" autofocus :disabled="Boolean(bank)" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm font-mono disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: nubank" />
        <span v-if="bank" class="mt-1 block text-xs text-stone-600 dark:text-slate-400">O código identifica o banco nas contas e não pode ser alterado.</span>
        <InputError class="mt-2" :message="form.errors.code" />
      </label>
      <label class="mt-6 block">
        <span class="mb-2 block text-sm font-semibold">Nome do banco</span>
        <input v-model="form.label" class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: Nubank" />
        <InputError class="mt-2" :message="form.errors.label" />
      </label>
      <div class="mt-6 flex items-center gap-4">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl text-sm font-extrabold text-white" :style="{ backgroundColor: form.color }">{{ previewInitials }}</span>
        <label class="block">
          <span class="mb-2 block text-sm font-semibold">Cor da marca</span>
          <span class="flex items-center gap-2">
            <input v-model="form.color" type="color" class="h-10 w-14 cursor-pointer rounded-lg border border-stone-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-950" aria-label="Cor do banco" />
            <input v-model="form.color" class="w-28 rounded-xl border border-stone-200 bg-white px-3 py-2.5 font-mono text-xs uppercase shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" aria-label="Cor em hexadecimal" />
          </span>
          <span class="mt-2 flex items-center gap-2">
            <button v-for="c in colorOptions" :key="c" type="button" class="h-5 w-5 rounded-full transition hover:scale-110" :style="{ backgroundColor: c }" :aria-label="`Cor ${c}`" :title="c" @click="form.color = c" />
          </span>
          <InputError class="mt-2" :message="form.errors.color" />
        </label>
      </div>
      <label class="mt-6 block">
        <span class="mb-2 block text-sm font-semibold">Iniciais do banco</span>
        <input v-model="form.initials" maxlength="4" autocomplete="off" class="w-28 rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-center text-sm font-extrabold uppercase tracking-widest dark:border-slate-700 dark:bg-slate-950" placeholder="NB" />
        <InputError class="mt-2" :message="form.errors.initials" />
      </label>
      <label v-if="bank" class="mt-6 flex items-center gap-3">
        <input v-model="form.is_active" type="checkbox" class="rounded border-stone-300 text-brand-700 dark:text-brand-300 focus:ring-brand-500" />
        <span>
          <span class="block text-sm font-semibold">Banco ativo</span>
          <span class="text-xs text-stone-600 dark:text-slate-400">Bancos inativos continuam no histórico, mas não aparecem na criação de contas.</span>
        </span>
      </label>
      <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-100 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end">
        <Link :href="route('banks.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-600 dark:text-slate-400">Cancelar</Link>
        <button :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"><Save :size="17" />{{ bank ? 'Salvar alterações' : 'Cadastrar banco' }}</button>
      </div>
    </form>
    <aside class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-24">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Prévia nas suas contas</p>
      <div class="relative mt-5 overflow-hidden rounded-2xl bg-slate-950 p-6 text-white">
        <div class="absolute -right-12 -top-12 h-48 w-48 rounded-full opacity-40 blur-2xl" :style="{ backgroundColor: form.color }" />
        <span class="relative grid h-12 w-12 place-items-center rounded-xl font-bold ring-1 ring-white/20" :style="{ backgroundColor: form.color }">{{ previewInitials }}</span>
        <p class="relative mt-8 text-xl font-bold">{{ form.label || 'Nome do banco' }}</p><p class="relative mt-2 text-xs text-white/70">Conta principal · BRL</p>
        <div class="relative mt-6 h-1.5 rounded-full" :style="{ backgroundColor: form.color }" />
      </div>
      <p class="mt-5 text-sm leading-relaxed text-stone-600 dark:text-slate-400">Escolha uma cor e iniciais fáceis de reconhecer. A identificação aparece nas contas e nos cartões vinculados a este banco.</p>
    </aside>
  </div>
</template>