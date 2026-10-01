<script setup lang="ts">
import FlashMessage from '@/components/FlashMessage.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { euro } from '@/lib/format';
import type { BreadcrumbItem, Item } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{ items: Item[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Meine Gegenstände', href: '/verwaltung/gegenstaende' }];

const remove = (item: Item) => {
    if (window.confirm(`„${item.name}“ wirklich löschen? Zugehörige Anfragen werden ebenfalls gelöscht.`)) {
        router.delete(route('manage.items.destroy', item.id));
    }
};
</script>

<template>
    <Head title="Meine Gegenstände" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Meine Gegenstände</h1>
                <Button as-child><Link :href="route('manage.items.create')">Gegenstand anlegen</Link></Button>
            </div>
            <FlashMessage />
            <p v-if="!items.length" class="py-10 text-center text-muted-foreground">Noch nichts eingetragen. Lege euren ersten Gegenstand an.</p>
            <div v-else class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="p-3">Name</th>
                            <th class="p-3">Kategorie</th>
                            <th class="p-3">Bestand</th>
                            <th class="p-3">Verleih an</th>
                            <th class="p-3">Kaution</th>
                            <th class="p-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id" class="border-t">
                            <td class="p-3 font-medium">
                                {{ item.name }} <span v-if="!item.active" class="ml-1 rounded bg-gray-100 px-1.5 text-xs text-gray-600">inaktiv</span>
                            </td>
                            <td class="p-3">{{ item.category?.name ?? '–' }}</td>
                            <td class="p-3">{{ item.quantity }}</td>
                            <td class="p-3">{{ item.scope_label }}</td>
                            <td class="p-3">{{ euro(item.deposit_cents) }}</td>
                            <td class="space-x-2 whitespace-nowrap p-3 text-right">
                                <Button size="sm" variant="outline" as-child
                                    ><Link :href="route('manage.items.edit', item.id)">Bearbeiten</Link></Button
                                >
                                <Button size="sm" variant="destructive" @click="remove(item)">Löschen</Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
