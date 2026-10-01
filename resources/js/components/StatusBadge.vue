<script setup lang="ts">
import { statusClasses } from '@/lib/format';

defineProps<{ status: string; label: string }>();
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClasses[status] ?? 'bg-gray-100'">{{
        label
    }}</span>
</template>
