<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
  <GuestLayout>
    <Head title="Redefinir senha" />

    <header class="mb-7">
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300 ">Nova credencial</p>
      <h2 class="mt-3 text-3xl font-bold tracking-tight">Escolha uma nova senha</h2>
      <p class="mt-2 text-sm leading-6 text-stone-600 dark:text-slate-400">Use uma senha forte para proteger seus dados financeiros.</p>
    </header>

    <form class="space-y-5" @submit.prevent="submit">
      <div>
        <InputLabel for="email" value="E-mail" />

        <TextInput
          id="email"
          v-model="form.email"
          type="email"
          class="mt-2 block w-full"
          required
          autofocus
          autocomplete="username"
        />

        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div>
        <InputLabel for="password" value="Nova senha" />

        <TextInput
          id="password"
          v-model="form.password"
          type="password"
          class="mt-2 block w-full"
          required
          autocomplete="new-password"
        />

        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div>
        <InputLabel
          for="password_confirmation"
          value="Confirmar senha"
        />

        <TextInput
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="mt-2 block w-full"
          required
          autocomplete="new-password"
        />

        <InputError
          class="mt-2"
          :message="form.errors.password_confirmation"
        />
      </div>

      <div class="flex items-center justify-end">
        <PrimaryButton
          class="w-full"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Redefinir senha
        </PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>
