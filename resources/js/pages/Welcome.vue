<script setup lang="ts">
import ItemCard from '@/components/ItemCard.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { Item } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ latest: Item[]; stats: { clubs: number; items: number } }>();
</script>

<template>
    <Head title="Vereinsinventar teilen" />
    <PublicLayout>
        <section class="py-10 text-center">
            <h1 class="mx-auto max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">Was ein Verein hat, kann der nächste leihen.</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-muted-foreground">
                Zelte, Bänke, Technik, Spiele: Vereine tragen ihr Inventar ein und legen fest, wer es leihen darf — andere Vereine, Privatpersonen
                oder niemand.
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <Button size="lg" as-child><Link :href="route('catalog.index')">Katalog ansehen</Link></Button>
                <Button size="lg" variant="outline" as-child><Link :href="route('register')">Verein registrieren</Link></Button>
            </div>
            <p class="mt-6 text-sm text-muted-foreground">{{ stats.clubs }} Vereine · {{ stats.items }} verleihbare Gegenstände</p>
        </section>

        <section v-if="latest.length">
            <h2 class="mb-4 text-xl font-semibold">Neu im Katalog</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <ItemCard v-for="item in latest" :key="item.id" :item="item" />
            </div>
        </section>
    </PublicLayout>
</template>
