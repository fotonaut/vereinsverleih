# Vereinsverleih

Verleihplattform für Vereinsinventar: Vereine tragen ihren Bestand ein (Zelte, Bänke, Technik, Spiele …), legen pro Gegenstand fest, **wer ihn leihen darf**, und andere Vereine oder Privatpersonen stellen Ausleihanfragen.

**Onepager:** <https://fotonaut.github.io/vereinsverleih/>

Pro Gegenstand einstellbar:

| Verleih an | Bedeutung |
| --- | --- |
| Nur Vereine | Anfragen nur von angemeldeten Vereinen |
| Nur Privatpersonen | Anfragen ohne Konto per Formular + E-Mail-Bestätigung |
| Vereine und Privatpersonen | beides |
| Niemand | nur intern, taucht nicht im Katalog auf |

## Funktionen (MVP)

- Vereins-Registrierung, Rollen *Admin* / *Mitglied*, Mitglieder per E-Mail einladen
- Inventar mit Foto, Kategorie, Bestand, Zustand, Abholort, Kaution
- Öffentlicher Katalog mit Suche und Filtern (Kategorie, Verein, Verleih an)
- Anfrage-Workflow: *angefragt → genehmigt → ausgeliehen → zurückgegeben* (oder abgelehnt/storniert)
- Bestandsprüfung je Zeitraum (keine Überbuchung), **Verfügbarkeitskalender** pro Gegenstand (öffentlich mit Klick-Auswahl, im Verein mit Namen und offenen Anfragen)
- **Rückgabe-Erinnerung** am Tag vor Ende, **Mahnung** bei Überfälligkeit (max. 3×, auch an den Verein), **Verlängerungsanfragen** mit Genehmigung
- **CSV-Export** der Ausleihen (Excel-tauglich, Filter Status/Zeitraum)
- Privatpersonen brauchen kein Konto: Anfrage wird per E-Mail-Link bestätigt (Spam-Schutz), danach Status-Seite per Token-Link
- E-Mail-Benachrichtigungen (über Queue), Honeypot und Rate-Limit am Anfrageformular
- Komplett deutsch, responsive, Dark Mode

## Technik

Laravel 13 · PHP ≥ 8.3 · MySQL 8 / MariaDB · Inertia.js · Vue 3 · TypeScript · Tailwind CSS · Policies/Form Requests/Notifications/Queues · PHPUnit

## Lokal starten (Docker)

```bash
cp .env.example .env            # ggf. APP_PORT=8123 und UID/GID anhängen
docker compose up -d mysql mailpit
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed
docker compose run --rm vite sh -c "npm install && npm run build"
docker compose up -d app queue
```

Danach: <http://localhost:8000> (bzw. `APP_PORT`), Mails landen in Mailpit auf <http://localhost:8025>.
Demo-Logins (nur lokal, durch den Seeder): `admin@schuetzen.test` / `admin@feuerwehr.test`, Passwort `password`.

Frontend mit Hot Reload: `docker compose up vite`.

## Tests

```bash
docker compose run --rm app php artisan test
```

## Eigene Instanz betreiben

Läuft auf klassischem PHP-Webhosting (kein Node, kein Dauer-Worker nötig): siehe [docs/DEPLOY-MANITU.md](docs/DEPLOY-MANITU.md). Die Anleitung gilt sinngemäß für jeden Apache-Hoster mit PHP 8.3+.

## Konfiguration

| Variable | Zweck |
| --- | --- |
| `DB_*`, `DB_PREFIX` | Datenbank; Prefix `vv_` erlaubt geteilte Datenbanken |
| `MAIL_*` | SMTP für Anfragen, Einladungen, Passwort-Reset |
| `QUEUE_CONNECTION=database` | Mails werden per Cron abgearbeitet (`schedule:run` alle 5 Min.); dasselbe Cron löst Erinnerungen (08:00), Mahnungen (08:10) und das Aufräumen unbestätigter Anfragen aus |
| `IMPRINT_*` | Impressum/Datenschutz des Betreibers (Name, Anschrift, E-Mail) |

Impressum und Datenschutz werden aus den `IMPRINT_*`-Variablen gespeist (siehe `config/imprint.php`). Kategorien liefert der Seeder (`database/seeders/DatabaseSeeder.php`).

## Lizenz

MIT, siehe [LICENSE](LICENSE).
