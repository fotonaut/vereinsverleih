export interface Reservation {
    start_date: string;
    end_date: string;
    quantity: number;
}

const nextDay = (iso: string): string => {
    const d = new Date(`${iso}T00:00:00Z`);
    d.setUTCDate(d.getUTCDate() + 1);
    return d.toISOString().slice(0, 10);
};

/** Wie viele Stück sind im Zeitraum (inklusive) mindestens an jedem Tag frei? */
export function minFreeQuantity(reservations: Reservation[], stock: number, start: string, end: string): number {
    let min = stock;
    for (let day = start, guard = 0; day <= end && guard < 400; day = nextDay(day), guard++) {
        const reserved = reservations.filter((r) => r.start_date <= day && day <= r.end_date).reduce((sum, r) => sum + r.quantity, 0);
        min = Math.min(min, stock - reserved);
    }
    return Math.max(0, min);
}

const toIso = (d: Date): string => d.toISOString().slice(0, 10);

/** Termine einer Wiederholung – spiegelt App\Support\LoanSeries (inkl. Monatsende ohne Überlauf). */
export function occurrences(start: string, end: string, repeat: string, count: number): { start: string; end: string }[] {
    const first = new Date(`${start}T00:00:00Z`);
    const durationMs = new Date(`${end}T00:00:00Z`).getTime() - first.getTime();
    const out: { start: string; end: string }[] = [];

    for (let i = 0; i < count; i++) {
        const s = new Date(first);
        if (repeat === 'monthly') {
            const day = first.getUTCDate();
            s.setUTCDate(1);
            s.setUTCMonth(first.getUTCMonth() + i);
            const lastDay = new Date(Date.UTC(s.getUTCFullYear(), s.getUTCMonth() + 1, 0)).getUTCDate();
            s.setUTCDate(Math.min(day, lastDay));
        } else {
            s.setUTCDate(first.getUTCDate() + i * (repeat === 'biweekly' ? 14 : 7));
        }
        out.push({ start: toIso(s), end: toIso(new Date(s.getTime() + durationMs)) });
    }
    return out;
}
