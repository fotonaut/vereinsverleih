<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import SettingsCard from '@/components/SettingsCard.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Darstellung',
        href: '/settings/appearance',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Darstellung" />

        <SettingsLayout>
            <SettingsCard title="Darstellung" description="Hell, dunkel oder passend zu deinem Gerät">
                <AppearanceTabs />
            </SettingsCard>
        </SettingsLayout>
    </AppLayout>
</template>
