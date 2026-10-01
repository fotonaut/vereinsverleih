<script setup lang="ts">
import CsvExport from '@/components/CsvExport.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { date } from '@/lib/format';
import type { BreadcrumbItem, Loan } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ loans: Loan[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Meine Anfragen', href: '/verwaltung/ausgang' }];
</script>

<template>
    <Head title="Meine Anfragen" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Unsere Anfragen bei anderen Vereinen</h1>
                <Button as-child><Link :href="route('catalog.index')">Katalog durchsuchen</Link></Button>
            </div>
            <CsvExport direction="outgoing" />
            <p v-if="!loans.length" class="py-10 text-center text-muted-foreground">Ihr habt noch nichts angefragt.</p>
            <article v-for="loan in loans" :key="loan.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-4">
                <div>
                    <h2 class="font-semibold">
                        {{ loan.item.name }} <span class="font-normal text-muted-foreground">× {{ loan.quantity }}</span>
                    </h2>
                    <p class="text-sm text-muted-foreground">{{ loan.item.club?.name }} · {{ date(loan.start_date) }} – {{ date(loan.end_date) }}</p>
                    <p v-if="loan.decision_note" class="mt-1 text-sm">Hinweis: {{ loan.decision_note }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <StatusBadge :status="loan.status" :label="loan.status_label" />
                    <Button size="sm" variant="outline" as-child><Link :href="route('requests.show', loan.token)">Details</Link></Button>
                </div>
            </article>
        </div>
    </AppLayout>
</template>
