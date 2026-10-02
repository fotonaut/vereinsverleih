<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import ItemCard from '@/components/ItemCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { selectClass } from '@/lib/format';
import type { Item, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps<{
    items: Paginated<Item>;
    filters: Record<string, string | undefined>;
    categories: { id: number; name: string }[];
    clubs: { id: number; name: string }[];
}>();

const f = reactive({
    q: props.filters.q ?? '',
    category: props.filters.category ?? '',
    club: props.filters.club ?? '',
    for: props.filters.for ?? '',
});

const apply = () => router.get(route('catalog.index'), Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true });
</script>

<template>
    <Head title="Katalog" />
    <PublicLayout>
        <h1 class="mb-4 text-2xl font-bold">Katalog</h1>
        <form class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="apply">
            <Input v-model="f.q" placeholder="Suchen …" class="lg:col-span-2" />
            <select v-model="f.category" :class="selectClass" @change="apply" aria-label="Kategorie">
                <option value="">Alle Kategorien</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="f.club" :class="selectClass" @change="apply" aria-label="Verein">
                <option value="">Alle Vereine</option>
                <option v-for="c in clubs" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="f.for" :class="selectClass" @change="apply" aria-label="Verleih an">
                <option value="">Verleih an alle</option>
                <option value="club">für Vereine</option>
                <option value="private">für Privatpersonen</option>
            </select>
        </form>

        <EmptyState v-if="!items.data.length" title="Nichts gefunden">
            Zu diesen Filtern gibt es keinen verleihbaren Gegenstand. Versuche einen anderen Suchbegriff oder setze die Filter zurück.
            <template #actions
                ><Button variant="outline" as-child><Link :href="route('catalog.index')">Filter zurücksetzen</Link></Button></template
            >
        </EmptyState>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <ItemCard v-for="item in items.data" :key="item.id" :item="item" />
        </div>

        <nav v-if="items.links.length > 3" class="mt-8 flex flex-wrap justify-center gap-1">
            <template v-for="l in items.links" :key="l.label">
                <Button v-if="l.url" :variant="l.active ? 'default' : 'outline'" size="sm" as-child>
                    <Link :href="l.url" preserve-state><span v-html="l.label" /></Link>
                </Button>
            </template>
        </nav>
    </PublicLayout>
</template>
