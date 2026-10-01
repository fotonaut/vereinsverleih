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
