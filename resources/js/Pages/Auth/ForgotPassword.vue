<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
  <GuestLayout>
    <Head title="Esqueceu a senha" />

    <div class="mb-4 text-sm text-gray-600">
      Esqueceu a senha? Sem problemas. Informe seu e-mail e enviaremos um
      link de redefinição de senha para que você escolha uma nova.
    </div>

    <div
      v-if="status"
      class="mb-4 text-sm font-medium text-green-600"
    >
      {{ status }}
    </div>

    <form @submit.prevent="submit">
      <div>
        <InputLabel for="email" value="E-mail" />

        <TextInput
          id="email"
          v-model="form.email"
          type="email"
          class="mt-1 block w-full"
          required
          autofocus
          autocomplete="username"
        />

        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div class="mt-4 flex items-center justify-end">
        <PrimaryButton
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Enviar link de redefinição
        </PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>
