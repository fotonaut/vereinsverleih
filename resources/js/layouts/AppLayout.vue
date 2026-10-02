<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Jede Seite im Verwaltungsbereich bekommt „Übersicht“ als Elternpunkt (außer der Übersicht selbst)
const trail = computed<BreadcrumbItemType[]>(() =>
    props.breadcrumbs.length && props.breadcrumbs[0].href !== '/dashboard'
        ? [{ title: 'Übersicht', href: '/dashboard' }, ...props.breadcrumbs]
        : props.breadcrumbs,
);
</script>

<template>
    <AppLayout :breadcrumbs="trail">
        <slot />
    </AppLayout>
</template>
