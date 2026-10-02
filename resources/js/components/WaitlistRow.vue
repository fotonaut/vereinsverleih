<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { date } from '@/lib/format';
import type { WaitlistCard } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{ entry: WaitlistCard; showRequester?: boolean }>();
</script>

<template>
    <li
        class="flex flex-wrap items-center justify-between gap-2 p-3 text-sm"
        :class="entry.status === 'notified' && 'bg-emerald-50 dark:bg-emerald-950/30'"
    >
        <div>
            <p class="font-medium">
                {{ entry.item.name }} <span class="font-normal text-muted-foreground">× {{ entry.quantity }}</span>
                <span v-if="entry.series" class="ml-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800"
                    >Serie {{ entry.series }}</span
                >
            </p>
            <p class="text-muted-foreground">
                <template v-if="showRequester">{{ entry.requester }} · </template>{{ date(entry.start_date) }} – {{ date(entry.end_date) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span
                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="entry.status === 'notified' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >{{ entry.status === 'notified' ? 'Jetzt frei!' : 'Wartet' }}</span
            >
            <Button v-if="entry.status === 'notified'" size="sm" as-child
                ><Link :href="route('catalog.show', entry.item.id)">Jetzt anfragen</Link></Button
            >
            <Button v-if="!showRequester" size="sm" variant="outline" as-child
                ><Link :href="route('waitlist.show', entry.token)">Details</Link></Button
            >
        </div>
    </li>
</template>
