<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import {
    ArrowLeftRight,
    CandlestickChart,
    ChartNoAxesCombined,
    ChevronDown,
    Gauge,
    LayoutDashboard,
    Menu,
    Moon,
    PieChart,
    Tags,
    Sun,
    WalletCards,
    X,
} from '@lucide/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const menuOpen = ref(false);
const isDark = ref(false);
const success = computed(() => page.props.flash?.success as string | null);

const navigation = [
    { label: 'Visao geral', route: 'dashboard', pattern: 'dashboard', icon: LayoutDashboard },
    { label: 'Contas', route: 'accounts.index', pattern: 'accounts.*', icon: WalletCards },
    { label: 'Categorias', route: 'categories.index', pattern: 'categories.*', icon: Tags },
    { label: 'Orcamentos', route: 'budgets.index', pattern: 'budgets.*', icon: Gauge },
    { label: 'Lancamentos', route: 'transactions.index', pattern: 'transactions.*', icon: ArrowLeftRight },
    { label: 'Carteiras', route: 'portfolios.index', pattern: 'portfolios.*', icon: PieChart },
    { label: 'Ativos', route: 'assets.index', pattern: 'assets.*', icon: CandlestickChart },
    { label: 'Relatorios', route: 'reports.index', pattern: 'reports.*', icon: ChartNoAxesCombined },
];

const applyTheme = (dark: boolean) => {
    isDark.value = dark;
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.classList.toggle('light', !dark);
    localStorage.setItem('theme', dark ? 'dark' : 'light');
};

onMounted(() => {
    const stored = localStorage.getItem('theme');
    applyTheme(stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches);
});
</script>

<template>
  <div class="min-h-screen bg-stone-50 text-stone-900 dark:bg-slate-950 dark:text-slate-100">
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-slate-800 bg-slate-950 text-slate-200 lg:flex">
      <Link :href="route('dashboard')" class="flex h-20 items-center gap-3 border-b border-slate-800 px-6">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
          <WalletCards :size="22" />
        </span>
        <span>
          <span class="block text-lg font-bold tracking-tight text-white">Conta Pro</span>
          <span class="block text-[11px] uppercase tracking-[0.2em] text-slate-500">Financas claras</span>
        </span>
      </Link>

      <nav class="flex-1 space-y-1 px-3 py-6">
        <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-600">Navegacao</p>
        <Link
          v-for="item in navigation"
          :key="item.route"
          :href="route(item.route)"
          class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
          :class="route().current(item.pattern) ? 'bg-brand-500 text-white shadow-lg shadow-brand-950/40' : 'text-slate-400 hover:bg-slate-900 hover:text-white'"
        >
          <component :is="item.icon" :size="18" />
          {{ item.label }}
        </Link>
      </nav>

      <div class="border-t border-slate-800 p-4">
        <div class="rounded-2xl bg-slate-900 p-4">
          <p class="text-xs text-slate-500">Ambiente protegido</p>
          <p class="mt-1 truncate text-sm font-semibold text-slate-200">{{ $page.props.auth.user.email }}</p>
        </div>
      </div>
    </aside>

    <div class="lg:pl-64">
      <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-stone-200/80 bg-white/90 px-4 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/90 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
          <button class="rounded-xl p-2 text-stone-600 hover:bg-stone-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden" @click="menuOpen = true">
            <Menu :size="22" />
          </button>
          <div>
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-stone-400">{{ new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' }).format(new Date()) }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button class="rounded-xl border border-stone-200 p-2 text-stone-500 transition hover:bg-stone-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Alternar tema" @click="applyTheme(!isDark)">
            <Sun v-if="isDark" :size="18" />
            <Moon v-else :size="18" />
          </button>
          <Dropdown align="right" width="48">
            <template #trigger>
              <button class="flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm font-semibold hover:bg-stone-100 dark:hover:bg-slate-800">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-950 dark:text-brand-200">{{ $page.props.auth.user.name.charAt(0).toUpperCase() }}</span>
                <span class="hidden sm:inline">{{ $page.props.auth.user.name }}</span>
                <ChevronDown :size="15" class="text-stone-400" />
              </button>
            </template>
            <template #content>
              <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
              <DropdownLink :href="route('logout')" method="post" as="button">Sair</DropdownLink>
            </template>
          </Dropdown>
        </div>
      </header>

      <div v-if="success" class="mx-4 mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200 sm:mx-6 lg:mx-8">
        {{ success }}
      </div>

      <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <slot />
      </main>
    </div>

    <div v-if="menuOpen" class="fixed inset-0 z-50 lg:hidden">
      <button class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" aria-label="Fechar menu" @click="menuOpen = false" />
      <aside class="relative flex h-full w-72 flex-col bg-slate-950 p-4 text-slate-200 shadow-2xl">
        <div class="mb-6 flex items-center justify-between px-2 py-2">
          <div class="flex items-center gap-3 text-lg font-bold text-white"><WalletCards :size="22" class="text-brand-400" />Conta Pro</div>
          <button class="rounded-xl p-2 hover:bg-slate-800" @click="menuOpen = false"><X :size="20" /></button>
        </div>
        <nav class="space-y-1">
          <Link v-for="item in navigation" :key="item.route" :href="route(item.route)" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium" :class="route().current(item.pattern) ? 'bg-brand-500 text-white' : 'text-slate-400'" @click="menuOpen = false">
            <component :is="item.icon" :size="19" />{{ item.label }}
          </Link>
        </nav>
      </aside>
    </div>
  </div>
</template>
