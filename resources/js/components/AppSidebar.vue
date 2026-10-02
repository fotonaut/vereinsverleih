<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Building2, Folder, Hourglass, Inbox, LayoutGrid, Package, Search, Send, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const user = usePage<SharedData>().props.auth.user;

const mainNavItems: NavItem[] = [
    { title: 'Übersicht', href: '/dashboard', icon: LayoutGrid },
    { title: 'Meine Gegenstände', href: '/verwaltung/gegenstaende', icon: Package },
    { title: 'Eingehende Anfragen', href: '/verwaltung/eingang', icon: Inbox },
    { title: 'Meine Anfragen', href: '/verwaltung/ausgang', icon: Send },
    { title: 'Meine Warteliste', href: '/verwaltung/warteliste', icon: Hourglass },
    { title: 'Katalog', href: '/katalog', icon: Search },
    ...(user.role === 'club_admin'
        ? [
              { title: 'Vereinsdaten', href: '/verwaltung/verein', icon: Building2 },
              { title: 'Mitglieder', href: '/verwaltung/mitglieder', icon: Users },
          ]
        : []),
];

const footerNavItems: NavItem[] = [
    { title: 'GitHub', href: 'https://github.com/fotonaut/vereinsverleih', icon: Folder },
    { title: 'Hilfe & Doku', href: 'https://github.com/fotonaut/vereinsverleih#readme', icon: BookOpen },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <p class="px-2 text-xs text-muted-foreground group-data-[collapsible=icon]:hidden">
                <Link :href="route('imprint')" class="underline-offset-2 hover:underline">Impressum</Link> ·
                <Link :href="route('privacy')" class="underline-offset-2 hover:underline">Datenschutz</Link>
            </p>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
