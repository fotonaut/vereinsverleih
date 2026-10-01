<script setup lang="ts">
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { date } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    entry: {
        token: string;
        status: string;
        statusLabel: string;
        quantity: number;
        start_date: string;
        end_date: string;
        item: { id: number; name: string; club: string };
        open: boolean;
    };
}>();

const cancel = () => {
    if (window.confirm('Wirklich von der Warteliste abmelden?')) router.post(route('waitlist.cancel', props.entry.token));
};
</script>

<template>
    <Head title="Warteliste" />
    <PublicLayout>
        <div class="mx-auto max-w-xl rounded-xl border p-6">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-xl font-bold">{{ entry.item.name }}</h1>
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">{{ entry.statusLabel }}</span>
            </div>
            <p class="text-muted-foreground">von {{ entry.item.club }}</p>
            <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-muted-foreground">Gewünschter Zeitraum</dt>
                    <dd class="font-medium">{{ date(entry.start_date) }} – {{ date(entry.end_date) }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Menge</dt>
                    <dd class="font-medium">{{ entry.quantity }}</dd>
                </div>
            </dl>
            <p v-if="entry.status === 'unverified'" class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                Bitte bestätige den Eintrag über den Link in der E-Mail, die wir dir geschickt haben.
            </p>
            <p v-else-if="entry.status === 'notified'" class="mt-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-900">
                Der Gegenstand war frei – wir haben dir eine E-Mail geschickt. Wer zuerst anfragt, bekommt ihn.
            </p>
            <div class="mt-6 flex gap-2">
                <Button as-child><Link :href="route('catalog.show', entry.item.id)">Zum Gegenstand</Link></Button>
                <Button v-if="entry.open" variant="destructive" @click="cancel">Abmelden</Button>
            </div>
        </div>
    </PublicLayout>
</template>
