<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            form.reset();
        },
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
  <section class="space-y-6">
    <header>
      <h2 class="text-lg font-bold tracking-tight text-danger-700 dark:text-danger-500">
        Excluir conta
      </h2>

      <p class="mt-1 text-sm leading-6 text-stone-600 dark:text-slate-400">
        Uma vez excluída, sua conta, seus recursos e seus dados serão
        removidos permanentemente. Antes de excluir, baixe tudo o que
        deseja manter.
      </p>
    </header>

    <DangerButton @click="confirmUserDeletion">Excluir conta</DangerButton>

    <Modal :show="confirmingUserDeletion" @close="closeModal">
      <div class="p-6 sm:p-7">
        <h2
          class="text-lg font-bold tracking-tight text-stone-950 dark:text-white"
        >
          Excluir conta permanentemente?
        </h2>

        <p class="mt-2 rounded-2xl border border-danger-100 bg-danger-50 p-4 text-sm leading-6 text-danger-700 dark:border-danger-900/60 dark:bg-danger-950/30 dark:text-danger-500">
          Esta ação não poderá ser desfeita. Sua conta e todos os dados financeiros serão removidos permanentemente. Digite sua senha para confirmar.
        </p>

        <div class="mt-6">
          <InputLabel
            for="password"
            value="Senha"
            class="sr-only"
          />

          <TextInput
            id="password"
            ref="passwordInput"
            v-model="form.password"
            type="password"
            class="mt-2 block w-full"
            placeholder="Senha"
            @keyup.enter="deleteUser"
          />

          <InputError :message="form.errors.password" class="mt-2" />
        </div>

        <div class="mt-6 flex flex-col-reverse justify-end gap-2 sm:flex-row">
          <SecondaryButton @click="closeModal">
            Cancelar
          </SecondaryButton>

          <DangerButton
            class="sm:ms-3"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            @click="deleteUser"
          >
            Excluir conta
          </DangerButton>
        </div>
      </div>
    </Modal>
  </section>
</template>
