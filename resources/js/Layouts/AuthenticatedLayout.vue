<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import type { Component } from 'vue';
import {
    AlertCircle,
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
    Landmark,
    LayoutDashboard,
    Menu,
    Moon,
    PanelLeft,
    PanelLeftClose,
    PieChart,
    Repeat,
    Scale,
    Sun,
    Tags,
    Target,
    Upload,
    WalletCards,
    X,
} from '@lucide/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const menuOpen = ref(false);
const isDark = ref(false);
const flashTitle = computed(() => page.props.flash?.success as string | null);
const flashDetail = computed(() => page.props.flash?.detail as string | null);
const flashError = computed(() => page.props.flash?.error as string | null);
const user = computed(() => page.props.auth.user as { name: string; email: string });
const initial = computed(() => user.value.name.charAt(0).toUpperCase());

const toast = ref<{ title: string; detail: string | null; tone: 'success' | 'error' } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;

onUnmounted(() => { if (toastTimer) clearTimeout(toastTimer); });

const dismissToast = () => {
    toast.value = null;
    if (toastTimer) clearTimeout(toastTimer);
};

watch([flashTitle, flashDetail, flashError], ([title, detail, error]) => {
    if (!title && !error) return;
    toast.value = {
        title: title ?? (error as string),
        detail,
        tone: title ? 'success' : 'error',
    };
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.value = null;
    }, 5000);
});

type NavItem = { label: string; route: string; pattern: string; icon: Component };
type NavSection = { key: string; label: string; items: NavItem[] };

const sections: NavSection[] = [
    { key: 'inicio', label: 'Visão geral', items: [
        { label: 'Dashboard', route: 'dashboard', pattern: 'dashboard', icon: LayoutDashboard },
    ] },
    { key: 'finance', label: 'Financeiro', items: [
        { label: 'Lançamentos', route: 'transactions.index', pattern: 'transactions.*', icon: ArrowLeftRight },
        { label: 'Contas e cartões', route: 'accounts.index', pattern: 'accounts.*', icon: WalletCards },
    ] },
    { key: 'planning', label: 'Planejamento', items: [
        { label: 'Agenda', route: 'agenda.index', pattern: 'agenda.*', icon: CalendarClock },
        { label: 'Receitas futuras', route: 'expected-incomes.index', pattern: 'expected-incomes.*', icon: CalendarCheck2 },
        { label: 'Recorrências', route: 'recurring.index', pattern: 'recurring.*', icon: Repeat },
        { label: 'Parcelas', route: 'installments.index', pattern: 'installments.*', icon: CalendarRange },
        { label: 'Orçamentos', route: 'budgets.index', pattern: 'budgets.*', icon: Gauge },
        { label: 'Metas', route: 'goals.index', pattern: 'goals.*', icon: Target },
    ] },
    { key: 'investments', label: 'Investimentos', items: [
        { label: 'Carteiras', route: 'portfolios.index', pattern: 'portfolios.*', icon: PieChart },
        { label: 'Ativos', route: 'assets.index', pattern: 'assets.*', icon: CandlestickChart },
        { label: 'Corretoras', route: 'brokers.index', pattern: 'brokers.*', icon: Building2 },
    ] },
    { key: 'data', label: 'Análises e dados', items: [
        { label: 'Relatórios', route: 'reports.index', pattern: 'reports.*', icon: ChartNoAxesCombined },
        { label: 'Importações', route: 'imports.index', pattern: 'imports.*', icon: Upload },
        { label: 'Conciliações', route: 'reconciliations.index', pattern: 'reconciliations.*', icon: Scale },
    ] },
    { key: 'system', label: 'Cadastros', items: [
        { label: 'Categorias', route: 'categories.index', pattern: 'categories.*', icon: Tags },
        { label: 'Bancos', route: 'banks.index', pattern: 'banks.*', icon: Landmark },
    ] },
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
  <div class="relative min-h-screen text-stone-900 dark:text-slate-100">
    <a href="#main-content" class="fixed left-4 top-2 z-[70] -translate-y-24 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-brand-700 shadow focus:translate-y-0">Ir para o conteúdo</a>
    <aside
      class="fixed inset-y-0 left-0 z-40 hidden flex-col overflow-hidden border-r border-slate-800/80 bg-slate-950 text-slate-200 transition-[width] duration-200 lg:flex"
      :class="collapsed ? 'w-[4.5rem]' : 'w-64'"
    >
      <Link :href="route('dashboard')" class="relative flex h-14 shrink-0 items-center border-b border-slate-800/70" :class="collapsed ? 'justify-center px-0' : 'gap-3 px-5'">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-600 text-white">
          <WalletCards :size="19" />
        </span>
        <span v-if="!collapsed">
          <span class="block text-[15px] font-bold leading-tight tracking-tight text-white">Conta Pro</span>
          <span class="block text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-400">Finanças claras</span>
        </span>
      </Link>

      <nav aria-label="Menu principal" class="relative flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <template v-for="(section, index) in sections" :key="section.key">
          <div v-if="index > 0" class="my-2 h-px bg-slate-800" />
          <button
            v-if="!collapsed"
            class="group flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400 transition hover:text-slate-200"
            :aria-expanded="isSectionOpen(section.key)"
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
            :aria-current="route().current(item.pattern) ? 'page' : undefined"
            class="flex items-center rounded-xl text-sm font-medium transition"
            :class="[
              collapsed ? 'justify-center py-2' : 'gap-3 px-3 py-2',
              route().current(item.pattern)
                ? 'bg-brand-600 text-white'
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
          <div class="relative flex items-center gap-2.5">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-600 text-xs font-bold text-white">{{ initial }}</span>
            <div class="min-w-0">
              <p class="truncate text-xs font-semibold text-slate-100">{{ user.name }}</p>
              <p class="mt-0.5 truncate text-xs text-slate-400">{{ user.email }}</p>
            </div>
          </div>
        </div>
        <div v-else class="grid place-items-center py-1" :title="user.email">
          <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-500/20 text-xs font-bold text-brand-300 ring-2 ring-brand-400/30">{{ initial }}</span>
        </div>
      </div>
    </aside>

    <div class="relative transition-[padding] duration-200" :class="collapsed ? 'lg:pl-[4.5rem]' : 'lg:pl-64'">
      <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-stone-200/70 bg-white px-4 dark:border-slate-800/80 dark:bg-slate-950 sm:px-6">
        <div class="flex min-w-0 items-center gap-2">
          <button class="rounded-lg p-2 text-stone-600 hover:bg-stone-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden" aria-label="Abrir menu" @click="menuOpen = true"><Menu :size="20" /></button>
          <button class="hidden rounded-lg p-2 text-stone-600 dark:text-slate-400 transition hover:bg-stone-100 dark:hover:bg-slate-800 lg:block" aria-label="Alternar menu lateral" @click="toggleCollapsed">
            <PanelLeftClose v-if="!collapsed" :size="18" />
            <PanelLeft v-else :size="18" />
          </button>
          <div v-if="currentContext" class="hidden min-w-0 items-center gap-1.5 text-sm text-stone-600 dark:text-slate-400 sm:flex">
            <span class="truncate">{{ currentContext.section }}</span>
            <span class="text-stone-300 dark:text-slate-600">/</span>
            <span class="truncate font-semibold text-stone-700 dark:text-slate-200">{{ currentContext.item }}</span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button class="rounded-lg border border-stone-200 p-2 text-stone-600 dark:text-slate-400 transition hover:bg-stone-100 dark:border-slate-700 dark:hover:bg-slate-800" aria-label="Alternar tema" @click="applyTheme(!isDark)">
            <Sun v-if="isDark" :size="17" />
            <Moon v-else :size="17" />
          </button>
          <Dropdown align="right" width="48">
            <template #trigger>
              <button class="flex items-center gap-2 rounded-xl border border-transparent px-2 py-1.5 text-sm font-semibold transition hover:border-stone-200 hover:bg-stone-100 dark:hover:border-slate-700 dark:hover:bg-slate-800">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ initial }}</span>
                <span class="hidden sm:inline">{{ user.name }}</span>
                <ChevronDown :size="14" class="text-stone-600 dark:text-slate-400" />
              </button>
            </template>
            <template #content>
              <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
              <DropdownLink :href="route('logout')" method="post" as="button">Sair</DropdownLink>
            </template>
          </Dropdown>
        </div>
      </header>

      <div v-if="toast" role="status" aria-live="polite" aria-atomic="true" class="fixed right-4 top-16 z-[60] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-1 rounded-2xl border bg-white/95 p-4 shadow-2xl backdrop-blur sm:right-6" :class="toast.tone === 'error' ? 'border-rose-200 shadow-rose-900/10 dark:border-rose-900/70 dark:bg-slate-900/95' : 'border-emerald-200 shadow-emerald-900/10 dark:border-emerald-900/70 dark:bg-slate-900/95'">
        <div class="flex items-start gap-3">
          <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl" :class="toast.tone === 'error' ? 'bg-rose-100 text-rose-700 dark:text-rose-300 dark:bg-rose-950/70 ' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300'">
            <AlertCircle v-if="toast.tone === 'error'" :size="17" />
            <CheckCircle2 v-else :size="17" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold" :class="toast.tone === 'error' ? 'text-rose-900 dark:text-rose-100' : 'text-emerald-900 '">{{ toast.title }}</p>
            <p v-if="toast.detail" class="mt-1 text-xs leading-snug text-stone-600 dark:text-slate-400">{{ toast.detail }}</p>
          </div>
          <button type="button" class="-mr-1 -mt-1 grid h-11 w-11 shrink-0 place-items-center rounded-lg text-stone-600 dark:text-slate-400 transition hover:bg-stone-100 hover:text-stone-700 dark:hover:bg-slate-800" aria-label="Fechar notificação" @click="dismissToast"><X :size="15" /></button>
        </div>
      </div>

      <main id="main-content" tabindex="-1" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
        <slot />
      </main>
    </div>

    <Modal :show="menuOpen" max-width="sm" title="Navegação" @close="menuOpen = false">
      <nav aria-label="Menu principal no celular" class="px-4 pb-5">
        <section v-for="section in sections" :key="section.key">
          <h2 class="px-3 pb-1 pt-4 text-xs font-semibold text-stone-600 dark:text-slate-400">{{ section.label }}</h2>
          <Link v-for="item in section.items" :key="item.route" :href="route(item.route)" :aria-current="route().current(item.pattern) ? 'page' : undefined" class="flex min-h-11 items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium" :class="route().current(item.pattern) ? 'bg-brand-600 text-white' : 'text-stone-700 hover:bg-stone-100 dark:text-slate-200 dark:hover:bg-slate-800'" @click="menuOpen = false">
            <component :is="item.icon" :size="18" aria-hidden="true" />{{ item.label }}
          </Link>
        </section>
      </nav>
    </Modal>
  </div>
</template>
