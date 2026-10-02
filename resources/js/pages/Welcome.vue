<script setup lang="ts">
import ItemCard from '@/components/ItemCard.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { Item } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarCheck, PackagePlus, Search } from 'lucide-vue-next';

defineProps<{ latest: Item[]; stats: { clubs: number; items: number } }>();

const steps = [
    {
        icon: PackagePlus,
        title: '1. Inventar eintragen',
        text: 'Gegenstände mit Foto, Bestand und Kaution anlegen und festlegen, wer sie leihen darf.',
    },
    {
        icon: Search,
        title: '2. Im Katalog finden',
        text: 'Suchen, filtern und im Kalender sehen, wann etwas frei ist. Belegtes lässt sich vormerken.',
    },
    {
        icon: CalendarCheck,
        title: '3. Anfragen & leihen',
        text: 'Anfrage senden, Zusage per E-Mail erhalten, abholen, zurückgeben – alles nachvollziehbar.',
    },
];
</script>

<template>
    <Head title="Vereinsinventar teilen" />
    <PublicLayout>
        <section class="grid items-center gap-8 py-8 lg:grid-cols-2 lg:py-12">
            <div class="text-center lg:text-left">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                    Was ein Verein hat, kann der <span class="text-primary">nächste leihen</span>.
                </h1>
                <p class="mt-4 text-lg text-muted-foreground">
                    Zelte, Bänke, Technik, Spiele: Vereine tragen ihr Inventar ein und legen fest, wer es leihen darf — andere Vereine, Privatpersonen
                    oder niemand.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3 lg:justify-start">
                    <Button size="lg" as-child><Link :href="route('catalog.index')">Katalog ansehen</Link></Button>
                    <Button size="lg" variant="outline" as-child><Link :href="route('register')">Verein registrieren</Link></Button>
                </div>
                <p class="mt-6 text-sm text-muted-foreground">{{ stats.clubs }} Vereine · {{ stats.items }} verleihbare Gegenstände</p>
            </div>
            <img
                src="/images/hero.svg"
                width="800"
                height="480"
                alt="Festzelt, Hüpfburg, Bierzeltgarnitur und Lautsprecher auf einer Wiese"
                class="w-full rounded-2xl border shadow-sm"
                fetchpriority="high"
            />
        </section>

        <section class="grid gap-4 py-6 sm:grid-cols-3">
            <div v-for="step in steps" :key="step.title" class="rounded-xl border bg-card p-5">
                <span class="mb-3 grid size-10 place-items-center rounded-lg bg-primary/10 text-primary"
                    ><component :is="step.icon" class="size-5"
                /></span>
                <h2 class="font-semibold">{{ step.title }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ step.text }}</p>
            </div>
        </section>

        <section v-if="latest.length">
            <h2 class="mb-4 text-xl font-semibold">Neu im Katalog</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <ItemCard v-for="item in latest" :key="item.id" :item="item" />
            </div>
        </section>
    </PublicLayout>
</template>
