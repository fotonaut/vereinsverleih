<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { date } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';
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
    };
}>();

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
            <p class="mt-4 text-xs text-muted-foreground">Speichere diese Seite als Lesezeichen, um den Status später zu prüfen.</p>
            <div class="mt-6 flex gap-2">
                <Button variant="outline" as-child><Link :href="route('catalog.index')">Zum Katalog</Link></Button>
                <Button v-if="cancellable" variant="destructive" @click="cancel">Stornieren</Button>
            </div>
        </div>
    </PublicLayout>
</template>
