
In diesem Ordner läuft ein TYPO3-Projekt der Version 14 in einem ddev-System.
Ich möchte erreichen, dass die Stammdaten zu einer Webseite wie Inhaber, Ansprechpartner, Telefon, STraße, PLZ, Stadt Fax, Email, Jugendschutzbeauftragter, Datenschutzbeauftragter, KI-Beauftragter, Barrierefreiheitsbeauftragter für den Nutzer im CK-Editor als Variable verfügbar gemacht werden. Als Speicherort für die Daten schwebt mit die config.yaml im Ordner configuration/Sets/ vor.
Sinnvoll wäre vermutlich auch eine eigene Erweiterungsextension dafür zu schreiben.
Wie würdest du sinnvoll vorgehen? Untergliedere dein Vorgehen in möglichst kleine Schritte. Bitte gib an, wie man jeweils den Arbeitsfortschritt prüfen kann.
Bitte erläutere zuerst deinen Plan.

Vorschlag 5-Schritt-Plan
1. Schritt Extension erstellen


1. Prüfung. Fehlerkorrektur. Funktioniert
>>  Hintergrund: TYPO3 erwartet in settings.definitions.yaml eine flache Struktur – jeder Schlüssel direkt unter settings: ist eine eigenständige Definition.
Verschachtelung wie siteMetadata.owner ist nicht möglich. Daher das Präfix sitemetadata direkt im Schlüsselnamen (sitemetadataOwner etc.).

1.b. Welche Schritte sind noch offen?
Schritt 2 – CKEditor 5 Plugin (JavaScript)                                                                                Schritt 3 – RTE-YAML-Konfiguration                                                                                        Schritt 4 – PageTSconfig für RTE einbinden                                                                                Schritt 5 – Frontend-Rendering                                                                                              Schritt 6 – TypoScript-Setup


2.  Schritt 2 – CKEditor 5 Plugin (JavaScript) - Integration von Button
    Der liest alles aus und es läuft

2.b. Mehrere Korrektur-schleifen
Musste im Browser-Console nachschauen, um den Fehler eingrenzen zu helfen

3. Mache mit schritt 5 weiter (PHP-Klasse für das Frontend-Rendering)
   Das System macht gute Vorschläge
Mehrere Korrekturschleifen
4. ❯ Kannst du eine Dokumentation für die Extension schreiben. Eine MD-Datei für Editoren. Eine für Integratoren und eine für Developer.
5. Kannst du noch Unit-Test für den php-code erstellen.  Was sollte alles getestet werden?     

