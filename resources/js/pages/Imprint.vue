<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Impressum" />
    <PublicLayout>
        <div class="prose mx-auto max-w-2xl">
            <h1 class="mb-4 text-2xl font-bold">Impressum</h1>
            <p class="text-muted-foreground">
                Bitte hier die Angaben gemäß § 5 DDG sowie die Datenschutzerklärung des Betreibers dieser Instanz eintragen (Datei
                <code>resources/js/pages/Imprint.vue</code>).
            </p>
        </div>
    </PublicLayout>
</template>
