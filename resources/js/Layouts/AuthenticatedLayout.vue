<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import type { Component } from 'vue';
import {
    ArrowLeftRight,
    Building2,
    CalendarClock,
    CalendarCheck2,
    CalendarRange,
    CandlestickChart,
    ChartNoAxesCombined,
    CheckCircle2,
    ChevronDown,
    Gauge,
    LayoutDashboard,
    Menu,
    Moon,
    PanelLeft,
    PanelLeftClose,
    PieChart,
    Repeat,
    Scale,
    ShieldCheck,
    Sun,
    Tags,
    Target,
    Upload,
    WalletCards,
    X,
} from '@lucide/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const menuOpen = ref(false);
const isDark = ref(false);
const success = computed(() => page.props.flash?.success as string | null);
const user = computed(() => page.props.auth.user as { name: string; email: string });
const initial = computed(() => user.value.name.charAt(0).toUpperCase());

type NavItem = { label: string; route: string; pattern: string; icon: Component };
type NavSection = { key: string; label: string; items: NavItem[] };

const sections: NavSection[] = [
    {
        key: 'inicio',
        label: 'Inicio',
        items: [
            { label: 'Visao geral', route: 'dashboard', pattern: 'dashboard', icon: LayoutDashboard },
            { label: 'Agenda', route: 'agenda.index', pattern: 'agenda.*', icon: CalendarClock },
        ],
    },
    {
        key: 'finance',
        label: 'Financeiro',
        items: [
            { label: 'Lancamentos', route: 'transactions.index', pattern: 'transactions.*', icon: ArrowLeftRight },
            { label: 'Contas', route: 'accounts.index', pattern: 'accounts.*', icon: WalletCards },
            { label: 'Categorias', route: 'categories.index', pattern: 'categories.*', icon: Tags },
            { label: 'Orcamentos', route: 'budgets.index', pattern: 'budgets.*', icon: Gauge },
            { label: 'Metas', route: 'goals.index', pattern: 'goals.*', icon: Target },
            { label: 'Receitas futuras', route: 'expected-incomes.index', pattern: 'expected-incomes.*', icon: CalendarCheck2 },
        ],
    },
    {
        key: 'planning',
        label: 'Planejamento',
        items: [
            { label: 'Recorrencias', route: 'recurring.index', pattern: 'recurring.*', icon: Repeat },
            { label: 'Parcelas', route: 'installments.index', pattern: 'installments.*', icon: CalendarRange },
        ],
    },
    {
        key: 'investments',
        label: 'Investimentos',
        items: [
            { label: 'Carteiras', route: 'portfolios.index', pattern: 'portfolios.*', icon: PieChart },
            { label: 'Ativos', route: 'assets.index', pattern: 'assets.*', icon: CandlestickChart },
            { label: 'Corretoras', route: 'brokers.index', pattern: 'brokers.*', icon: Building2 },
        ],
    },
    {
        key: 'data',
        label: 'Dados & Relatorios',
        items: [
            { label: 'Importacoes', route: 'imports.index', pattern: 'imports.*', icon: Upload },
            { label: 'Conciliacoes', route: 'reconciliations.index', pattern: 'reconciliations.*', icon: Scale },
            { label: 'Relatorios', route: 'reports.index', pattern: 'reports.*', icon: ChartNoAxesCombined },
        ],
    },
];

const collapsed = ref(false);
const openSections = ref<Record<string, boolean>>({});

const readSidebarState = () => {
    collapsed.value = localStorage.getItem('sidebar-collapsed') === '1';
    try {
        openSections.value = JSON.parse(localStorage.getItem('sidebar-sections') ?? '{}');
    } catch {
        openSections.value = {};
    }
};

const toggleCollapsed = () => {
    collapsed.value = !collapsed.value;
    localStorage.setItem('sidebar-collapsed', collapsed.value ? '1' : '0');
};

const toggleSection = (key: string) => {
    openSections.value = { ...openSections.value, [key]: !(openSections.value[key] ?? true) };
    localStorage.setItem('sidebar-sections', JSON.stringify(openSections.value));
};

const isSectionOpen = (key: string) => openSections.value[key] ?? true;
const sectionVisible = (key: string) => collapsed.value || isSectionOpen(key);

const applyTheme = (dark: boolean) => {
    isDark.value = dark;
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.classList.toggle('light', !dark);
    localStorage.setItem('theme', dark ? 'dark' : 'light');
};

const currentContext = computed(() => {
    void page.props;
    for (const section of sections) {
        for (const item of section.items) {
            if (route().current(item.pattern)) {
                return { section: section.label, item: item.label };
            }
        }
    }
    return null;
});

onMounted(() => {
    readSidebarState();
    const stored = localStorage.getItem('theme');
    applyTheme(stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches);
});
</script>

<template>
  <div class="min-h-screen text-stone-900 dark:text-slate-100">
    <aside
      class="fixed inset-y-0 left-0 z-40 hidden flex-col overflow-hidden border-r border-slate-800/80 bg-slate-950 bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 text-slate-200 transition-[width] duration-200 lg:flex"
      :class="collapsed ? 'w-[4.5rem]' : 'w-64'"
    >
      <div class="pointer-events-none absolute inset-x-0 top-0 h-44 bg-[radial-gradient(closest-side_at_50%_0%,rgba(51,141,255,0.16),transparent)]" aria-hidden="true" />

      <Link :href="route('dashboard')" class="relative flex h-14 shrink-0 items-center border-b border-slate-800/70" :class="collapsed ? 'justify-center px-0' : 'gap-3 px-5'">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-lg shadow-brand-500/30 ring-1 ring-white/20">
          <WalletCards :size="19" />
        </span>
        <span v-if="!collapsed">
          <span class="block text-[15px] font-bold leading-tight tracking-tight text-white">Conta Pro</span>
          <span class="block text-[9px] font-semibold uppercase tracking-[0.24em] text-slate-500">Financas claras</span>
        </span>
      </Link>

      <nav class="relative flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <template v-for="(section, index) in sections" :key="section.key">
          <div v-if="index > 0" class="my-2 h-px bg-gradient-to-r from-transparent via-slate-800 to-transparent" />
          <button
            v-if="!collapsed"
            class="group flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-left text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500 transition hover:text-slate-300"
            @click="toggleSection(section.key)"
          >
            <span class="flex items-center gap-2">
              <span class="h-1 w-1 rounded-full bg-slate-600 transition group-hover:bg-brand-400" />
              {{ section.label }}
            </span>
            <ChevronDown :size="13" class="transition" :class="isSectionOpen(section.key) ? '' : '-rotate-90'" />
          </button>
          <Link
            v-for="item in sectionVisible(section.key) ? section.items : []"
            :key="item.route"
            :href="route(item.route)"
            class="flex items-center rounded-xl text-sm font-medium transition"
            :class="[
              collapsed ? 'justify-center py-2' : 'gap-3 px-3 py-2',
              route().current(item.pattern)
                ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/25'
                : 'text-slate-400 hover:bg-slate-800/70 hover:text-white',
            ]"
            :title="collapsed ? item.label : undefined"
            :aria-label="collapsed ? item.label : undefined"
          >
            <span class="grid h-5 w-5 shrink-0 place-items-center">
              <component :is="item.icon" :size="18" />
            </span>
            <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
            <span v-if="!collapsed && route().current(item.pattern)" class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-white/70" />
          </Link>
        </template>
      </nav>

      <div class="relative border-t border-slate-800/70 p-3">
        <div v-if="!collapsed" class="relative overflow-hidden rounded-xl bg-slate-900/90 p-3.5 ring-1 ring-white/10">
          <div class="pointer-events-none absolute -right-8 -top-10 h-24 w-24 rounded-full bg-brand-500/25 blur-2xl" aria-hidden="true" />
          <div class="relative flex items-center gap-2.5">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 text-xs font-bold text-white shadow shadow-brand-500/30">{{ initial }}</span>
            <div class="min-w-0">
              <p class="truncate text-xs font-semibold text-slate-100">{{ user.name }}</p>
              <p class="mt-0.5 flex items-center gap-1 text-[10px] font-semibold text-emerald-400"><ShieldCheck :size="11" />Ambiente protegido</p>
            </div>
          </div>
        </div>
        <div v-else class="grid place-items-center py-1" :title="user.email">
          <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-500/20 text-xs font-bold text-brand-300 ring-2 ring-brand-400/30">{{ initial }}</span>
        </div>
      </div>
    </aside>

    <div class="transition-[padding] duration-200" :class="collapsed ? 'lg:pl-[4.5rem]' : 'lg:pl-64'">
      <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-stone-200/70 bg-white/85 px-4 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/85 sm:px-6">
        <div class="flex min-w-0 items-center gap-2">
          <button class="rounded-lg p-2 text-stone-600 hover:bg-stone-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden" aria-label="Abrir menu" @click="menuOpen = true"><Menu :size="20" /></button>
          <button class="hidden rounded-lg p-2 text-stone-500 transition hover:bg-stone-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:block" aria-label="Alternar menu lateral" @click="toggleCollapsed">
            <PanelLeftClose v-if="!collapsed" :size="18" />
            <PanelLeft v-else :size="18" />
          </button>
          <div v-if="currentContext" class="hidden min-w-0 items-center gap-1.5 text-sm text-stone-400 dark:text-slate-400 sm:flex">
            <span class="truncate">{{ currentContext.section }}</span>
            <span class="text-stone-300 dark:text-slate-600">/</span>
            <span class="truncate font-semibold text-stone-700 dark:text-slate-200">{{ currentContext.item }}</span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button class="rounded-lg border border-stone-200 p-2 text-stone-500 transition hover:bg-stone-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Alternar tema" @click="applyTheme(!isDark)">
            <Sun v-if="isDark" :size="17" />
            <Moon v-else :size="17" />
          </button>
          <Dropdown align="right" width="48">
            <template #trigger>
              <button class="flex items-center gap-2 rounded-xl border border-transparent px-2 py-1.5 text-sm font-semibold transition hover:border-stone-200 hover:bg-stone-100 dark:hover:border-slate-700 dark:hover:bg-slate-800">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-xs font-bold text-white shadow shadow-brand-500/25">{{ initial }}</span>
                <span class="hidden sm:inline">{{ user.name }}</span>
                <ChevronDown :size="14" class="text-stone-400" />
              </button>
            </template>
            <template #content>
              <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
              <DropdownLink :href="route('logout')" method="post" as="button">Sair</DropdownLink>
            </template>
          </Dropdown>
        </div>
      </header>

      <div v-if="success" class="mx-4 mt-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-800 backdrop-blur dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200 sm:mx-6">
        <CheckCircle2 :size="16" class="shrink-0 text-emerald-500 dark:text-emerald-400" />
        {{ success }}
      </div>

      <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
        <slot />
      </main>
    </div>

    <div v-if="menuOpen" class="fixed inset-0 z-50 lg:hidden">
      <button class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" aria-label="Fechar menu" @click="menuOpen = false" />
      <aside class="relative flex h-full w-72 flex-col overflow-hidden bg-slate-950 bg-gradient-to-b from-slate-950 to-slate-900 p-4 text-slate-200 shadow-2xl">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-44 bg-[radial-gradient(closest-side_at_50%_0%,rgba(51,141,255,0.16),transparent)]" aria-hidden="true" />
        <div class="relative mb-4 flex items-center justify-between px-2 py-2">
          <div class="flex items-center gap-3 text-lg font-bold text-white">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-lg shadow-brand-500/30 ring-1 ring-white/20"><WalletCards :size="20" /></span>
            Conta Pro
          </div>
          <button class="rounded-lg p-2 text-slate-300 hover:bg-slate-800" aria-label="Fechar menu" @click="menuOpen = false"><X :size="20" /></button>
        </div>
        <nav class="relative flex-1 space-y-1 overflow-y-auto">
          <template v-for="section in sections" :key="section.key">
            <p class="px-3 pb-1 pt-3 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-600">{{ section.label }}</p>
            <Link v-for="item in section.items" :key="item.route" :href="route(item.route)" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium" :class="route().current(item.pattern) ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md shadow-brand-500/25' : 'text-slate-400'" @click="menuOpen = false">
              <component :is="item.icon" :size="18" />{{ item.label }}
            </Link>
          </template>
        </nav>
      </aside>
    </div>
  </div>
</template>