<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { Operator } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ operator: Operator }>();
</script>

<template>
    <Head title="Impressum" />
    <PublicLayout>
        <div class="mx-auto max-w-2xl space-y-6">
            <h1 class="text-2xl font-bold">Impressum</h1>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">Angaben gemäß § 5 DDG</h2>
                <p v-if="operator.brand" class="font-medium">{{ operator.brand }}</p>
                <p v-if="operator.name || operator.street">
                    <template v-if="operator.name">{{ operator.name }}<br /></template>
                    <template v-if="operator.street">{{ operator.street }}<br /></template>
                    <template v-if="operator.city">{{ operator.city }}<br /></template>
                    {{ operator.country }}
                </p>
                <p v-else class="text-sm text-amber-700">Die Betreiberangaben sind für diese Instanz noch nicht hinterlegt.</p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">Kontakt</h2>
                <p>
                    <template v-if="operator.email"
                        >E-Mail: <a :href="`mailto:${operator.email}`" class="underline">{{ operator.email }}</a
                        ><br
                    /></template>
                    <template v-if="operator.phone">Telefon: {{ operator.phone }}</template>
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h2>
                <p>{{ operator.name || operator.brand }} (Anschrift wie oben)</p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">Inhalte der Vereine</h2>
                <p class="text-sm text-muted-foreground">
                    Gegenstände, Beschreibungen und Fotos im Katalog werden von den jeweiligen Vereinen eingestellt und verantwortet. Der Betreiber
                    prüft diese Inhalte nicht vorab; bei Hinweisen auf Rechtsverletzungen werden entsprechende Inhalte unverzüglich entfernt.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">Haftung für Links</h2>
                <p class="text-sm text-muted-foreground">
                    Diese Seite kann Links zu externen Webseiten Dritter enthalten, auf deren Inhalte der Betreiber keinen Einfluss hat. Für diese
                    fremden Inhalte wird keine Gewähr übernommen.
                </p>
            </section>

            <p class="text-sm text-muted-foreground">Siehe auch: <Link :href="route('privacy')" class="underline">Datenschutzerklärung</Link></p>
        </div>
    </PublicLayout>
</template>
