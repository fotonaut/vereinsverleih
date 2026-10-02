<script setup lang="ts">
defineProps<{ title: string; description?: string; tone?: 'default' | 'danger' }>();
</script>

<template>
    <section
        class="rounded-2xl border p-6 shadow-sm"
        :class="tone === 'danger' ? 'border-red-200 bg-red-50/60 dark:border-red-400/20 dark:bg-red-500/5' : 'bg-card'"
    >
        <header class="mb-5">
            <h2 class="text-lg font-semibold tracking-tight" :class="tone === 'danger' && 'text-red-700 dark:text-red-300'">{{ title }}</h2>
            <p v-if="description" class="mt-0.5 text-sm text-muted-foreground">{{ description }}</p>
        </header>
        <slot />
    </section>
</template>
