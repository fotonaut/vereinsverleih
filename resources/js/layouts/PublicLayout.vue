<script setup lang="ts">
import FlashMessage from '@/components/FlashMessage.vue';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Boxes } from 'lucide-vue-next';

const page = usePage<SharedData>();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
                <Link :href="route('home')" class="flex items-center gap-2 font-semibold">
                    <Boxes class="size-6 text-primary" /> Vereinsverleih
                </Link>
                <nav class="flex items-center gap-2 text-sm">
                    <Button variant="ghost" as-child><Link :href="route('catalog.index')">Katalog</Link></Button>
                    <Button v-if="page.props.auth.user" as-child><Link :href="route('dashboard')">Mein Verein</Link></Button>
                    <template v-else>
                        <Button variant="ghost" as-child><Link :href="route('login')">Anmelden</Link></Button>
                        <Button as-child><Link :href="route('register')">Verein registrieren</Link></Button>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
            <FlashMessage class="mb-6" />
            <slot />
        </main>

        <footer class="border-t py-6 text-center text-sm text-muted-foreground">
            Vereinsverleih · Open Source (MIT) ·
            <a href="https://github.com/fotonaut/vereinsverleih" class="underline" target="_blank" rel="noopener">GitHub</a> ·
            <Link :href="route('imprint')" class="underline">Impressum</Link> ·
            <Link :href="route('privacy')" class="underline">Datenschutz</Link>
        </footer>
    </div>
</template>
