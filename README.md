# tOmek-Assistent — Grundgerüst

PHP-basiert (läuft auf jedem Alfahosting-Reseller-Tarif, kein Node.js nötig).
Telegram-Bot + tägliche Erinnerung per Cronjob + KI-gestützte Inbox-Sortierung über die Claude API.

## Was es tut

- Du schickst dem Telegram-Bot einen Link oder eine Idee.
- Der Bot lässt sie von Claude einordnen und schickt dir **Buttons**:
  passendes Projekt / neues Projekt / später.
- `/liste` zeigt den aktuellen Stand aller Projekte.
- Täglich (per Cronjob) schickt dir der Bot automatisch eine Zusammenfassung
  der aktiven Projekte mit offenem nächsten Schritt.

## Einrichtung (Schritt für Schritt) — für deine Struktur

App-Ordner: `/html/hugahuga/kira/`
Config-Ordner: `/.configs/`

1. **Bot erstellen**: In Telegram `@BotFather` öffnen → `/newbot` → Token kopieren.
2. **Chat-ID herausfinden**: Dem neuen Bot einmal `/start` schreiben, dann
   `https://api.telegram.org/bot<TOKEN>/getUpdates` im Browser öffnen und die
   `"chat":{"id": ...}` ablesen.
3. **Claude API-Key** aus der Anthropic Console holen (falls noch nicht vorhanden).
3b. **Brave Search API-Key** kostenlos holen unter https://brave.com/search/api/
    (Free-Tier: 2.000 Suchen/Monat — reicht für die tägliche Nachrichten-Zusammenfassung locker).
4. **Dateien hochladen**:
   - `webhook.php`, `storage.php`, `telegram.php`, `ai_sort.php`, `cron_reminder.php`
     nach `/html/hugahuga/kira/`
   - `config.php` UND den Ordner `data/` nach `/.configs/`
     (Pfad `../../../.configs/config.php` in den PHP-Dateien geht davon aus,
     dass `/html/` und `/.configs/` beide direkt im selben Wurzelverzeichnis
     liegen — bei Abweichung Pfad in den `require_once`-Zeilen anpassen.)
5. **`/.configs/config.php` ausfüllen**: Bot-Token, Chat-ID, Claude-Key, und einen
   selbst ausgedachten `WEBHOOK_SECRET` (langer Zufallsstring) eintragen.
6. **`data/` beschreibbar machen**: Rechte auf 755/775 setzen, damit PHP dort
   schreiben darf.
7. **Webhook bei Telegram registrieren** (einmalig, im Browser aufrufen):
   ```
   https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://deinedomain.de/pfad/zu/kira/webhook.php?secret=<WEBHOOK_SECRET>
   ```
8. **Cronjob einrichten**: Im Alfahosting-Kundencenter unter "Cronjobs" einen
   täglichen Job anlegen, der aufruft:
   ```
   php /html/hugahuga/kira/cron_reminder.php
   ```
   (Vollen Server-Pfad verwenden, nicht die URL.)

## Wichtig

- **`config.php` niemals öffentlich zugänglich machen** — enthält Zugangsdaten.
  Falls dein Tarif das erlaubt, eine Ebene oberhalb des Webroots ablegen und
  per `require` referenzieren.
- Die Daten liegen in `data/projects.json` — unabhängig vom Browser-Tool,
  das wir vorher gebaut haben. Ein Abgleich beider (z.B. per kleiner
  Sync-Funktion) ist ein möglicher nächster Ausbauschritt.
- 5 Cronjobs sind im Reseller-Tarif meist inklusive, weitere kosten extra.

## Nächste Ausbaustufen (nach Wunsch)

- Web-Oberfläche (wie das Browser-Tool) direkt mit `data/projects.json` verbinden
- Erinnerungen mit Fristen (z.B. LEADER-Antrag November) vorziehen
- Bot kann auch Aufgaben aktiv erledigen (E-Mail-Entwürfe, Recherchen) statt nur zu sortieren

### Vorgemerkt: Automatisierte Blog-Veröffentlichung interessanter News

Idee: Als "News" markierte Einträge automatisch umformulieren, SEO-optimieren
und auf einem Blog veröffentlichen lassen.

Zu klären, bevor das umgesetzt wird:
- **Ziel-Blog/Plattform**: eigenes WordPress? Statischer Generator? Neue Domain?
- **Redaktionelle Kontrolle**: vollautomatisch veröffentlichen ist riskant
  (Fehlinformationen, rechtliche Fragen bei News-Umformulierung, Qualität).
  Empfehlung: erst als Entwurf ablegen, Freigabe per Telegram-Button
  ("✅ Veröffentlichen"), erst dann live schalten.
- **Quellentreue**: bei News-Paraphrasierung besonders auf korrekte
  Zuschreibung und keine Falschdarstellung der Originalquelle achten.
- **SEO-Ansatz**: Keyword-Fokus, Meta-Description, interne Verlinkung —
  müsste pro Blog-Ziel definiert werden.

→ Wenn du bereit bist, das anzugehen, einfach Bescheid geben.
