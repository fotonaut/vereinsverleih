<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { date, euro, selectClass } from '@/lib/format';
import type { Item } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Package } from 'lucide-vue-next';

const props = defineProps<{
    item: Item;
    scopeLabel: string;
    reservations: { start_date: string; end_date: string; quantity: number }[];
    canRequestAsClub: boolean;
    canRequestAsPrivate: boolean;
    ownItem: boolean;
    clubOnlyHint: boolean;
    privateOnlyHint: boolean;
}>();

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    requester_name: '',
    requester_email: '',
    requester_phone: '',
    quantity: 1,
    start_date: '',
    end_date: '',
    message: '',
    website: '', // Honeypot
});

const submit = () => form.post(route('requests.store', props.item.id));
</script>

<template>
    <Head :title="item.name" />
    <PublicLayout>
        <Link :href="route('catalog.index')" class="text-sm text-muted-foreground hover:underline">← Zurück zum Katalog</Link>

        <div class="mt-4 grid gap-8 lg:grid-cols-2">
            <div>
                <div class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-xl bg-muted">
                    <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-full w-full object-cover" />
                    <Package v-else class="size-16 text-muted-foreground" />
                </div>
                <h1 class="mt-6 text-3xl font-bold">{{ item.name }}</h1>
                <p class="text-muted-foreground">
                    {{ item.club?.name }} <span v-if="item.club?.city">· {{ item.club.city }}</span>
                </p>
                <p v-if="item.description" class="mt-4 whitespace-pre-line">{{ item.description }}</p>
                <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-muted-foreground">Verleih</dt>
                        <dd class="font-medium">{{ scopeLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Bestand</dt>
                        <dd class="font-medium">{{ item.quantity }} Stück</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Kaution</dt>
                        <dd class="font-medium">{{ euro(item.deposit_cents) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Zustand</dt>
                        <dd class="font-medium">{{ item.condition || '–' }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-muted-foreground">Abholort</dt>
                        <dd class="font-medium">{{ item.location || 'nach Absprache' }}</dd>
                    </div>
                </dl>

                <div v-if="reservations.length" class="mt-6">
                    <h2 class="mb-2 font-semibold">Bereits belegt</h2>
                    <ul class="space-y-1 text-sm text-muted-foreground">
                        <li v-for="(r, i) in reservations" :key="i">{{ date(r.start_date) }} – {{ date(r.end_date) }} ({{ r.quantity }}×)</li>
                    </ul>
                </div>
            </div>

            <div>
                <div class="rounded-xl border p-6">
                    <h2 class="mb-4 text-lg font-semibold">Ausleihe anfragen</h2>

                    <p v-if="ownItem" class="text-sm text-muted-foreground">Das ist euer eigener Gegenstand.</p>
                    <p v-else-if="clubOnlyHint" class="text-sm text-muted-foreground">
                        Dieser Gegenstand wird nur an Vereine verliehen.
                        <Link :href="route('login')" class="underline">Als Verein anmelden</Link> oder
                        <Link :href="route('register')" class="underline">Verein registrieren</Link>.
                    </p>
                    <p v-else-if="privateOnlyHint" class="text-sm text-muted-foreground">Dieser Gegenstand wird nur an Privatpersonen verliehen.</p>

                    <form v-else-if="canRequestAsClub || canRequestAsPrivate" class="grid gap-4" @submit.prevent="submit">
                        <template v-if="canRequestAsPrivate">
                            <div class="grid gap-2">
                                <Label for="requester_name">Dein Name</Label>
                                <Input id="requester_name" v-model="form.requester_name" required />
                                <InputError :message="form.errors.requester_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="requester_email">E-Mail</Label>
                                <Input id="requester_email" type="email" v-model="form.requester_email" required />
                                <InputError :message="form.errors.requester_email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="requester_phone">Telefon (optional)</Label>
                                <Input id="requester_phone" v-model="form.requester_phone" />
                            </div>
                        </template>
                        <p v-else class="text-sm text-muted-foreground">Die Anfrage geht im Namen deines Vereins raus.</p>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="grid gap-2">
                                <Label for="quantity">Menge</Label>
                                <Input id="quantity" type="number" min="1" :max="item.quantity" v-model.number="form.quantity" required />
                            </div>
                            <div class="grid gap-2">
                                <Label for="start_date">Von</Label>
                                <Input id="start_date" type="date" :min="today" v-model="form.start_date" required />
                            </div>
                            <div class="grid gap-2">
                                <Label for="end_date">Bis</Label>
                                <Input id="end_date" type="date" :min="form.start_date || today" v-model="form.end_date" required />
                            </div>
                        </div>
                        <InputError :message="form.errors.quantity || form.errors.start_date || form.errors.end_date" />

                        <div class="grid gap-2">
                            <Label for="message">Nachricht (optional)</Label>
                            <textarea id="message" v-model="form.message" rows="3" :class="[selectClass, 'h-auto']" />
                        </div>

                        <!-- Honeypot gegen Spam-Bots -->
                        <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

                        <Button type="submit" :disabled="form.processing">Anfrage senden</Button>
                        <p v-if="canRequestAsPrivate" class="text-xs text-muted-foreground">
                            Du bekommst eine E-Mail, mit der du die Anfrage bestätigst. Erst danach sieht der Verein sie.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
