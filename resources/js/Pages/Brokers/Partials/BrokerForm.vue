<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import type { Broker } from '@/types/investment';
import { Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';

const props = defineProps<{ broker?: Broker }>();
const form = useForm({
    name: props.broker?.name ?? '',
    is_active: props.broker?.is_active ?? true,
});
const submit = () => props.broker
    ? form.put(route('brokers.update', props.broker.id))
    : form.post(route('brokers.store'));
</script>

<template>
  <form class="mt-8 max-w-2xl rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" @submit.prevent="submit">
    <label><span class="mb-2 block text-sm font-semibold">Nome da corretora</span><input v-model="form.name" autofocus class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="Ex.: XP Investimentos" /><InputError class="mt-2" :message="form.errors.name" /></label>
    <label v-if="broker" class="mt-6 flex items-center gap-3"><input v-model="form.is_active" type="checkbox" class="rounded border-stone-300 text-brand-600 focus:ring-brand-500" /><span><span class="block text-sm font-semibold">Corretora ativa</span><span class="text-xs text-stone-400">Corretoras inativas permanecem no historico, mas nao aparecem em novos lancamentos.</span></span></label>
    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-stone-100 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end"><Link :href="route('brokers.index')" class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-stone-500">Cancelar</Link><button :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"><Save :size="17" />{{ broker ? 'Salvar alteracoes' : 'Cadastrar corretora' }}</button></div>
  </form>
</template>
