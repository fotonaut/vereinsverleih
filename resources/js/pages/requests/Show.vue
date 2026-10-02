<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PhotoStrip from '@/components/PhotoStrip.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { date } from '@/lib/format';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    loan: {
        token: string;
        status: string;
        statusLabel: string;
        quantity: number;
        start_date: string;
        end_date: string;
        message: string | null;
        decision_note: string | null;
        item: { id: number; name: string; location: string | null };
        club: { name: string; email: string };
        canExtend: boolean;
        canCancelSeries: boolean;
        return: {
            condition: string | null;
            note: string | null;
            at: string | null;
            depositReturned: boolean;
            hasDeposit: boolean;
            photos: { id: number; url: string }[];
        } | null;
        series: { token: string; start_date: string; end_date: string; status: string; statusLabel: string; current: boolean }[] | null;
        extension: { status: string; statusLabel: string; requested_end_date: string; message: string | null; decision_note: string | null } | null;
    };
}>();

const extForm = useForm({ requested_end_date: '', message: '' });
const requestExtension = () => extForm.post(route('requests.extend', props.loan.token), { preserveScroll: true, onSuccess: () => extForm.reset() });

const cancelSeries = () => {
    if (window.confirm('Alle noch offenen Termine der Serie stornieren?')) router.post(route('requests.cancel-series', props.loan.token));
};

const cancellable = computed(() => ['unverified', 'pending', 'approved'].includes(props.loan.status));
const cancel = () => {
    if (window.confirm('Anfrage wirklich stornieren?')) router.post(route('requests.cancel', props.loan.token));
};
</script>

<template>
    <Head title="Deine Anfrage" />
    <PublicLayout>
        <div class="mx-auto max-w-xl rounded-xl border p-6">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-xl font-bold">{{ loan.item.name }}</h1>
                <StatusBadge :status="loan.status" :label="loan.statusLabel" />
            </div>
            <p class="text-muted-foreground">von {{ loan.club.name }}</p>
            <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-muted-foreground">Zeitraum</dt>
                    <dd class="font-medium">{{ date(loan.start_date) }} – {{ date(loan.end_date) }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Menge</dt>
                    <dd class="font-medium">{{ loan.quantity }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-muted-foreground">Abholort</dt>
                    <dd class="font-medium">{{ loan.item.location || 'nach Absprache' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-muted-foreground">Kontakt</dt>
                    <dd class="font-medium">{{ loan.club.email }}</dd>
                </div>
                <div v-if="loan.decision_note" class="col-span-2">
                    <dt class="text-muted-foreground">Hinweis des Vereins</dt>
                    <dd class="font-medium">{{ loan.decision_note }}</dd>
                </div>
            </dl>
            <p v-if="loan.status === 'unverified'" class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                Bitte bestätige deine Anfrage über den Link in der E-Mail, die wir dir geschickt haben.
            </p>
            <div
                v-if="loan.return"
                class="mt-4 rounded-md border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-950 dark:bg-emerald-950/30 dark:text-emerald-100"
            >
                <p>
                    <strong
                        >Zurückgegeben<template v-if="loan.return.at"> am {{ date(loan.return.at) }}</template
                        >:</strong
                    >
                    {{ loan.return.condition }}
                </p>
                <p v-if="loan.return.note" class="mt-1">Notiz des Vereins: {{ loan.return.note }}</p>
                <p v-if="loan.return.hasDeposit" class="mt-1">Kaution: {{ loan.return.depositReturned ? 'zurückgegeben' : 'noch offen' }}</p>
                <PhotoStrip :photos="loan.return.photos" />
            </div>
            <div v-if="loan.series" class="mt-4 rounded-md border p-3 text-sm">
                <h2 class="mb-2 font-semibold">Serie mit {{ loan.series.length }} Terminen</h2>
                <ul class="space-y-1">
                    <li v-for="s in loan.series" :key="s.token" class="flex items-center justify-between gap-2" :class="s.current && 'font-semibold'">
                        <Link v-if="!s.current" :href="route('requests.show', s.token)" class="underline"
                            >{{ date(s.start_date) }} – {{ date(s.end_date) }}</Link
                        >
                        <span v-else>{{ date(s.start_date) }} – {{ date(s.end_date) }} (dieser Termin)</span>
                        <StatusBadge :status="s.status" :label="s.statusLabel" />
                    </li>
                </ul>
                <Button v-if="loan.canCancelSeries" variant="destructive" size="sm" class="mt-3" @click="cancelSeries">Ganze Serie stornieren</Button>
            </div>
            <div v-if="loan.extension" class="mt-4 rounded-md border p-3 text-sm">
                <strong>Verlängerung bis {{ date(loan.extension.requested_end_date) }}:</strong> {{ loan.extension.statusLabel }}
                <p v-if="loan.extension.decision_note" class="mt-1 text-muted-foreground">Hinweis des Vereins: {{ loan.extension.decision_note }}</p>
            </div>
            <form v-if="loan.canExtend" class="mt-4 grid gap-3 rounded-md border p-3" @submit.prevent="requestExtension">
                <h2 class="font-semibold">Länger ausleihen?</h2>
                <div class="grid gap-2">
                    <Label for="requested_end_date">Neues Rückgabedatum</Label>
                    <Input id="requested_end_date" type="date" :min="loan.end_date" v-model="extForm.requested_end_date" required />
                    <InputError :message="extForm.errors.requested_end_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="ext_message">Nachricht (optional)</Label>
                    <Input id="ext_message" v-model="extForm.message" />
                </div>
                <Button type="submit" variant="outline" :disabled="extForm.processing">Verlängerung anfragen</Button>
            </form>
            <p class="mt-4 text-xs text-muted-foreground">Speichere diese Seite als Lesezeichen, um den Status später zu prüfen.</p>
            <div class="mt-6 flex gap-2">
                <Button variant="outline" as-child><Link :href="route('catalog.index')">Zum Katalog</Link></Button>
                <Button v-if="cancellable" variant="destructive" @click="cancel">Stornieren</Button>
            </div>
        </div>
    </PublicLayout>
</template>
