<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps<{ stats: { items: number; pending: number; active: number; outgoing: number } }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Übersicht', href: '/dashboard' }];
const user = usePage<SharedData>().props.auth.user;
</script>

<template>
    <Head title="Übersicht" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <div>
                <h1 class="text-2xl font-bold">{{ user.club?.name ?? 'Willkommen' }}</h1>
                <p class="text-muted-foreground">Hallo {{ user.name }}!</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link :href="route('manage.items.index')" class="rounded-xl border p-5 transition hover:shadow-md">
                    <div class="text-3xl font-bold">{{ stats.items }}</div>
                    <div class="text-sm text-muted-foreground">Gegenstände im Bestand</div>
                </Link>
                <Link
                    :href="route('manage.incoming.index')"
                    class="rounded-xl border p-5 transition hover:shadow-md"
                    :class="stats.pending && 'border-amber-400 bg-amber-50'"
                >
                    <div class="text-3xl font-bold">{{ stats.pending }}</div>
                    <div class="text-sm text-muted-foreground">Offene Anfragen an uns</div>
                </Link>
                <Link :href="route('manage.incoming.index')" class="rounded-xl border p-5 transition hover:shadow-md">
                    <div class="text-3xl font-bold">{{ stats.active }}</div>
                    <div class="text-sm text-muted-foreground">Laufende Verleihungen</div>
                </Link>
                <Link :href="route('manage.outgoing.index')" class="rounded-xl border p-5 transition hover:shadow-md">
                    <div class="text-3xl font-bold">{{ stats.outgoing }}</div>
                    <div class="text-sm text-muted-foreground">Unsere offenen Anfragen</div>
                </Link>
            </div>
            <div class="flex gap-2">
                <Button as-child><Link :href="route('manage.items.create')">Gegenstand anlegen</Link></Button>
                <Button variant="outline" as-child><Link :href="route('catalog.index')">Katalog durchsuchen</Link></Button>
            </div>
        </div>
    </AppLayout>
</template>
