<script setup lang="ts">
import FlashMessage from '@/components/FlashMessage.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { selectClass } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

defineProps<{ members: { id: number; name: string; email: string; role: string }[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mitglieder', href: '/verwaltung/mitglieder' }];
const form = useForm({ name: '', email: '', role: 'member' });
const errors = usePage().props.errors as Record<string, string>;

const invite = () => form.post(route('manage.members.store'), { onSuccess: () => form.reset() });
const remove = (id: number, name: string) => {
    if (window.confirm(`${name} aus dem Verein entfernen?`)) router.delete(route('manage.members.destroy', id));
};
</script>

<template>
    <Head title="Mitglieder" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6 p-4">
            <h1 class="text-2xl font-bold">Mitglieder</h1>
            <FlashMessage />
            <p v-if="errors.member" class="text-sm text-red-700">{{ errors.member }}</p>

            <ul class="divide-y rounded-xl border">
                <li v-for="m in members" :key="m.id" class="flex items-center justify-between gap-3 p-3 text-sm">
                    <span
                        ><strong>{{ m.name }}</strong> · {{ m.email }}
                        <span class="ml-1 rounded bg-muted px-1.5 text-xs">{{ m.role === 'club_admin' ? 'Admin' : 'Mitglied' }}</span></span
                    >
                    <Button size="sm" variant="destructive" @click="remove(m.id, m.name)">Entfernen</Button>
                </li>
            </ul>

            <form class="grid gap-3 rounded-xl border p-4 sm:grid-cols-2" @submit.prevent="invite">
                <h2 class="font-semibold sm:col-span-2">Mitglied einladen</h2>
                <div class="grid gap-2">
                    <Label for="m_name">Name</Label><Input id="m_name" v-model="form.name" required /><InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="m_email">E-Mail</Label><Input id="m_email" type="email" v-model="form.email" required /><InputError
                        :message="form.errors.email"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="m_role">Rolle</Label>
                    <select id="m_role" v-model="form.role" :class="selectClass">
                        <option value="member">Mitglied</option>
                        <option value="club_admin">Admin</option>
                    </select>
                </div>
                <div class="flex items-end"><Button type="submit" :disabled="form.processing">Einladung senden</Button></div>
                <p class="text-xs text-muted-foreground sm:col-span-2">Die Person erhält eine E-Mail mit einem Link, um ihr Passwort festzulegen.</p>
            </form>
        </div>
    </AppLayout>
</template>
