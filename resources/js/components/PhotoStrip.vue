<script setup lang="ts">
defineProps<{ photos: { id: number; url: string }[] }>();
</script>

<template>
    <ul v-if="photos.length" class="mt-2 flex flex-wrap gap-2">
        <li v-for="(p, i) in photos" :key="p.id">
            <a :href="p.url" target="_blank" rel="noopener" :aria-label="`Foto ${i + 1} öffnen`">
                <img
                    :src="p.url"
                    :alt="`Rückgabe-Foto ${i + 1}`"
                    loading="lazy"
                    class="size-20 rounded-md border object-cover transition hover:opacity-80"
                />
            </a>
        </li>
    </ul>
</template>
