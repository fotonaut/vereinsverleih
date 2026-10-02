<script setup lang="ts">
defineProps<{ title: string }>();
</script>

<template>
    <div class="flex flex-col items-center gap-2 py-10 text-center">
        <img src="/images/empty-box.svg" alt="" class="w-48 max-w-full" loading="lazy" />
        <h2 class="text-lg font-semibold">{{ title }}</h2>
        <p v-if="$slots.default" class="max-w-md text-sm text-muted-foreground"><slot /></p>
        <div v-if="$slots.actions" class="mt-2 flex flex-wrap justify-center gap-2"><slot name="actions" /></div>
    </div>
</template>
