<script setup lang="ts">
import CategoryArt from '@/components/CategoryArt.vue';
import { euro } from '@/lib/format';
import type { Item } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{ item: Item }>();

const scopeText: Record<string, string> = {
    clubs: 'Für Vereine',
    private: 'Für Privatpersonen',
    both: 'Vereine & Privat',
};
</script>

<template>
    <Link :href="route('catalog.show', item.id)" class="group flex flex-col overflow-hidden rounded-xl border bg-card transition hover:shadow-md">
        <div class="aspect-[4/3] bg-muted">
            <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-full w-full object-cover" loading="lazy" />
            <CategoryArt v-else :category="item.category?.name" />
        </div>
        <div class="flex flex-1 flex-col gap-1 p-4">
            <h3 class="font-semibold group-hover:underline">{{ item.name }}</h3>
            <p class="text-sm text-muted-foreground">
                {{ item.club?.name }}<span v-if="item.club?.city"> · {{ item.club.city }}</span>
            </p>
            <div class="mt-auto flex flex-wrap items-center gap-2 pt-3 text-xs">
                <span class="rounded-full bg-primary/10 px-2 py-0.5 font-medium">{{ scopeText[item.lending_scope] }}</span>
                <span class="text-muted-foreground">{{ item.quantity }}× · Kaution {{ euro(item.deposit_cents) }}</span>
            </div>
        </div>
    </Link>
</template>
