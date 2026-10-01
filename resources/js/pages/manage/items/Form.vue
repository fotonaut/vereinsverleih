<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { selectClass } from '@/lib/format';
import type { BreadcrumbItem, Item } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    item: (Item & { deposit_euro: number | null }) | null;
    categories: { id: number; name: string }[];
    scopes: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Meine Gegenstände', href: '/verwaltung/gegenstaende' },
    { title: props.item ? 'Bearbeiten' : 'Neu', href: '#' },
];

const form = useForm({
    name: props.item?.name ?? '',
    description: props.item?.description ?? '',
    category_id: props.item?.category_id ?? '',
    quantity: props.item?.quantity ?? 1,
    condition: props.item?.condition ?? '',
    location: props.item?.location ?? '',
    deposit_euro: props.item?.deposit_euro ?? '',
    lending_scope: props.item?.lending_scope ?? 'none',
    active: props.item?.active ?? true,
    image: null as File | null,
    _method: props.item ? 'put' : 'post',
});

// Multipart + Method-Spoofing, damit der Bild-Upload auch bei PUT funktioniert
const submit = () => form.post(props.item ? route('manage.items.update', props.item.id) : route('manage.items.store'));
</script>

<template>
    <Head :title="item ? 'Gegenstand bearbeiten' : 'Gegenstand anlegen'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="mx-auto grid max-w-2xl gap-5 p-4" @submit.prevent="submit">
            <h1 class="text-2xl font-bold">{{ item ? 'Gegenstand bearbeiten' : 'Gegenstand anlegen' }}</h1>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" required />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="description">Beschreibung</Label>
                <textarea id="description" v-model="form.description" rows="4" :class="[selectClass, 'h-auto']" />
                <InputError :message="form.errors.description" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="category">Kategorie</Label>
                    <select id="category" v-model="form.category_id" :class="selectClass">
                        <option value="">–</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="quantity">Bestand (Stück)</Label>
                    <Input id="quantity" type="number" min="1" v-model.number="form.quantity" required />
                    <InputError :message="form.errors.quantity" />
                </div>
                <div class="grid gap-2">
                    <Label for="condition">Zustand</Label>
                    <Input id="condition" v-model="form.condition" placeholder="z. B. gut, neuwertig" />
                </div>
                <div class="grid gap-2">
                    <Label for="deposit">Kaution (€)</Label>
                    <Input id="deposit" type="number" step="0.01" min="0" v-model="form.deposit_euro" />
                    <InputError :message="form.errors.deposit_euro" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="location">Abholort</Label>
                <Input id="location" v-model="form.location" placeholder="z. B. Vereinsheim, Hauptstr. 1" />
            </div>

            <fieldset class="grid gap-2 rounded-lg border p-4">
                <legend class="px-1 text-sm font-medium">Wer darf diesen Gegenstand leihen?</legend>
                <label v-for="s in scopes" :key="s.value" class="flex items-center gap-2 text-sm">
                    <input type="radio" v-model="form.lending_scope" :value="s.value" /> {{ s.label }}
                </label>
                <InputError :message="form.errors.lending_scope" />
            </fieldset>

            <div class="grid gap-2">
                <Label for="image">Foto (JPG/PNG/WebP, max. 4 MB)</Label>
                <img v-if="item?.image_url && !form.image" :src="item.image_url" alt="" class="h-32 w-auto rounded-md object-cover" />
                <input
                    id="image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    @input="form.image = ($event.target as HTMLInputElement).files?.[0] ?? null"
                />
                <InputError :message="form.errors.image" />
            </div>

            <label class="flex items-center gap-2 text-sm"
                ><input type="checkbox" v-model="form.active" /> Aktiv (im Katalog sichtbar, sofern verleihbar)</label
            >

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">Speichern</Button>
                <Button variant="outline" as-child><Link :href="route('manage.items.index')">Abbrechen</Link></Button>
            </div>
        </form>
    </AppLayout>
</template>
