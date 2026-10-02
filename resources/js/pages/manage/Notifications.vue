<script setup lang="ts">
import FlashMessage from '@/components/FlashMessage.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    settings: { requests: boolean; extensions: boolean; overdue: boolean; recipients: 'both' | 'club_email' | 'admins'; extra_email: string | null };
    clubEmail: string;
    adminEmails: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Benachrichtigungen', href: '/verwaltung/benachrichtigungen' }];

const form = useForm({
    requests: props.settings.requests,
    extensions: props.settings.extensions,
    overdue: props.settings.overdue,
    recipients: props.settings.recipients,
    extra_email: props.settings.extra_email ?? '',
});

const topics = [
    { key: 'requests', title: 'Neue Ausleihanfragen', text: 'Wenn jemand einen Gegenstand (auch als Serie) anfragt.' },
    { key: 'extensions', title: 'Verlängerungsanfragen', text: 'Wenn Ausleihende eine Verlängerung wünschen.' },
    { key: 'overdue', title: 'Überfällige Rückgaben', text: 'Info an euch, wenn wir Ausleihende an die Rückgabe mahnen.' },
] as const;

const recipientOptions = [
    { value: 'both', label: 'Vereinsadresse und Admin-Konten' },
    { value: 'club_email', label: 'Nur die Vereinsadresse' },
    { value: 'admins', label: 'Nur die Admin-Konten' },
] as const;

// Vorschau: an wen würde tatsächlich gesendet?
const preview = computed(() => {
    const list =
        form.recipients === 'club_email'
            ? [props.clubEmail]
            : form.recipients === 'admins'
              ? props.adminEmails.length
                  ? props.adminEmails
                  : [props.clubEmail]
              : [props.clubEmail, ...props.adminEmails];
    if (form.extra_email) list.push(form.extra_email);
    return [...new Set(list.map((m) => m.toLowerCase()))];
});

const sendTest = () => router.post(route('manage.notifications.test'), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Benachrichtigungen" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="mx-auto grid max-w-2xl gap-6 p-4" @submit.prevent="form.put(route('manage.notifications.update'), { preserveScroll: true })">
            <div>
                <h1 class="text-2xl font-bold">Benachrichtigungen</h1>
                <p class="text-sm text-muted-foreground">Legt fest, welche E-Mails euer Verein bekommt und wohin sie gehen.</p>
            </div>
            <FlashMessage />

            <fieldset class="grid gap-3 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Worüber informieren wir euch?</legend>
                <label v-for="t in topics" :key="t.key" class="flex items-start gap-3 text-sm">
                    <input v-model="form[t.key]" type="checkbox" class="mt-1" />
                    <span
                        ><strong>{{ t.title }}</strong
                        ><br /><span class="text-muted-foreground">{{ t.text }}</span></span
                    >
                </label>
                <p v-if="!form.requests" class="rounded-md bg-amber-50 p-2 text-xs text-amber-900">
                    Ohne diese Mails seht ihr neue Anfragen nur, wenn ihr in den Eingang schaut.
                </p>
            </fieldset>

            <fieldset class="grid gap-2 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">An wen gehen die Mails?</legend>
                <label v-for="o in recipientOptions" :key="o.value" class="flex items-center gap-2 text-sm">
                    <input v-model="form.recipients" type="radio" :value="o.value" /> {{ o.label }}
                </label>
                <InputError :message="form.errors.recipients" />

                <div class="mt-2 grid gap-2">
                    <Label for="extra_email">Zusätzliche Adresse (optional, z. B. Materialwart)</Label>
                    <Input id="extra_email" v-model="form.extra_email" type="email" placeholder="materialwart@example.org" />
                    <InputError :message="form.errors.extra_email" />
                </div>

                <p class="mt-1 text-xs text-muted-foreground">
                    Aktuell gehen Mails an: <strong>{{ preview.join(', ') }}</strong>
                </p>
            </fieldset>

            <div class="flex flex-wrap gap-2">
                <Button type="submit" :disabled="form.processing">Speichern</Button>
                <Button type="button" variant="outline" :disabled="form.isDirty" :title="form.isDirty ? 'Erst speichern' : ''" @click="sendTest">
                    Test-Mail senden
                </Button>
            </div>
            <p v-if="form.isDirty" class="-mt-3 text-xs text-muted-foreground">
                Die Test-Mail geht an die gespeicherten Einstellungen – bitte erst speichern.
            </p>
        </form>
    </AppLayout>
</template>
