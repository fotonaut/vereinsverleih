<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        scene?: 'welcome' | 'register';
    }>(),
    { scene: 'welcome' },
);

const copy = {
    welcome: {
        headline: 'Schön, dass ihr wieder da seid.',
        points: ['Anfragen im Blick behalten', 'Rückgaben, Erinnerungen und Warteliste verwalten', 'Inventar jederzeit aktuell halten'],
    },
    register: {
        headline: 'Inventar teilen, Geld sparen.',
        points: [
            'Kostenlos, ohne Tracking, Open Source',
            'Ihr bestimmt, wer eure Gegenstände leihen darf',
            'Anfragen, Kalender und Warteliste inklusive',
        ],
    },
};

const image = computed(() => `/images/auth-${props.scene}.svg`);
const text = computed(() => copy[props.scene]);
</script>

<template>
    <div class="grid min-h-dvh bg-background lg:grid-cols-2">
        <!-- Illustration (nur Desktop) -->
        <div class="relative hidden overflow-hidden bg-[#123f35] lg:block">
            <img :src="image" alt="" class="absolute inset-0 size-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/5 to-transparent" />
            <Link
                :href="route('home')"
                class="absolute left-8 top-8 flex items-center gap-2 rounded-full bg-black/30 py-1.5 pl-1.5 pr-4 text-white backdrop-blur-sm transition hover:bg-black/40"
            >
                <img src="/favicon.svg" alt="" class="size-8 rounded-lg" />
                <span class="font-semibold">Vereinsverleih</span>
            </Link>
            <div class="absolute inset-x-0 bottom-0 p-10 text-white">
                <h2 class="text-3xl font-bold tracking-tight">{{ text.headline }}</h2>
                <ul class="mt-4 space-y-2 text-sm text-white/90">
                    <li v-for="p in text.points" :key="p" class="flex items-start gap-2">
                        <Check class="mt-0.5 size-4 shrink-0 text-amber-300" />
                        {{ p }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Formular -->
        <div class="flex flex-col justify-center px-6 py-8 sm:px-10">
            <div class="mx-auto w-full max-w-sm space-y-6">
                <div class="space-y-4 lg:hidden">
                    <img :src="image" alt="" class="h-28 w-full rounded-2xl border object-cover object-[50%_62%]" />
                    <Link :href="route('home')" class="flex items-center justify-center gap-2 font-semibold">
                        <img src="/favicon.svg" alt="" class="size-7 rounded-lg" /> Vereinsverleih
                    </Link>
                </div>
                <div class="space-y-2 text-center">
                    <h1 v-if="title" class="text-xl font-semibold tracking-tight">{{ title }}</h1>
                    <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
