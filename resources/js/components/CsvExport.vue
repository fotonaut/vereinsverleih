<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { selectClass } from '@/lib/format';
import { computed, reactive } from 'vue';

const props = defineProps<{ direction: 'incoming' | 'outgoing' }>();

const f = reactive({ status: '', from: '', to: '' });
const statuses = [
    { value: 'pending', label: 'Angefragt' },
    { value: 'approved', label: 'Genehmigt' },
    { value: 'picked_up', label: 'Ausgeliehen' },
    { value: 'returned', label: 'Zurückgegeben' },
    { value: 'declined', label: 'Abgelehnt' },
    { value: 'cancelled', label: 'Storniert' },
];

// Normaler Link (kein Inertia-Visit), damit der Browser die Datei lädt
const href = computed(() => {
    const params = new URLSearchParams({ direction: props.direction });
    (Object.keys(f) as (keyof typeof f)[]).forEach((k) => f[k] && params.set(k, f[k]));
    return `${route('manage.export.loans')}?${params.toString()}`;
});
</script>

<template>
    <details class="rounded-lg border p-3 text-sm">
        <summary class="cursor-pointer font-medium">CSV-Export</summary>
        <div class="mt-3 flex flex-wrap items-end gap-3">
            <label class="grid gap-1"
                >Status
                <select v-model="f.status" :class="[selectClass, 'w-44']">
                    <option value="">Alle</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </label>
            <label class="grid gap-1"
                >Von
                <input type="date" v-model="f.from" :class="[selectClass, 'w-40']" />
            </label>
            <label class="grid gap-1"
                >Bis
                <input type="date" v-model="f.to" :min="f.from" :class="[selectClass, 'w-40']" />
            </label>
            <Button as-child size="sm"><a :href="href" download>Herunterladen</a></Button>
        </div>
        <p class="mt-2 text-xs text-muted-foreground">
            Semikolon-getrennt, UTF-8 – öffnet direkt in Excel/LibreOffice. Enthält personenbezogene Daten: vertraulich behandeln.
        </p>
    </details>
</template>
