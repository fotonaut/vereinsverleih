<script setup lang="ts">
import CsvExport from '@/components/CsvExport.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { date } from '@/lib/format';
import type { BreadcrumbItem, Loan } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

defineProps<{ loans: Loan[]; conditions: { value: string; label: string }[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Eingehende Anfragen', href: '/verwaltung/eingang' }];
const notes = reactive<Record<number, string>>({});

// Rückgabe-Protokoll: statt sofort zu speichern erst Zustand/Notiz/Kaution abfragen
const returning = ref<number | null>(null);
const returnForm = reactive({ return_condition: 'ok', return_note: '', deposit_returned: false });
const startReturn = (loan: Loan) => {
    Object.assign(returnForm, { return_condition: 'ok', return_note: '', deposit_returned: false });
    returning.value = loan.id;
};
const confirmReturn = (loan: Loan) =>
    router.patch(
        route('manage.incoming.update', loan.id),
        { status: 'returned', decision_note: notes[loan.id] ?? '', ...returnForm },
        { preserveScroll: true, onSuccess: () => (returning.value = null) },
    );
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

const decideSeries = (loan: Loan, decision: 'approved' | 'declined') =>
    router.patch(route('manage.incoming.series', loan.series_id!), { decision, decision_note: notes[loan.id] ?? '' }, { preserveScroll: true });

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
                        <span v-if="loan.series_total" class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800"
                            >Serie · {{ loan.series_total }} Termine</span
                        >
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

                <div
                    v-if="loan.series_total && loan.status === 'pending'"
                    class="mt-3 flex flex-wrap items-center gap-2 rounded-md border border-blue-300 bg-blue-50 p-3 text-sm dark:bg-blue-950/30"
                >
                    <span class="mr-auto">Teil einer Serie mit {{ loan.series_total }} Terminen – gemeinsam entscheiden:</span>
                    <Button size="sm" @click="decideSeries(loan, 'approved')">Alle offenen Termine genehmigen</Button>
                    <Button size="sm" variant="destructive" @click="decideSeries(loan, 'declined')">Ganze Serie ablehnen</Button>
                </div>

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
                        @click="s === 'returned' ? startReturn(loan) : change(loan, s)"
                        >{{ labels[s] }}</Button
                    >
                </div>

                <div
                    v-if="returning === loan.id"
                    class="mt-3 grid gap-3 rounded-md border border-emerald-300 bg-emerald-50 p-3 text-sm dark:bg-emerald-950/30"
                >
                    <h3 class="font-semibold">Rückgabe protokollieren</h3>
                    <label class="grid gap-1"
                        >Zustand
                        <select v-model="returnForm.return_condition" class="h-9 rounded-md border bg-transparent px-2">
                            <option v-for="c in conditions" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </label>
                    <label class="grid gap-1"
                        >Notiz (optional, die Ausleihenden sehen sie)
                        <input
                            v-model="returnForm.return_note"
                            class="h-9 rounded-md border bg-transparent px-3"
                            placeholder="z. B. Riss an der Seitenwand"
                        />
                    </label>
                    <label class="flex items-center gap-2"
                        ><input v-model="returnForm.deposit_returned" type="checkbox" /> Kaution zurückgegeben</label
                    >
                    <div class="flex gap-2">
                        <Button size="sm" @click="confirmReturn(loan)">Rückgabe bestätigen</Button>
                        <Button size="sm" variant="outline" @click="returning = null">Abbrechen</Button>
                    </div>
                </div>

                <p v-if="loan.status === 'returned' && loan.return_condition_label" class="mt-2 text-xs text-muted-foreground">
                    Zurückgegeben: {{ loan.return_condition_label }}
                </p>
            </article>
        </div>
    </AppLayout>
</template>
