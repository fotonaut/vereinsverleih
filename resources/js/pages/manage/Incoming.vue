<script setup lang="ts">
import CsvExport from '@/components/CsvExport.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { date } from '@/lib/format';
import type { BreadcrumbItem, Loan } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineProps<{ loans: Loan[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Eingehende Anfragen', href: '/verwaltung/eingang' }];
const notes = reactive<Record<number, string>>({});
const errors = usePage().props.errors as Record<string, string>;

const labels: Record<string, string> = {
    approved: 'Genehmigen',
    declined: 'Ablehnen',
    picked_up: 'Als abgeholt markieren',
    returned: 'Als zurückgegeben markieren',
    cancelled: 'Stornieren',
};

const decide = (loan: Loan, decision: 'approved' | 'declined') =>
    router.patch(
        route('manage.incoming.extension', [loan.id, loan.pending_extension!.id]),
        { decision, decision_note: notes[loan.id] ?? '' },
        { preserveScroll: true },
    );

const change = (loan: Loan, status: string) =>
    router.patch(route('manage.incoming.update', loan.id), { status, decision_note: notes[loan.id] ?? '' }, { preserveScroll: true });
</script>

<template>
    <Head title="Eingehende Anfragen" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <h1 class="text-2xl font-bold">Eingehende Anfragen</h1>
            <FlashMessage />
            <CsvExport direction="incoming" />
            <p v-if="errors.status" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ errors.status }}</p>
            <p v-if="!loans.length" class="py-10 text-center text-muted-foreground">Noch keine Anfragen.</p>

            <article v-for="loan in loans" :key="loan.id" class="rounded-xl border p-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="font-semibold">
                            {{ loan.item.name }} <span class="font-normal text-muted-foreground">× {{ loan.quantity }}</span>
                        </h2>
                        <p class="text-sm">{{ date(loan.start_date) }} – {{ date(loan.end_date) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="loan.overdue" class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800"
                            >Überfällig</span
                        >
                        <StatusBadge :status="loan.status" :label="loan.status_label" />
                    </div>
                </div>
                <p class="mt-2 text-sm">
                    <strong>{{ loan.requester_name }}</strong> ({{ loan.requester_type === 'club' ? 'Verein' : 'Privatperson' }}) ·
                    <a :href="`mailto:${loan.requester_email}`" class="underline">{{ loan.requester_email }}</a>
                    <span v-if="loan.requester_phone"> · {{ loan.requester_phone }}</span>
                </p>
                <p v-if="loan.message" class="mt-2 rounded-md bg-muted p-2 text-sm">{{ loan.message }}</p>

                <div v-if="loan.pending_extension" class="mt-3 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm dark:bg-amber-950/30">
                    <p>
                        <strong>Verlängerung angefragt:</strong> bis {{ date(loan.pending_extension.requested_end_date) }} (statt
                        {{ date(loan.pending_extension.previous_end_date) }})
                        <span v-if="loan.pending_extension.message">· „{{ loan.pending_extension.message }}“</span>
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <Button size="sm" @click="decide(loan, 'approved')">Verlängerung genehmigen</Button>
                        <Button size="sm" variant="destructive" @click="decide(loan, 'declined')">Ablehnen</Button>
                    </div>
                </div>

                <div v-if="loan.next?.length" class="mt-3 flex flex-wrap items-center gap-2">
                    <input
                        v-model="notes[loan.id]"
                        placeholder="Hinweis an Anfragende (optional)"
                        class="h-9 min-w-48 flex-1 rounded-md border bg-transparent px-3 text-sm"
                    />
                    <Button
                        v-for="s in loan.next"
                        :key="s"
                        size="sm"
                        :variant="s === 'declined' || s === 'cancelled' ? 'destructive' : 'default'"
                        @click="change(loan, s)"
                        >{{ labels[s] }}</Button
                    >
                </div>
            </article>
        </div>
    </AppLayout>
</template>
