=== Tebuto - Online-Terminbuchung ===
Contributors: tebuto
Tags: Praxissoftware, Terminbuchung, Therapie, Buchungswidget, Kalender
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Verbinde deine Tebuto-Praxissoftware mit WordPress: Buchungswidget einbetten, Termine verwalten und Buchungen direkt aus dem Admin-Bereich bearbeiten.

== Beschreibung ==

**Tebuto** ist eine Praxissoftware für Therapeut:innen, Berater:innen und Coaches auf Selbstzahlerbasis. Neben Online-Terminbuchung bietet Tebuto Klientenverwaltung, Zahlungen, Kalenderintegration, sichere Kommunikation und weitere Werkzeuge für den Praxisalltag.

Mit diesem Plugin bindest du die **Online-Terminbuchung** deines Tebuto-Kontos in deine WordPress-Website ein. Besucher:innen können Termine direkt auf deiner Seite buchen, ohne zu einer externen Buchungsseite weitergeleitet zu werden. Zusätzlich kannst du anstehende Termine und Buchungen bequem im WordPress-Admin einsehen und bearbeiten.

= Funktionen =

* **Buchungswidget** – Öffentliche Termine per Shortcode oder Gutenberg-Block auf deiner Website anzeigen
* **WordPress-Dashboard** – Übersicht über anstehende Termine direkt im Admin-Bereich
* **Buchungsverwaltung** – Buchungen einsehen, bestätigen und absagen
* **Kategorienverwaltung** – Terminkategorien aus WordPress heraus verwalten
* **Anpassbare Darstellung** – Farben, Theme-Presets, Kategorienfilter, Schnellfilter und eigenes CSS
* **Multi-User-Praxen** – Anbieterfilter für Praxen mit mehreren Therapeut:innen
* **Mehrere Widgets pro Seite** – Verschiedene Konfigurationen über Shortcode-Attribute
* **Responsive Design** – Optimiert für alle Bildschirmgrößen

= Wie es funktioniert =

1. Installiere und aktiviere das Plugin
2. Registriere dich bei [tebuto.de](https://tebuto.de) oder melde dich mit deinem bestehenden Tebuto-Konto an
3. Gehe zu **Tebuto → Verbindung** im WordPress-Admin und verbinde dein Konto
4. Konfiguriere das Widget unter **Tebuto → Shortcode**
5. Füge den Shortcode oder Gutenberg-Block auf deiner gewünschten Seite ein

= Hinweis zu Tebuto und Abonnement =

Für die Nutzung des Plugins ist ein **aktives Tebuto-Konto** erforderlich. Das WordPress-Plugin selbst ist kostenlos.

Tebuto kann **30 Tage lang kostenlos getestet** werden. Anschließend ist ein **monatliches Abonnement** erforderlich – es gibt keinen dauerhaften kostenlosen Tarif. Alle Funktionen von Tebuto, einschließlich der Online-Terminbuchung, stehen im Rahmen des Abonnements zur Verfügung.

Aktuelle Preise und Leistungsumfang findest du auf [tebuto.de](https://tebuto.de).

== Externe Services ==

Dieses Plugin verbindet sich mit den APIs von Tebuto (*.tebuto.de), um die Online-Terminbuchung und die Verwaltungsfunktionen im WordPress-Admin bereitzustellen.

Bei der Nutzung des Plugins werden folgende Daten an Tebuto übermittelt:

* **Authentifizierungsdaten** – Für die sichere Verbindung mit deinem Tebuto-Konto
* **Benutzerinformationen** – Daten wie Name und E-Mail-Adresse für Terminbuchungen
* **Terminangaben** – Details zum gebuchten Termin (Datum, Uhrzeit, Art)
* **Kommunikationsdaten** – Informationen für Benachrichtigungen und Erinnerungen

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

1. Erstelle ein Tebuto-Konto auf [tebuto.de](https://tebuto.de) oder melde dich an
2. Gehe zu **Tebuto → Verbindung** und verbinde dein Tebuto-Konto
3. Richte in Tebuto deine Terminkategorien und Verfügbarkeiten ein
4. Konfiguriere das Widget unter **Tebuto → Shortcode**
5. Füge den Shortcode oder Block auf deiner Seite ein

== Häufig gestellte Fragen ==

= Ist ein Tebuto-Account notwendig? =

Ja. Das Plugin verbindet deine WordPress-Website mit deinem Tebuto-Konto. Die Registrierung ist unter [tebuto.de](https://tebuto.de) möglich.

= Ist das Plugin kostenlos? =

Ja, das WordPress-Plugin selbst ist kostenlos und unter der GPLv2-Lizenz veröffentlicht.

Für Tebuto als Praxissoftware gilt: Du kannst Tebuto 30 Tage lang kostenlos testen. Danach ist ein monatliches Abonnement erforderlich. Es gibt keinen dauerhaften kostenlosen Tarif. Details zu Preisen und Leistungen findest du auf [tebuto.de](https://tebuto.de).

= Was ist Tebuto genau? =

Tebuto ist eine Praxissoftware für Therapeut:innen, Berater:innen und Coaches auf Selbstzahlerbasis. Das Plugin integriert vor allem die Online-Terminbuchung in deine Website. Die vollständige Praxisverwaltung – Kalender, Klienten, Zahlungen, Kommunikation und mehr – erfolgt in der Tebuto-Webanwendung unter [app.tebuto.de](https://app.tebuto.de).

= Kann ich mehrere Buchungswidgets auf einer Seite verwenden? =

Ja! Ab Version 2.1.0 können mehrere Widgets mit unterschiedlichen Einstellungen auf derselben Seite verwendet werden. Nutze dazu den Shortcode mit individuellen Attributen, z.B. `[tebuto_online_terminbuchung_widget primary_color="#3b82f6" categories="1,2,3"]`.

= Funktioniert das Plugin mit Page Buildern? =

Ja, der Shortcode funktioniert mit allen gängigen Page Buildern wie Elementor, Divi, WPBakery und anderen.

= Wie kann ich das Design anpassen? =

Unter **Tebuto → Shortcode** kannst du Farben, Theme-Presets, Kategorienfilter, Schnellfilter und weiteres CSS anpassen. Einstellungen lassen sich auch direkt im Shortcode überschreiben.

== Screenshots ==

1. Einstellungsseite - Verbindung mit Tebuto herstellen
2. Shortcode-Einstellungen - Widget konfigurieren
3. Frontend - Buchungswidget auf der Website

== Changelog ==

= 2.3.0 =
* **Fix: Multi-User-Widget** – Veraltete `configured-therapists`-Option entfernt; Termine von verwalteten Konten werden nicht mehr fälschlich ausgeblendet, wenn „Termine von verwalteten Konten anzeigen“ aktiv ist
* **Fix: Tebuto-Verbindung** – Access Tokens werden bei Ablauf automatisch erneuert, sodass Kategorien und andere API-Daten nicht mehr nach kurzer Zeit mit Fehlern abbrechen
* **Verbesserung: Sitzung abgelaufen** – Admin-Seiten und Gutenberg-Block zeigen bei abgelaufener Verbindung eine klare Meldung mit Button zur erneuten Anmeldung statt generischer API-Fehler
* **Verbesserung: Erneute Anmeldung** – Widget-Einstellungen bleiben beim erneuten Verbinden erhalten; nur die OAuth-Tokens werden zurückgesetzt

= 2.2.2 =
* **Verbesserung: Widget-Einstellungen** – Kategorien-Bereich steht jetzt an erster Stelle in Block-Editor und Shortcode-Seite
* **Verbesserung: Kategorieauswahl zuerst** – Option ist in den Kategorien-Bereich integriert und wird bei nur einer ausgewählten Kategorie deaktiviert statt ausgeblendet
* **Verbesserung: Gutenberg-Block & Shortcode-Seite** – Einheitliche Abschnittsreihenfolge, Beschriftungen und Hilfetexte

= 2.2.1 =
* **Fix: Produktions-URLs** – Entfernt lokale Entwicklungs-Domains aus dem Plugin-Code; alle Standard-URLs verweisen auf *.tebuto.de
* **Fix: Gutenberg-Block** – Gespeicherte Blöcke verwenden immer die Produktions-Widget-URL

= 2.2.0 =
* **NEU: Standort-Schnellfilter** – Optionaler Schnellfilter nach Standort/Ort im Buchungswidget
* **NEU: Kategorieauswahl zuerst** – Option, Besucher:innen zuerst eine Terminart wählen zu lassen
* **NEU: Shortcode-Attribute** – `show_location_quick_filter` und `show_category_selection_first` auch per Shortcode steuerbar
* **Verbesserung: Gutenberg-Block** – Neue Widget-Optionen im Block-Editor verfügbar

= 2.1.0 =
* **NEU: Mehrere Widgets pro Seite** - Verschiedene Buchungswidgets mit individuellen Einstellungen auf derselben Seite verwenden
* **NEU: Shortcode-Attribute** - Einstellungen direkt im Shortcode überschreiben, z.B. `[tebuto_online_terminbuchung_widget primary_color="#3b82f6" categories="1,2,3"]`
* **NEU: Anbieterfilter** - Neuer Filter zur Auswahl des Anbieters im Widget für Multi-User-Konten
* **NEU: Live-Shortcode-Generator** - Der angezeigte Shortcode aktualisiert sich automatisch bei Änderungen an den Einstellungen
* **NEU: Konfigurierbare URLs** - API-, Auth- und Widget-URLs können per wp-config.php überschrieben werden (für lokale Entwicklung)
* **Fix: Widget für nicht angemeldete Besucher** - Das Buchungswidget wird nun korrekt für alle Seitenbesucher angezeigt, nicht nur für eingeloggte WordPress-Nutzer
* **Fix: Verbindungstrennung** - Beim Trennen der Tebuto-Verbindung werden nun alle gespeicherten Daten vollständig entfernt
* **Fix: Doppelte Kategorien im Widget** - Bei Multi-User-Konten werden gleichnamige Kategorien verschiedener Anbieter im Widget nur einmal angezeigt
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

= 2.3.0 =
Wichtiges Update für Multi-User-Praxen und längere Verbindungsstabilität: behobene Subaccount-Anzeige im Widget, automatische Token-Erneuerung und klarere Meldung bei abgelaufener Tebuto-Sitzung.

= 2.2.2 =
Überarbeitete Widget-Einstellungen: Kategorien stehen jetzt an erster Stelle, die Kategorieauswahl-Option ist dort integriert und wird bei nur einer Kategorie automatisch deaktiviert.

= 2.2.1 =
Kleines Update: Stellt sicher, dass das Plugin ausschließlich Produktions-URLs (*.tebuto.de) verwendet.

= 2.2.0 =
Neue Widget-Optionen: Standort-Schnellfilter und Kategorieauswahl vor der Terminauswahl.

= 2.1.0 =
Mehrere Widgets pro Seite, Anbieterfilter für Multi-User-Konten und Live-Shortcode-Generator!

= 2.0.0 =
Großes Update mit Dashboard, Terminverwaltung, erweiterten Widget-Einstellungen und Live-Vorschau!

= 1.0.0 =
Erste stabile Version des Tebuto Plugins.
