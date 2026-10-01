export const euro = (cents: number | null | undefined): string =>
    cents == null ? '–' : (cents / 100).toLocaleString('de-DE', { style: 'currency', currency: 'EUR' });

export const date = (iso: string): string => new Date(iso).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });

export const statusClasses: Record<string, string> = {
    unverified: 'bg-gray-100 text-gray-700',
    pending: 'bg-amber-100 text-amber-800',
    approved: 'bg-emerald-100 text-emerald-800',
    declined: 'bg-red-100 text-red-800',
    picked_up: 'bg-blue-100 text-blue-800',
    returned: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-gray-100 text-gray-500',
};

export const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
