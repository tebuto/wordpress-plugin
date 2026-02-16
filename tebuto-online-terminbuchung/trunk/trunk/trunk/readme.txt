=== Tebuto - Online-Terminbuchung ===
Contributors: tebuto
Tags: online booking, appointment scheduling, calendar, Terminbuchung, Termine
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integriere die Online-Terminbuchung von Tebuto in deine WordPress-Website. Biete öffentliche Termine direkt auf deiner Seite an.

== Beschreibung ==

Mit dem Plugin **Tebuto - Online-Terminbuchung** kannst du die öffentlichen Termine deines Tebuto-Kontos einfach auf deiner Website anzeigen und buchbar machen.

= Funktionen =

* **Einfache Integration** - Verbinde dein Tebuto-Konto mit einem Klick
* **Shortcode** - Füge die Terminbuchung per Shortcode ein: `[tebuto_online_terminbuchung_widget]`
* **Gutenberg-Block** - Nutze den Tebuto-Block im WordPress-Editor
* **Anpassbare Darstellung** - Passe Hintergrundfarbe und Rahmen an
* **Responsive Design** - Optimiert für alle Bildschirmgrößen

= Wie es funktioniert =

1. Installiere und aktiviere das Plugin
2. Gehe zu **Tebuto → Einstellungen** im Admin-Menü
3. Klicke auf "Mit Tebuto verbinden" und melde dich an
4. Füge den Shortcode oder Block auf deiner gewünschten Seite ein
5. Deine öffentlichen Termine werden automatisch angezeigt

= Hinweis =

Für die Nutzung des Plugins ist ein Tebuto-Account erforderlich. Die Anmeldung ist unter [tebuto.de](https://tebuto.de) möglich.

== Externe Services ==

Dieses Plugin verbindet sich mit den APIs von Tebuto (*.tebuto.de), um die Online-Terminbuchungen zu ermöglichen.

Bei der Nutzung des Plugins werden folgende Daten an Tebuto übermittelt:

* **Authentifizierungsdaten** - Für die sichere Verbindung mit deinem Tebuto-Konto
* **Benutzerinformationen** - Daten wie Name und E-Mail-Adresse für Terminbuchungen
* **Terminangaben** - Details zum gebuchten Termin (Datum, Uhrzeit, Art)
* **Kommunikationsdaten** - Informationen für Benachrichtigungen und Erinnerungen

Diese Datenübermittlung ist notwendig, um die Funktionen des Plugins bereitzustellen.

Tebuto erfüllt alle Anforderungen der DSGVO. Daten werden ausschließlich innerhalb der EU gespeichert und verarbeitet.

* [Datenschutzerklärung](https://tebuto.de/datenschutzerklaerung)
* [Allgemeine Geschäftsbedingungen](https://tebuto.de/agb)

== Installation ==

= Automatische Installation =

1. Gehe zu **Plugins → Plugin hinzufügen**
2. Suche nach "Tebuto"
3. Klicke auf "Jetzt installieren" und dann "Aktivieren"

= Manuelle Installation =

1. Lade das Plugin herunter und entpacke es
2. Lade den Ordner `tebuto-online-terminbuchung` in `/wp-content/plugins/` hoch
3. Aktiviere das Plugin über **Plugins → Installierte Plugins**

= Nach der Installation =

1. Gehe zu **Tebuto → Einstellungen**
2. Verbinde dein Tebuto-Konto
3. Erstelle in Tebuto deine Terminkategorien und Termine
4. Füge den Shortcode oder Block auf deiner Seite ein

== Häufig gestellte Fragen ==

= Ist ein Tebuto-Account notwendig? =

Ja, um dieses Plugin nutzen zu können, benötigst du einen Tebuto-Account. Die Registrierung ist unter [tebuto.de](https://tebuto.de) möglich.

= Ist das Plugin kostenlos? =

Ja, das Plugin selbst ist kostenlos. Je nach Tebuto-Tarif können für erweiterte Funktionen Gebühren anfallen.

= Kann ich mehrere Buchungswidgets auf einer Seite verwenden? =

Ja! Ab Version 2.1.0 können mehrere Widgets mit unterschiedlichen Einstellungen auf derselben Seite verwendet werden. Nutze dazu den Shortcode mit individuellen Attributen, z.B. `[tebuto_online_terminbuchung_widget primary_color="#3b82f6" categories="1,2,3"]`.

= Funktioniert das Plugin mit Page Buildern? =

Ja, der Shortcode funktioniert mit allen gängigen Page Buildern wie Elementor, Divi, WPBakery und anderen.

= Wie kann ich das Design anpassen? =

Unter **Tebuto → Shortcode** kannst du die Hintergrundfarbe ändern und einen Rahmen aktivieren. Weitere Anpassungen sind über CSS möglich.

== Screenshots ==

1. Einstellungsseite - Verbindung mit Tebuto herstellen
2. Shortcode-Einstellungen - Widget konfigurieren
3. Frontend - Buchungswidget auf der Website

== Changelog ==

= 2.1.0 =
* **NEU: Mehrere Widgets pro Seite** - Verschiedene Buchungswidgets mit individuellen Einstellungen auf derselben Seite verwenden
* **NEU: Shortcode-Attribute** - Einstellungen direkt im Shortcode überschreiben, z.B. `[tebuto_online_terminbuchung_widget primary_color="#3b82f6" categories="1,2,3"]`
* **NEU: Anbieterfilter** - Neuer Filter zur Auswahl des Anbieters im Widget für Multi-User-Konten
* **NEU: Live-Shortcode-Generator** - Der angezeigte Shortcode aktualisiert sich automatisch bei Änderungen an den Einstellungen
* **Verbesserung: Kategorien-Deduplizierung** - Duplikate bei gleichnamigen Kategorien von Unternutzern werden automatisch entfernt
* **Verbesserung: Kategorienfilter** - Nur öffentlich buchbare Kategorien werden angezeigt, Tebuto-Meet-Kategorien werden ausgeblendet
* **Verbesserung: Eingabevalidierung** - Verbesserte Sanitisierung aller Shortcode-Attribute (Farben, Kategorien, CSS)
* **Verbesserung: Gutenberg-Block** - Anbieterfilter und konfigurierte Kategorien nun auch im Block-Editor verfügbar
* **Änderung: Rahmen-Standard** - Der Rahmen ist standardmäßig deaktiviert

= 2.0.0 =
* **NEU: Dashboard** - Übersicht über anstehende Termine direkt im WordPress-Admin
* **NEU: Terminverwaltung** - Termine einsehen, bestätigen und absagen
* **NEU: Kategorienverwaltung** - Terminkategorien direkt im Plugin verwalten
* **NEU: Theme Presets** - Vordefinierte Farbschemata für schnelle Anpassung
* **NEU: Erweiterte Widget-Konfiguration** - Primärfarbe, Textfarben, Rahmenfarbe anpassbar
* **NEU: Live-Vorschau** - Änderungen am Widget in Echtzeit sehen
* **NEU: Kategorienfilter** - Widget auf bestimmte Kategorien einschränken (Multiselect)
* **NEU: Schnellfilter** - Filter-Option für Multi-User-Konten
* **NEU: Custom CSS** - Eigenes CSS für individuelle Anpassungen
* **NEU: Schriftart übernehmen** - Option zur Übernahme der Website-Schriftart
* **Verbesserung: Gutenberg-Block** - Alle Einstellungen direkt im Block-Editor verfügbar
* **Verbesserung: Shortcode-Seite** - Komplett überarbeitete Oberfläche

= 1.0.0 =
* Erste stabile Version
* OAuth-Integration mit Tebuto
* Shortcode-Unterstützung
* Gutenberg-Block
* Anpassbare Widget-Einstellungen
* Professionelles Admin-Interface

== Upgrade Notice ==

= 2.1.0 =
Mehrere Widgets pro Seite, Anbieterfilter für Multi-User-Konten und Live-Shortcode-Generator!

= 2.0.0 =
Großes Update mit Dashboard, Terminverwaltung, erweiterten Widget-Einstellungen und Live-Vorschau!

= 1.0.0 =
Erste stabile Version des Tebuto Plugins.
