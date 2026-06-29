# MeetBot Calendar

**Zeigt verfügbare Buchungszeiten von [Meet.bot](https://meet.bot) auf deiner WordPress-Seite an.**

Open Source WordPress Plugin von [GoFonIA](https://gofonia.de).

## Features

- 📅 Wochen-Kalender mit verfügbaren Slots
- 🌍 Mehrsprachig (DE/EN, erweiterbar)
- 🎨 Light/Dark Theme
- ⚡ Direkte Buchung über die Seite
- 🔑 Einfache Einrichtung: nur API-Key + Buchungsseite wählen
- 📱 Mobile-optimiert
- 🔗 "Powered by GoFonIA" Branding

## Installation

1. Plugin-Ordner `meetbot-calendar/` in `/wp-content/plugins/` hochladen
2. In WordPress unter **Plugins** aktivieren
3. Unter **Einstellungen → MeetBot Kalender** den API-Key eingeben
4. Buchungsseite wählen
5. Shortcode `[meetbot_calendar]` auf einer Seite einfügen

## Konfiguration

### API-Key

1. Bei [Meet.bot](https://meet.bot) registrieren
2. Unter Einstellungen → API-Key kopieren
3. In WordPress einfügen und "Verbindung testen" klicken

### Shortcode

```
[meetbot_calendar]
```

**Optionale Parameter:**

| Parameter | Werte | Standard |
|-----------|-------|----------|
| `lang` | `de`, `en` | WordPress-Sprache |
| `theme` | `light`, `dark` | `light` |

**Beispiel:**
```
[meetbot_calendar lang="en" theme="dark"]
```

## Voraussetzungen

- WordPress 5.0+
- PHP 7.4+
- Meet.bot Account mit API-Key

## Lizenz

GPL v2 or later — siehe [LICENSE](LICENSE).

## Entwicklung

Entwickelt von [GoFonIA](https://gofonia.de).
GitHub: [livedialai/meetbot-calendar](https://github.com/livedialai/meetbot-calendar)

## Changelog

### 1.0.0
- Initiale Veröffentlichung
- Meet.bot API Integration (Slots, Buchung, Kalender)
- Wochen-Kalender mit Slot-Anzeige
- Buchungsformular
- Admin-Einstellungen mit API-Key und Seiten-Auswahl
- Light/Dark Theme
- Deutsch/Englisch
- "Powered by GoFonIA" Footer
- Activation Webhook an admin.gomeetme.de
