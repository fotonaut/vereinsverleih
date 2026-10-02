<script setup lang="ts">
import AvailabilityCalendar, { type CalendarReservation } from '@/components/AvailabilityCalendar.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { date } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    item: { id: number; name: string; quantity: number };
    reservations: CalendarReservation[];
    waitlist: { id: number; requester: string; quantity: number; start_date: string; end_date: string; series: string | null }[];
    loans: { id: number; requester: string; status: string; status_label: string; quantity: number; start_date: string; end_date: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Meine Gegenstände', href: '/verwaltung/gegenstaende' },
    { title: `${props.item.name}: Kalender`, href: '#' },
];
</script>

<template>
    <Head :title="`Kalender: ${item.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-bold">{{ item.name }}</h1>
                    <p class="text-sm text-muted-foreground">Bestand: {{ item.quantity }} Stück</p>
                </div>
                <Button variant="outline" as-child><Link :href="route('manage.incoming.index')">Zu den Anfragen</Link></Button>
            </div>

            <AvailabilityCalendar :quantity="item.quantity" :reservations="reservations" />

            <section>
                <h2 class="mb-2 font-semibold">Belegungen und offene Anfragen</h2>
                <p v-if="!loans.length" class="text-sm text-muted-foreground">Aktuell keine Belegungen.</p>
                <ul v-else class="divide-y rounded-xl border text-sm">
                    <li v-for="l in loans" :key="l.id" class="flex flex-wrap items-center justify-between gap-2 p-3">
                        <span>
                            <strong>{{ date(l.start_date) }} – {{ date(l.end_date) }}</strong> · {{ l.requester }} · {{ l.quantity }}×
                        </span>
                        <StatusBadge :status="l.status" :label="l.status_label" />
                    </li>
                </ul>
            </section>

            <section v-if="waitlist.length">
                <h2 class="mb-2 font-semibold">Warteliste</h2>
                <ul class="divide-y rounded-xl border text-sm">
                    <li v-for="w in waitlist" :key="w.id" class="p-3">
                        <strong>{{ date(w.start_date) }} – {{ date(w.end_date) }}</strong> · {{ w.requester }} · {{ w.quantity }}×<span
                            v-if="w.series"
                            class="text-muted-foreground"
                        >
                            · Serie {{ w.series }}</span
                        >
                    </li>
                </ul>
                <p class="mt-2 text-xs text-muted-foreground">Wird etwas frei, wird die erste passende Person automatisch per E-Mail informiert.</p>
            </section>
        </div>
    </AppLayout>
</template>
