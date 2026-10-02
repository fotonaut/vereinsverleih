<script setup lang="ts">
import { Armchair, CookingPot, Gamepad2, Package, Speaker, Tent, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ category?: string | null; size?: 'md' | 'lg' }>();

// Platzhalter-Optik für Gegenstände ohne Foto: passendes Symbol und Farbverlauf je Kategorie
const themes = [
    {
        match: 'zelt',
        icon: Tent,
        bg: 'from-orange-100 to-amber-200 dark:from-orange-500/20 dark:to-amber-500/10',
        fg: 'text-orange-700 dark:text-orange-300',
    },
    {
        match: 'technik',
        icon: Speaker,
        bg: 'from-indigo-100 to-sky-200 dark:from-indigo-500/20 dark:to-sky-500/10',
        fg: 'text-indigo-700 dark:text-indigo-300',
    },
    {
        match: 'möbel',
        icon: Armchair,
        bg: 'from-amber-100 to-yellow-200 dark:from-amber-500/20 dark:to-yellow-500/10',
        fg: 'text-amber-800 dark:text-amber-300',
    },
    {
        match: 'sport',
        icon: Gamepad2,
        bg: 'from-rose-100 to-pink-200 dark:from-rose-500/20 dark:to-pink-500/10',
        fg: 'text-rose-700 dark:text-rose-300',
    },
    {
        match: 'küche',
        icon: CookingPot,
        bg: 'from-red-100 to-orange-200 dark:from-red-500/20 dark:to-orange-500/10',
        fg: 'text-red-700 dark:text-red-300',
    },
    {
        match: 'werkzeug',
        icon: Wrench,
        bg: 'from-stone-100 to-zinc-200 dark:from-stone-400/15 dark:to-zinc-400/10',
        fg: 'text-stone-700 dark:text-stone-300',
    },
];
const fallback = {
    icon: Package,
    bg: 'from-emerald-50 to-teal-200 dark:from-emerald-500/20 dark:to-teal-500/10',
    fg: 'text-teal-700 dark:text-teal-300',
};

const theme = computed(() => themes.find((t) => props.category?.toLowerCase().includes(t.match)) ?? fallback);
</script>

<template>
    <div
        class="relative flex h-full w-full items-center justify-center overflow-hidden bg-gradient-to-br"
        :class="theme.bg"
        role="img"
        :aria-label="category ?? 'Gegenstand'"
    >
        <span class="absolute -right-6 -top-6 size-24 rounded-full bg-white/40 dark:bg-white/5" />
        <span class="absolute -bottom-8 -left-4 size-28 rounded-full bg-white/30 dark:bg-white/5" />
        <component :is="theme.icon" :class="[theme.fg, size === 'lg' ? 'size-24' : 'size-14']" :stroke-width="1.5" class="relative drop-shadow-sm" />
    </div>
</template>
