<script setup lang="ts">
import WaitlistRow from '@/components/WaitlistRow.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, WaitlistCard } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{ own: WaitlistCard[]; forUs: WaitlistCard[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Meine Warteliste', href: '/verwaltung/warteliste' }];
</script>

<template>
    <Head title="Meine Warteliste" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-8 p-4">
            <h1 class="text-2xl font-bold">Warteliste</h1>

            <section class="space-y-2">
                <h2 class="font-semibold">Worauf wir warten</h2>
                <p v-if="!own.length" class="text-sm text-muted-foreground">
                    Ihr steht auf keiner Warteliste. Ist ein Gegenstand im Wunschzeitraum belegt, könnt ihr euch auf der Gegenstandsseite eintragen.
                </p>
                <ul v-else class="divide-y rounded-xl border">
                    <WaitlistRow v-for="e in own" :key="e.id" :entry="e" />
                </ul>
            </section>

            <section class="space-y-2">
                <h2 class="font-semibold">Wer auf unsere Gegenstände wartet</h2>
                <p v-if="!forUs.length" class="text-sm text-muted-foreground">Aktuell wartet niemand auf eure Gegenstände.</p>
                <template v-else>
                    <ul class="divide-y rounded-xl border">
                        <WaitlistRow v-for="e in forUs" :key="e.id" :entry="e" show-requester />
                    </ul>
                    <p class="text-xs text-muted-foreground">
                        Die Wartenden werden automatisch per E-Mail informiert, sobald ihr Zeitraum frei wird (z. B. nach einer Absage oder Rückgabe).
                    </p>
                </template>
            </section>
        </div>
    </AppLayout>
</template>
