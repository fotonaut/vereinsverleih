<script setup lang="ts">
import { X } from 'lucide-vue-next';

defineProps<{ photos: { id: number; url: string }[]; deletable?: boolean }>();
const emit = defineEmits<{ remove: [id: number] }>();
</script>

<template>
    <ul v-if="photos.length" class="mt-2 flex flex-wrap gap-2">
        <li v-for="(p, i) in photos" :key="p.id" class="relative">
            <a :href="p.url" target="_blank" rel="noopener" :aria-label="`Foto ${i + 1} öffnen`">
                <img
                    :src="p.url"
                    :alt="`Rückgabe-Foto ${i + 1}`"
                    loading="lazy"
                    class="size-20 rounded-md border object-cover transition hover:opacity-80"
                />
            </a>
            <button
                v-if="deletable"
                type="button"
                class="absolute -right-1.5 -top-1.5 grid size-5 place-items-center rounded-full bg-red-600 text-white shadow hover:bg-red-700"
                :aria-label="`Foto ${i + 1} löschen`"
                @click="emit('remove', p.id)"
            >
                <X class="size-3" />
            </button>
        </li>
    </ul>
</template>
