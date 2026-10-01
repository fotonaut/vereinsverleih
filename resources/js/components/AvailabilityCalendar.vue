<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';

export interface CalendarReservation {
    start_date: string;
    end_date: string;
    quantity: number;
    /** Offene Anfrage: blockiert den Bestand noch nicht, wird nur markiert. */
    pending?: boolean;
    label?: string;
}

const props = defineProps<{
    quantity: number;
    reservations: CalendarReservation[];
    /** Auswahl per Klick (Datum als YYYY-MM-DD) – nur auf der öffentlichen Seite */
    selectable?: boolean;
    from?: string;
    to?: string;
}>();

const emit = defineEmits<{ pick: [day: string] }>();

const pad = (n: number) => String(n).padStart(2, '0');
const fmt = (y: number, m: number, d: number) => `${y}-${pad(m + 1)}-${pad(d)}`;
const now = new Date();
const today = fmt(now.getFullYear(), now.getMonth(), now.getDate());

const year = ref(now.getFullYear());
const month = ref(now.getMonth());

const monthLabel = computed(() => new Date(year.value, month.value, 1).toLocaleDateString('de-DE', { month: 'long', year: 'numeric' }));

const shift = (delta: number) => {
    const d = new Date(year.value, month.value + delta, 1);
    year.value = d.getFullYear();
    month.value = d.getMonth();
};
const reset = () => {
    year.value = now.getFullYear();
    month.value = now.getMonth();
};

type Day = { date: string; day: number; reserved: number; pending: number; past: boolean; titles: string[] } | null;

const days = computed<Day[]>(() => {
    const first = new Date(year.value, month.value, 1);
    const offset = (first.getDay() + 6) % 7; // Woche beginnt Montag
    const count = new Date(year.value, month.value + 1, 0).getDate();
    const cells: Day[] = Array(offset).fill(null);

    for (let d = 1; d <= count; d++) {
        const date = fmt(year.value, month.value, d);
        const hits = props.reservations.filter((r) => r.start_date <= date && date <= r.end_date);
        cells.push({
            date,
            day: d,
            reserved: hits.filter((r) => !r.pending).reduce((sum, r) => sum + r.quantity, 0),
            pending: hits.filter((r) => r.pending).length,
            past: date < today,
            titles: hits.map((r) => `${r.pending ? '(angefragt) ' : ''}${r.label ?? ''} ${r.quantity}×`.trim()),
        });
    }
    return cells;
});

const state = (d: NonNullable<Day>) => {
    if (d.past) return 'past';
    if (d.reserved >= props.quantity) return 'full';
    if (d.reserved > 0) return 'part';
    return 'free';
};

const classes: Record<string, string> = {
    past: 'bg-muted/40 text-muted-foreground/60',
    full: 'bg-red-100 text-red-900 dark:bg-red-950/50 dark:text-red-200',
    part: 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200',
    free: 'bg-emerald-50 text-emerald-900 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-200',
};

const inRange = (date: string) => !!props.from && date >= props.from && date <= (props.to || props.from);

const click = (d: NonNullable<Day>) => {
    if (props.selectable && !d.past && state(d) !== 'full') emit('pick', d.date);
};
</script>

<template>
    <div class="rounded-xl border p-4">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold capitalize">{{ monthLabel }}</h3>
            <div class="flex items-center gap-1">
                <Button type="button" size="icon" variant="ghost" aria-label="Vorheriger Monat" @click="shift(-1)"
                    ><ChevronLeft class="size-4"
                /></Button>
                <Button type="button" size="sm" variant="ghost" @click="reset">Heute</Button>
                <Button type="button" size="icon" variant="ghost" aria-label="Nächster Monat" @click="shift(1)"
                    ><ChevronRight class="size-4"
                /></Button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-xs text-muted-foreground">
            <div v-for="w in ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']" :key="w">{{ w }}</div>
        </div>
        <div class="mt-1 grid grid-cols-7 gap-1">
            <template v-for="(d, i) in days" :key="i">
                <div v-if="!d" />
                <button
                    v-else
                    type="button"
                    :title="d.titles.join(' · ') || undefined"
                    :disabled="!selectable || d.past || state(d) === 'full'"
                    class="relative aspect-square rounded-md text-sm transition disabled:cursor-default"
                    :class="[classes[state(d)], inRange(d.date) && 'ring-2 ring-primary', d.date === today && 'font-bold underline']"
                    @click="click(d)"
                >
                    {{ d.day }}
                    <span v-if="d.pending && !d.past" class="absolute right-1 top-1 size-1.5 rounded-full bg-blue-500" aria-label="Anfrage offen" />
                </button>
            </template>
        </div>

        <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded bg-emerald-200" /> frei</li>
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded bg-amber-200" /> teilweise belegt</li>
            <li class="flex items-center gap-1"><span class="inline-block size-3 rounded bg-red-200" /> ausgebucht</li>
            <li v-if="reservations.some((r) => r.pending)" class="flex items-center gap-1">
                <span class="inline-block size-2 rounded-full bg-blue-500" /> Anfrage offen
            </li>
        </ul>
    </div>
</template>
