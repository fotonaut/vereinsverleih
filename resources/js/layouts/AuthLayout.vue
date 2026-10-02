<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSplitLayout.vue';

defineProps<{
    title?: string;
    description?: string;
    scene?: 'welcome' | 'register';
}>();
</script>

<template>
    <AuthLayout :title="title" :description="description" :scene="scene">
        <slot />
    </AuthLayout>
</template>
