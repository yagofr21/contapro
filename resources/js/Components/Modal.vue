<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
        title?: string;
        closeable?: boolean;
        gradient?: boolean | 'brand' | 'green' | 'rose';
    }>(),
    {
        show: false,
        maxWidth: '2xl',
        title: '',
        closeable: true,
        gradient: false,
    },
);

const emit = defineEmits(['close']);

watch(
    () => props.show,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});

const gradientTone = computed(() => {
    if (props.gradient === 'green') return 'aurora-green';
    if (props.gradient === 'rose') return 'aurora-rose';
    return '';
});
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-stone-900/40" @click="close" />
        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
          <div
            class="relative w-full overflow-hidden rounded-2xl bg-white shadow-2xl shadow-stone-900/10 ring-1 ring-stone-900/5 dark:bg-slate-900 dark:ring-white/5"
            :class="[maxWidthClass, gradientTone]"
          >
            <template v-if="props.gradient">
              <div class="aurora-surface pointer-events-none absolute inset-0" />
              <div class="aurora-blob pointer-events-none aurora-blob-1" />
              <div class="aurora-blob pointer-events-none aurora-blob-2" />
              <div class="aurora-blob pointer-events-none aurora-blob-3" />
            </template>
            <header
              v-if="title || closeable"
              class="relative flex shrink-0 items-center justify-between gap-4 border-b border-stone-100 px-6 py-4 dark:border-slate-800 sm:px-8"
              :class="props.gradient ? 'border-transparent bg-white/60 backdrop-blur dark:bg-slate-900/60' : ''"
            >
              <h2 v-if="title" class="text-lg font-bold tracking-tight">
                {{ title }}
              </h2>
              <span v-else />
              <button
                v-if="closeable"
                type="button"
                class="rounded-lg p-2 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                aria-label="Fechar"
                @click="close"
              >
                <X :size="18" />
              </button>
            </header>

            <div class="relative max-h-[calc(100dvh-3.5rem)] min-h-0 overflow-y-auto">
              <slot />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>