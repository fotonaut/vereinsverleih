<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    club_name: '',
    club_city: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Verein registrieren" description="Lege den Zugang für euren Verein an und trage euer Inventar ein">
        <Head title="Verein registrieren" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="club_name">Name des Vereins</Label>
                    <Input
                        id="club_name"
                        type="text"
                        required
                        autofocus
                        tabindex="1"
                        v-model="form.club_name"
                        placeholder="z. B. TSV Musterdorf e.V."
                    />
                    <InputError :message="form.errors.club_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="club_city">Ort</Label>
                    <Input id="club_city" type="text" tabindex="1" v-model="form.club_city" placeholder="Musterdorf" />
                    <InputError :message="form.errors.club_city" />
                </div>

                <div class="grid gap-2">
                    <Label for="name">Dein Name</Label>
                    <Input id="name" type="text" required tabindex="1" autocomplete="name" v-model="form.name" placeholder="Vollständiger Name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">E-Mail-Adresse</Label>
                    <Input id="email" type="email" required tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Passwort</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Passwort"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Passwort bestätigen</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Passwort bestätigen"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Verein registrieren
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Schon registriert?
                <TextLink :href="route('login')" class="underline underline-offset-4" tabindex="6">Anmelden</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
