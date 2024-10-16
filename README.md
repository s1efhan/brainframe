# BrainFrame - Dokumentation

## Inhaltsverzeichnis
1. [Einleitung](#1-einleitung)
2. [Anforderungsanalyse](#2-anforderungsanalyse)
3. [Systemarchitektur](#3-systemarchitektur)
4. [Implementierungsdetails](#4-implementierungsdetails)
5. [Testdokumentation](#5-testdokumentation)
6. [Benutzerhandbuch](#6-benutzerhandbuch)
7. [Projektverlauf](#7-projektverlauf)
8. [Fazit und Ausblick](#8-fazit-und-ausblick)
9. [Literaturverzeichnis](#9-literaturverzeichnis)
10. [Anhänge](#10-anhänge)

## 1. Einleitung
BrainFrame ist ein digitales Kollaborationstool, das auf Vue3 und Laravel 11 basiert. Das Hauptziel der Anwendung besteht darin, Nutzern die Möglichkeit zu bieten, Ideen anonym zu sammeln und zu bewerten. Das Konzept von BrainFrame vereint traditionelle Brainstorming-Techniken mit modernen technologischen Ansätzen wie Künstlicher Intelligenz (KI) und Web Apps.
In der kreativen Branche und im akademischen Umfeld sind klassische Brainstorming-Methoden mit physischen Hilfsmitteln wie Stift und Papier oder Tafeln noch weit verbreitet. Alternativ kommen digitale Whiteboard-Lösungen wie Miro zum Einsatz. Diese Methoden weisen jedoch Einschränkungen hinsichtlich der Effizienz auf. Eine wesentliche Herausforderung des klassischen Brainstormings liegt in der oft unstrukturierten und unübersichtlichen Darstellung der Ergebnisse, was eine effiziente Weiterverarbeitung der gesammelten Ideen erschwert.
BrainFrame adressiert diese Problematik durch die Implementierung vorgegebener Strukturen und die automatische Generierung von Ergebnis-PDFs und CSVs. Dieser Ansatz zielt darauf ab, den Prozess der Ideenfindung und -verarbeitung zu optimieren.


## 2. Anforderungsanalyse
Die Anforderungsanalyse für BrainFrame umfasst folgende Aspekte:
- Integration etablierter Kollaborationsprozesse in die digitale Umgebung.
- Bereitstellung einer Nutzungsoption ohne vorherige Anmeldung, sowie die Möglichkeit zur Registrierung eines Benutzerkontos zur Speicherung von Sitzungsergebnissen.
- Implementierung verschiedener Beitrittsmöglichkeiten zu einer Sitzung, darunter QR-Code, PIN-Code und direkter Link.
- Entwicklung einer Funktion zum anonymen Einreichen von Ideen ("Ideen Pitching").
- Integration verschiedener Eingabemöglichkeiten für Ideen, einschließlich Text-, Bild- und Spracheingabe.
- Implementierung eines anonymen Bewertungssystems für eingereichte Ideen.
- Einsatz von KI-Technologie zur Vereinheitlichung und sprachlichen Korrektur der Eingaben.
- Entwicklung eines Moduls zur Durchführung von Evaluationsumfragen.
- Implementierung einer Funktion zum Versand von Ergebnis-PDFs per E-Mail.
- Bereitstellung von Konfigurationsmöglichkeiten für den Sitzungsleiter zur Anpassung der Sitzungseinstellungen.
- Integration einer KI-gestützten Funktion zur Generierung initialer Ideen als "Eisbrecher".
- Automatische Erstellung von Ergebnis-PDFs und CSV-Dateien nach Abschluss einer Sitzung.
- Implementierung einer Funktion zum Versand von Sitzungseinladungen per E-Mail, inklusive der Option zur Erstellung eines Kalendereintrags.
- Entwicklung eines Systems für anonyme Kommentare zu eingereichten Ideen.

## 3. Systemarchitektur
### 3.1 Überblick über die Systemkomponenten
Das BrainFrame-System basiert auf einer modernen, mehrschichtigen Architektur, die verschiedene Technologien und Komponenten integriert, um eine leistungsfähige und skalierbare Anwendung zu gewährleisten. Die Hauptkomponenten umfassen:
Frontend:
- Implementiert mit Vue3-Komponenten
- Bietet eine reaktive und benutzerfreundliche Oberfläche
Backend:
- Basiert auf dem Laravel MVC-Framework (Version 11)
- Verarbeitet Geschäftslogik und Datenmanagement
Websocket-Kommunikation:
- Realisiert durch Reverb
- Ermöglicht Echtzeit-Kommunikation zwischen Client und Server
Künstliche Intelligenz:
- Integration der OpenAI API
- Unterstützt KI-basierte Funktionen wie Ideengenerierung und Textverarbeitung
Datenbankmanagementsystem (DBMS):
- Verwendung von PostgreSQL
- Speichert und verwaltet alle persistenten Daten der Anwendung
Echo Listener:
- Komponente zur Verarbeitung von Echtzeit-Ereignissen
- Unterstützt die Websocket-Kommunikation
### 3.2 Verwendete Technologien und Begründung der Auswahl
PostgreSQL 14:
- Gewählt aufgrund seiner Zuverlässigkeit, Leistungsfähigkeit und Unterstützung komplexer Datenstrukturen
- Bietet robuste Funktionen für Datenkonsistenz und -integrität
Laravel 11 & Reverb Websockets:
- Laravel als PHP-Framework ermöglicht eine schnelle und sichere Entwicklung des Backends
- Reverb, als Teil des Laravel-Ökosystems, bietet nahtlose Integration für Echtzeit-Kommunikation
Laravel Forge und AWS EC2 T2.Micro:
- Laravel Forge vereinfacht die Bereitstellung und Verwaltung von Laravel-Anwendungen
- AWS EC2 T2.Micro bietet eine kosteneffiziente und skalierbare Infrastruktur für die Anwendung
Vue3:
- Gewählt für das Frontend aufgrund seiner Leistungsfähigkeit und Flexibilität
- Ermöglicht die Erstellung von reaktiven und dynamischen Benutzeroberflächen
Vite:
- Dient als Build-Tool und Entwicklungsserver
- Bietet schnelle Kompilierungszeiten und effizientes Hot Module Replacement für eine verbesserte Entwicklererfahrung
### 3.3 ERD nach Chen & Relationenschema

## 4. Implementierungsdetails
- Beschreibung wichtiger Algorithmen und Datenstrukturen
- Codebeispiele für zentrale Funktionen
- Erläuterung von Design-Entscheidungen

## 5. Testdokumentation
Jmeter Lasttest für die Anwendung auf einem AWS EC2 T2.Micro
import http from 'k6/http';
import ws from 'k6/ws';
export default function() {
  http.get('https://stefan-theissen.de');
  ws.connect('wss://ws.stefan-theissen.de/ws', function(socket) {
    socket.on('open', () => console.log('WebSocket verbunden'));
    socket.on('message', (data) => console.log('Nachricht erhalten:', data));
    socket.setTimeout(() => socket.close(), 3000);
  });
}
export let options = {
  vus: 100,
  duration: '30s',
};
Ergebnisse:
{
"testParameter": {
"getesteteWebseite": "https://stefan-theissen.de",
"websocketVerbindung": "wss://ws.stefan-theissen.de/app/iu44mbpzuikorpxuwyv3",
"lastSteigerung": [
{ "dauer": "30 Sekunden", "zielnutzer": 50 },
{ "dauer": "1 Minute", "zielnutzer": 100 },
{ "dauer": "3 Minuten", "zielnutzer": 100 },
{ "dauer": "30 Sekunden", "zielnutzer": 0 }
],
"pauseZwischenAnfragen": "0,1 Sekunden",
"gesamtTestdauer": "5 Minuten und 2,5 Sekunden",
"maximaleSynchroneNutzer": 100,
"getesteteAPIRouten": [
{
"route": "/api/session/ping",
"methode": "POST",
"funktion": "Aktualisiert den Aktivitätsstatus eines Nutzers in einer Sitzung",
"details": "Setzt 'is_active' auf true, aktualisiert 'last_ping', prüft inaktive Nutzer und gibt die Anzahl aktiver Teilnehmer zurück"
},
{
"route": "/api/user",
"methode": "POST",
"funktion": "Erstellt einen neuen Benutzer",
"details": "Prüft, ob die Benutzer-ID existiert. Falls ja, gibt es eine Meldung zurück. Falls nein, wird ein neuer Benutzer mit einem Token erstellt"
},
{
"route": "/api/session/{sessionId}",
"methode": "GET",
"funktion": "Ruft detaillierte Informationen zu einer Sitzung ab",
"details": "Liefert Sitzungsdaten, Teilnehmerinformationen, Ideenzählungen und Abstimmungsphasen-Details"
}
]
}
}

{
"testResults": {
"checksSuccess": 99.97,
"totalChecks": 17374,
"failedChecks": 4,
"dataReceived": "19 MB",
"dataSent": "4.2 MB",
"httpRequests": {
"total": 9928,
"rate": 32.815229,
"avgDuration": 1.72,
"maxDuration": 2.75,
"failureRate": 0
},
"iterations": {
"total": 2482,
"rate": 8.203807,
"avgDuration": 10.1,
"maxDuration": 12.7
},
"webSockets": {
"connectSuccess": 99,
"msgsSent": 2478,
"msgsReceived": 2478,
"avgSessionDuration": 3.11
}
}
}

## 6. Benutzerhandbuch
- Bedienungsanleitung mit Screenshots

## 7. Projektverlauf
Zeitplan und Meilensteine
- 01.09.24 1: Das Projekt ist vorbereitet und geplant 
- 15.09.24 2: Ein erster Prototyp (MVP) wurde implementiert
- 27.09.24 3: Testbare/Nutzbare Software ist bereitgestellt
- 12.10.24 4: Feinschliff ist erledigt
- 25.10.24 5: Quantitative und Qualitative Daten wurden gesammelt
- 30.10.24 6: Projektarbeit wurde präsentiert

Herausforderungen und Lösungsansätze

Lessons Learned

## 8. Ausblick
- Mögliche zukünftige Erweiterungen

## 9. Literaturverzeichnis
- Laravel Doku
- Vue3 Doku
- Laravel Forge Doku
- Laravel Reverb Doku
