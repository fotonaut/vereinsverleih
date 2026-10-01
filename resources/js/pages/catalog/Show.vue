<script setup lang="ts">
import AvailabilityCalendar from '@/components/AvailabilityCalendar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { minFreeQuantity } from '@/lib/availability';
import { date, euro, selectClass } from '@/lib/format';
import type { Item } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Package } from 'lucide-vue-next';
import { computed } from 'vue';

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

// Klick im Kalender: erster Klick = Von, zweiter (späterer) Klick = Bis
const pick = (day: string) => {
    if (!form.start_date || form.end_date || day < form.start_date) {
        form.start_date = day;
        form.end_date = '';
    } else {
        form.end_date = day;
    }
};

// Gewählter Zeitraum für die gewünschte Menge schon belegt? Dann Warteliste anbieten.
const rangeFull = computed(
    () =>
        !!form.start_date &&
        !!form.end_date &&
        form.end_date >= form.start_date &&
        Number(form.quantity) <= props.item.quantity &&
        minFreeQuantity(props.reservations, props.item.quantity, form.start_date, form.end_date) < Number(form.quantity),
);

const joinWaitlist = () => form.post(route('waitlist.store', props.item.id));

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

                <div class="mt-6">
                    <h2 class="mb-2 font-semibold">Verfügbarkeit</h2>
                    <AvailabilityCalendar
                        :quantity="item.quantity"
                        :reservations="reservations"
                        :selectable="canRequestAsClub || canRequestAsPrivate"
                        :from="form.start_date"
                        :to="form.end_date"
                        @pick="pick"
                    />
                </div>

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

                        <div
                            v-if="rangeFull"
                            class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200"
                        >
                            <p>
                                Im gewählten Zeitraum ist der Gegenstand schon vergeben. Wir benachrichtigen dich per E-Mail, sobald etwas frei wird
                                (z. B. bei Absage).
                            </p>
                            <Button type="button" variant="outline" class="mt-2" :disabled="form.processing" @click="joinWaitlist"
                                >Auf die Warteliste setzen</Button
                            >
                        </div>
                        <Button v-else type="submit" :disabled="form.processing">Anfrage senden</Button>
                        <p v-if="canRequestAsPrivate" class="text-xs text-muted-foreground">
                            Du bekommst eine E-Mail, mit der du die Anfrage bestätigst. Erst danach sieht der Verein sie. Deine Angaben gehen an den
                            verleihenden Verein, mehr dazu in der <Link :href="route('privacy')" class="underline">Datenschutzerklärung</Link>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
