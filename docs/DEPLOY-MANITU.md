# Deployment auf Manitu Webhosting (Apache, PHP 8.3/8.4)

Das Frontend wird **lokal** gebaut (Manitu hat kein Node), Abhängigkeiten ohne Dev-Pakete installiert, dann per rsync übertragen.

## Einmalig

1. **Datenbank** im Manitu-Panel anlegen. Tabellen-Prefix `vv_` (`DB_PREFIX`) beibehalten, wenn die Datenbank geteilt wird.
   Manitu-Standard-Engine ist MyISAM; die App erzwingt `InnoDB` (`config/database.php`), sonst greifen Foreign-Key-Cascades nicht.
2. **PHP-Version** der Domain auf 8.3 oder 8.4 stellen.
3. **Verzeichnisse** per SSH: App neben `web/`, nur `public/` ist erreichbar:
   ```bash
   ssh USER@HOST
   mkdir -p ~/vereinsverleih
   # Domain-Verzeichnis zeigt auf public/ (vorher leeres web/DOMAIN entfernen)
   ln -s ~/vereinsverleih/public ~/web/DOMAIN
   ```
   (Pfad bei Manitu: `/home/sites/siteNNN/…`; `web/` ist der Document-Root-Bereich.)
4. **`.env`** auf dem Server anlegen (aus `.env.production.example`): `APP_KEY` mit `php artisan key:generate --show` lokal erzeugen, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, DB- und SMTP-Zugang.
5. **Cron** im Manitu-Panel (kleinster Takt: 5 Minuten, Typ *PHP-Skript* oder *Befehl*):
   ```
   cd /home/sites/siteNNN/vereinsverleih && php artisan schedule:run
   ```
   `schedule:run` startet alle 5 Min. `queue:work --stop-when-empty` und versendet damit die Mails (Cron-Laufzeit max. 120 s).
6. **Storage-Link** nach dem ersten Deploy: `php artisan storage:link`

## Jedes Update

```bash
SSH_TARGET=USER@HOST REMOTE_DIR=/home/sites/siteNNN/vereinsverleih ./deploy.sh
```

Das Skript baut das Frontend, installiert Composer-Pakete (`--no-dev`), überträgt per rsync und führt auf dem Server `migrate --force` und die Cache-Befehle aus.

## Stolperfallen

- `bootstrap/cache/packages.php` und `services.php` werden **nicht** übertragen (sie enthalten lokale Dev-Pakete) – das Skript schließt sie aus.
- Beim ersten Mal `php artisan db:seed --force` ausführen (in Produktion legt er nur Kategorien an, keine Demo-Daten).
- Ist `public/storage` ein Symlink-Problem, Bilder alternativ nach `public/uploads` legen und `FILESYSTEM_DISK`/URL anpassen.
- Mails: SMTP des Hosters verwenden, `MAIL_FROM_ADDRESS` auf eine Adresse der eigenen Domain setzen (SPF!).
