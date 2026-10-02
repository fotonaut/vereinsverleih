import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    auth: Auth;
    flash?: string | null;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    club_id: number | null;
    role: 'club_admin' | 'member';
    club?: { id: number; name: string } | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Club {
    id: number;
    name: string;
    city?: string | null;
    email?: string;
}

export interface Item {
    id: number;
    club_id: number;
    category_id: number | null;
    name: string;
    description: string | null;
    quantity: number;
    condition: string | null;
    location: string | null;
    deposit_cents: number | null;
    image_url: string | null;
    lending_scope: 'clubs' | 'private' | 'both' | 'none';
    scope_label?: string;
    active: boolean;
    club?: Club;
    category?: { id: number; name: string } | null;
}

export interface Loan {
    id: number;
    status: string;
    status_label: string;
    requester_name: string;
    requester_email: string;
    requester_phone: string | null;
    requester_type: 'club' | 'private';
    quantity: number;
    start_date: string;
    end_date: string;
    message: string | null;
    decision_note: string | null;
    token: string;
    item: { id: number; name: string; club?: Club };
    next?: string[];
    overdue?: boolean;
    series_id?: string | null;
    series_total?: number | null;
    pending_extension?: { id: number; requested_end_date: string; previous_end_date: string; message: string | null } | null;
}

export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}

export interface Operator {
    brand: string;
    name: string;
    street: string;
    city: string;
    country: string;
    email: string;
    phone: string;
    hoster: string;
}
