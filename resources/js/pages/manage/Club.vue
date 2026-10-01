<script setup lang="ts">
import FlashMessage from '@/components/FlashMessage.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { selectClass } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ club: Record<string, string | null> }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Vereinsdaten', href: '/verwaltung/verein' }];
const form = useForm({
    name: props.club.name ?? '',
    description: props.club.description ?? '',
    email: props.club.email ?? '',
    phone: props.club.phone ?? '',
    street: props.club.street ?? '',
    zip: props.club.zip ?? '',
    city: props.club.city ?? '',
    website: props.club.website ?? '',
});
</script>

<template>
    <Head title="Vereinsdaten" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="mx-auto grid max-w-2xl gap-4 p-4" @submit.prevent="form.put(route('manage.club.update'))">
            <h1 class="text-2xl font-bold">Vereinsdaten</h1>
            <FlashMessage />
            <p class="text-sm text-muted-foreground">
                Die E-Mail-Adresse erhält alle Ausleihanfragen und wird bei genehmigten Anfragen als Kontakt angezeigt.
            </p>
            <div class="grid gap-2">
                <Label for="name">Name</Label><Input id="name" v-model="form.name" required /><InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="email">Kontakt-E-Mail</Label><Input id="email" type="email" v-model="form.email" required /><InputError
                    :message="form.errors.email"
                />
            </div>
            <div class="grid gap-2">
                <Label for="description">Über den Verein</Label
                ><textarea id="description" v-model="form.description" rows="3" :class="[selectClass, 'h-auto']" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2"><Label for="phone">Telefon</Label><Input id="phone" v-model="form.phone" /></div>
                <div class="grid gap-2">
                    <Label for="website">Webseite</Label><Input id="website" v-model="form.website" placeholder="https://" /><InputError
                        :message="form.errors.website"
                    />
                </div>
                <div class="grid gap-2 sm:col-span-2"><Label for="street">Straße</Label><Input id="street" v-model="form.street" /></div>
                <div class="grid gap-2"><Label for="zip">PLZ</Label><Input id="zip" v-model="form.zip" /></div>
                <div class="grid gap-2"><Label for="city">Ort</Label><Input id="city" v-model="form.city" /></div>
            </div>
            <Button type="submit" class="w-fit" :disabled="form.processing">Speichern</Button>
        </form>
    </AppLayout>
</template>
