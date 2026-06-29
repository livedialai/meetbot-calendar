=== MeetBot Calendar ===
Contributors: gofonia
Tags: booking, calendar, meet-bot, scheduling, video-meeting
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zeigt verfügbare Buchungszeiten von Meet.bot auf deiner WordPress-Seite an. Mit Google Meet Integration.

== Description ==

**MeetBot Calendar** verbindet deine WordPress-Seite mit der [Meet.bot](https://meet.bot) Buchungsplattform. Zeige verfügbare Termine an und lasse Besucher direkt buchen — einfach, schnell, mobil-optimiert.

**Features:**

* Interaktiver Monats-Kalender mit verfügbaren Zeit-Slots
* Direkte Terminbuchung auf deiner Seite
* Google Meet Video-Integration (automatische Meet-Links)
* Eigene Bestätigungs-E-Mails (WordPress/Brevo)
* Admin-Einstellungen mit API-Key und Buchungsseiten-Auswahl
* Mehrsprachig (Deutsch/Englisch)
* Mobile-optimiertes responsives Design
* "Powered by GoFonIA" Branding

**So funktioniert es:**

1. Erstelle einen kostenlosen Account bei [Meet.bot](https://meet.bot)
2. Verbinde deine Kalender (Google, Outlook, Apple)
3. Installiere dieses Plugin und gib deinen API-Key ein
4. Füge den Shortcode `[meetbot_calendar]` auf einer Seite hinzu
5. Fertig! Besucher können jetzt Termine buchen

**Meet.bot Vorteile:**

* Synchronisiert mit Google Kalender, Microsoft Outlook und Apple Kalender
* Automatische Google Meet Links für Video-Anrufe
* Keine Doppelbuchungen durch Echtzeit-Sync
* Kostenlos nutzbar

== Installation ==

1. Lade das Plugin über die WordPress Plugin-Verwaltung hoch oder entpacke den Ordner in `/wp-content/plugins/`
2. Aktiviere das Plugin unter "Plugins" in WordPress
3. Gehe zu "Einstellungen > MeetBot Kalender" und trage deinen Meet.bot API-Key ein
4. Wähle deine Buchungsseite aus
5. Füge den Shortcode `[meetbot_calendar]` auf einer beliebigen Seite oder einem Beitrag ein

== Frequently Asked Questions ==

= Brauche ich einen Meet.bot Account? =

Ja, erstelle einen kostenlosen Account bei [meet.bot](https://meet.bot). Dort erhältst du auch den API-Key.

= Welche Kalender werden unterstützt? =

Meet.bot unterstützt Google Kalender, Microsoft Outlook und Apple Kalender (via CalDAV).

= Kann ich eigene E-Mails senden? =

Ja! Aktiviere "Eigene E-Mail senden" in den Einstellungen. Das Plugin sendet dann Bestätigungs-E-Mails über WordPress (z.B. mit Brevo/SMTP). Die E-Mail-Vorlage kann mit Platzhaltern wie {name}, {datum}, {uhrzeit} und {meet_link} angepasst werden.

= Funktioniert Google Meet automatisch? =

Ja, wenn "Google Meet automatisch erstellen" aktiviert ist, erstellt Meet.bot automatisch einen Video-Link für jede Buchung.

= Ist das Plugin DSGVO-konform? =

Das Plugin speichert keine personenbezogenen Daten in WordPress. Alle Buchungsdaten werden direkt an Meet.bot übermittelt. Bitte prüfe die Meet.bot Datenschutzbestimmungen für Details.

== Screenshots ==

1. Kalender-Ansicht mit verfügbaren Slots
2. Buchungsformular
3. Bestätigungsseite mit Video-Link
4. Admin-Einstellungen

== Changelog ==

= 1.0.0 =
* Initiale Veröffentlichung
* Meet.bot API Integration (Slots, Buchung, Kalender)
* Monats-Kalender mit Slot-Anzeige
* Buchungsformular mit Name, E-Mail, Notizen
* Google Meet Video-Integration
* Eigene Bestätigungs-E-Mails (WordPress/Brevo)
* Admin-Einstellungen mit API-Key Test
* Mehrsprachig (Deutsch/Englisch)
* Mobile-optimiertes responsives Design
* "Powered by GoFonIA" Footer

== Upgrade Notice ==

= 1.0.0 =
Initiale Veröffentlichung.
