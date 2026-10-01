<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { Operator } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ operator: Operator; retentionDays: number }>();
</script>

<template>
    <Head title="Datenschutzerklärung" />
    <PublicLayout>
        <article class="mx-auto max-w-2xl space-y-6 text-sm leading-relaxed">
            <h1 class="text-2xl font-bold">Datenschutzerklärung</h1>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">1. Verantwortlicher</h2>
                <p>
                    {{ operator.name || operator.brand }}<template v-if="operator.street">, {{ operator.street }}</template
                    ><template v-if="operator.city">, {{ operator.city }}</template
                    ><template v-if="operator.email">, E-Mail: {{ operator.email }}</template
                    >. Details im <Link :href="route('imprint')" class="underline">Impressum</Link>.
                </p>
                <p>
                    Der Betreiber stellt die technische Plattform bereit. Für die Verarbeitung von Anfragedaten nach der Übermittlung ist zusätzlich
                    der jeweils verleihende Verein verantwortlich (siehe Abschnitt 4).
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">2. Besuch der Website (Server-Logs)</h2>
                <p>
                    Beim Aufruf verarbeitet der Webserver technisch notwendig IP-Adresse, Datum/Uhrzeit, aufgerufene Seite und Browserkennung. Das
                    dient dem sicheren Betrieb (Art. 6 Abs. 1 lit. f DSGVO). Hoster: {{ operator.hoster }}. Es werden keine Tracking- oder
                    Analyse-Dienste eingesetzt und keine externen Schriften oder Skripte nachgeladen.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">3. Cookies</h2>
                <p>
                    Es werden ausschließlich technisch notwendige Cookies gesetzt (Sitzung und Schutz vor Formularfälschung/CSRF). Eine Einwilligung
                    ist dafür nicht erforderlich (§ 25 Abs. 2 Nr. 2 TDDDG).
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">4. Ausleihanfragen</h2>
                <p>
                    <strong>Privatpersonen (ohne Konto):</strong> Für eine Anfrage werden Name, E-Mail-Adresse, optional Telefonnummer und Nachricht
                    sowie Gegenstand, Zeitraum und Menge gespeichert. Du erhältst eine E-Mail mit Bestätigungslink; erst danach wird die Anfrage an
                    den verleihenden Verein übermittelt. Nicht bestätigte Anfragen werden nach {{ retentionDays }} Tagen automatisch gelöscht.
                </p>
                <p><strong>Vereine:</strong> Anfragen eingeloggter Vereine enthalten Vereinsname, Name und E-Mail-Adresse des Mitglieds.</p>
                <p>
                    Der verleihende Verein erhält diese Daten zur Bearbeitung der Anfrage und zur Kontaktaufnahme (Art. 6 Abs. 1 lit. b DSGVO,
                    vorvertragliche Maßnahmen). Die Statusseite deiner Anfrage ist nur über einen geheimen Link erreichbar; teile diesen nicht weiter.
                    Abgeschlossene Anfragen werden gespeichert, bis sie auf Wunsch gelöscht werden bzw. der verleihende Verein sie löscht.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">5. Vereinskonto</h2>
                <p>
                    Bei der Registrierung werden Vereinsangaben, Name, E-Mail-Adresse und ein verschlüsselt (Hash) gespeichertes Passwort verarbeitet
                    (Art. 6 Abs. 1 lit. b DSGVO). Ein Konto kann jederzeit in den Einstellungen gelöscht werden. Vereinsdaten und Inventar sind im
                    öffentlichen Katalog sichtbar, soweit ein Gegenstand als verleihbar markiert ist; Kontakt-E-Mail des Vereins wird erst bei
                    genehmigten Anfragen angezeigt.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">6. E-Mail-Versand</h2>
                <p>
                    Systemmails (Bestätigung, Benachrichtigung über Anfragen und Entscheidungen, Erinnerung an die Rückgabe am Tag vor Ende des Zeitraums, Passwort-Reset, Einladungen) werden über den
                    SMTP-Server des Hosters versendet. Eine Weitergabe an sonstige Dritte findet nicht statt.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold">7. Deine Rechte</h2>
                <p>
                    Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch (Art.
                    15–21 DSGVO). Schreibe dazu an
                    <a v-if="operator.email" :href="`mailto:${operator.email}`" class="underline">{{ operator.email }}</a
                    ><template v-else>den Betreiber</template>. Außerdem kannst du dich bei einer Datenschutz-Aufsichtsbehörde beschweren.
                </p>
            </section>
        </article>
    </PublicLayout>
</template>
