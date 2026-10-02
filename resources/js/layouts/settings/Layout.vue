<script setup lang="ts">
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { KeyRound, Palette, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const path = computed(() => page.url.split('?')[0]);

const tabs = [
    { title: 'Profil', href: '/settings/profile', icon: UserRound },
    { title: 'Passwort', href: '/settings/password', icon: KeyRound },
    { title: 'Darstellung', href: '/settings/appearance', icon: Palette },
];

const initials = computed(() =>
    user.value.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0].toUpperCase())
        .join(''),
);
</script>

<template>
    <div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6">
        <!-- Kopf im Stil der Anmeldeseiten: Abendszene, Logo-Farben, Konto auf einen Blick -->
        <div class="relative overflow-hidden rounded-2xl border bg-[#123f35]">
            <img src="/images/auth-welcome.svg" alt="" class="absolute inset-0 size-full object-cover object-[50%_62%]" />
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/25 to-transparent" />
            <div class="relative flex items-center gap-4 p-5 text-white sm:p-6">
                <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-white/90 text-lg font-bold text-teal-800 shadow">{{
                    initials
                }}</span>
                <div class="min-w-0">
                    <h1 class="truncate text-xl font-semibold tracking-tight">{{ user.name }}</h1>
                    <p class="truncate text-sm text-white/80">{{ user.email }}</p>
                    <p v-if="user.club" class="mt-1 flex flex-wrap items-center gap-2 text-xs text-white/90">
                        <span class="truncate">{{ user.club.name }}</span>
                        <span class="rounded-full bg-white/20 px-2 py-0.5 font-medium backdrop-blur-sm">{{
                            user.role === 'club_admin' ? 'Admin' : 'Mitglied'
                        }}</span>
                    </p>
                </div>
            </div>
        </div>

        <nav class="flex gap-1 rounded-xl bg-muted p-1" aria-label="Einstellungen">
            <Link
                v-for="t in tabs"
                :key="t.href"
                :href="t.href"
                class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition"
                :class="path === t.href ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                :aria-current="path === t.href ? 'page' : undefined"
            >
                <component :is="t.icon" class="size-4" />
                <span>{{ t.title }}</span>
            </Link>
        </nav>

        <div class="space-y-6">
            <slot />
        </div>
    </div>
</template>
