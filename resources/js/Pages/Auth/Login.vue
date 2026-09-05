<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Eye, EyeOff, LockKeyhole, Mail } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const showPassword = ref(false);
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const useDemoAccount = () => {
    form.email = 'demo@contapro.local';
    form.password = 'password';
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
  <Head title="Entrar" />
  <GuestLayout>
    <header>
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-600">Bem-vindo de volta</p>
      <h2 class="mt-3 text-3xl font-bold tracking-tight">Acesse sua conta</h2>
      <p class="mt-2 text-sm leading-6 text-stone-500 dark:text-slate-400">Entre para acompanhar sua vida financeira.</p>
    </header>

    <div v-if="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">{{ status }}</div>

    <form class="mt-7 space-y-5" @submit.prevent="submit">
      <label class="block">
        <span class="mb-2 block text-sm font-semibold">E-mail</span>
        <span class="relative block"><Mail :size="17" class="absolute left-3 top-3 text-stone-400" /><input v-model="form.email" type="email" required autofocus autocomplete="username" class="w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm transition focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="voce@exemplo.com" /></span>
        <InputError class="mt-2" :message="form.errors.email" />
      </label>

      <label class="block">
        <span class="mb-2 block text-sm font-semibold">Senha</span>
        <span class="relative block"><LockKeyhole :size="17" class="absolute left-3 top-3 text-stone-400" /><input v-model="form.password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" class="w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-10 pr-11 text-sm shadow-sm transition focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950" placeholder="Sua senha" /><button type="button" class="absolute right-3 top-2.5 rounded p-1 text-stone-400 hover:text-stone-700 dark:hover:text-white" :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'" @click="showPassword = !showPassword"><EyeOff v-if="showPassword" :size="17" /><Eye v-else :size="17" /></button></span>
        <InputError class="mt-2" :message="form.errors.password" />
      </label>

      <div class="flex items-center justify-between gap-4">
        <label class="flex items-center gap-2 text-sm text-stone-500 dark:text-slate-400"><Checkbox v-model:checked="form.remember" name="remember" />Lembrar de mim</label>
        <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Esqueci a senha</Link>
      </div>

      <button :disabled="form.processing" class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700 disabled:opacity-50">Entrar <ArrowRight :size="17" /></button>
    </form>

    <div class="my-6 flex items-center gap-3 text-xs text-stone-400"><span class="h-px flex-1 bg-stone-200 dark:bg-slate-800" />acesso de demonstracao<span class="h-px flex-1 bg-stone-200 dark:bg-slate-800" /></div>
    <button type="button" class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm font-semibold text-stone-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-brand-950/30" @click="useDemoAccount">Preencher conta demo</button>

    <p class="mt-7 text-center text-sm text-stone-500 dark:text-slate-400">Ainda nao tem uma conta? <Link :href="route('register')" class="font-semibold text-brand-600 hover:text-brand-700">Criar cadastro</Link></p>
  </GuestLayout>
</template>
