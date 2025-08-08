# BrainFrame - Dokumentation
Demo: https://stefan-theissen.de/brainframe/

Preview Video: https://youtu.be/7MdvP6U642o?si=xyAIF_Q0yxrJYv15
## Inhaltsverzeichnis
1. [Einleitung](#1-einleitung)
2. [Anforderungsanalyse](#2-anforderungsanalyse)
3. [Systemarchitektur](#3-systemarchitektur)
4. [Implementierungsdetails](#4-implementierungsdetails)
5. [Testdokumentation](#5-testdokumentation)
6. [Benutzerhandbuch](#6-benutzerhandbuch)
7. [Projektverlauf](#7-projektverlauf)
8. [Ausblick](#8-ausblick)
9. [Literaturverzeichnis](#9-literaturverzeichnis)

## 1. Einleitung
Dieses Projekt ist im Rahmen meines Praxisprojekts als Vorbereitung für meine Bachelorarbeit am Fachbereich 5 (Elektrotechnik und Informationstechnik) an der FH Aachen entstanden.
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

**PostgreSQL 14:**
- Gewählt aufgrund seiner Zuverlässigkeit, Leistungsfähigkeit und Unterstützung komplexer Datenstrukturen
- Bietet robuste Funktionen für Datenkonsistenz und -integrität

**Laravel 11 & Reverb Websockets:**
- Laravel als PHP-Framework ermöglicht eine schnelle und sichere Entwicklung des Backends
- Reverb, als Teil des Laravel-Ökosystems, bietet nahtlose Integration für Echtzeit-Kommunikation

**Laravel Forge und AWS EC2 T2.Micro:**
- Laravel Forge vereinfacht die Bereitstellung und Verwaltung von Laravel-Anwendungen
- AWS EC2 T2.Micro bietet eine kosteneffiziente und skalierbare Infrastruktur für die Anwendung

**Vue3:**
- Gewählt für das Frontend aufgrund seiner Leistungsfähigkeit und Flexibilität
- Ermöglicht die Erstellung von reaktiven und dynamischen Benutzeroberflächen

**Vite:**
- Dient als Build-Tool und Entwicklungsserver
- Bietet schnelle Kompilierungszeiten und effizientes Hot Module Replacement für eine verbesserte Entwicklererfahrung

### 3.3 ERD nach Chen & Relationenschema
[Users] 1 -- 0..N [Sessions]
[Users] 1 -- 0..N [Personal_Access_Tokens]
[Users] 1 -- 0..N [BF_Contributors]
[Users] 1 -- 0..N [BF_Sessions] (als Host)
[Users] 1 -- 0..N [BF_Survey_Responses]

[BF_Methods] 1 -- 0..N [BF_Sessions]
[BF_Methods] m -- N [BF_Roles]

[BF_Roles] 1 -- 0..N [BF_Contributors]

[BF_Sessions] 1 -- 0..N [BF_Contributors]
[BF_Sessions] 1 -- 0..N [BF_Ideas]
[BF_Sessions] 1 -- 0..N [BF_Votes]
[BF_Sessions] 1 -- 0..N [API_Logs]

[BF_Contributors] 1 -- 0..N [BF_Ideas]
[BF_Contributors] 1 -- 0..N [BF_Votes]
[BF_Contributors] 1 -- 0..N [API_Logs]

[BF_Ideas] 1 -- 0..N [BF_Votes]
[BF_Ideas] 0..1 -- 0..N [BF_Ideas] (Original zu abgeleiteten Ideen)

- users(id, name, email, email_verified_at, password, remember_token, created_at, updated_at, token)
- password_reset_tokens(email, token, created_at)
- sessions(id, user_id, ip_address, user_agent, payload, last_activity)
- cache(key, value, expiration)
- cache_locks(key, owner, expiration)
- jobs(id, queue, payload, attempts, reserved_at, available_at, created_at)
- job_batches(id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at)
- failed_jobs(id, uuid, connection, queue, payload, exception, failed_at)
- personal_access_tokens(id, tokenable_id, tokenable_type, name, token, abilities, last_used_at, expires_at, created_at, updated_at)
- bf_methods(id, name, description, time_limit, round_limit, created_at, updated_at, idea_limit)
- bf_roles(id, name, description, icon, created_at, updated_at)
- bf_sessions(id, target, method_id, host_id, phase, collecting_round, vote_round, is_paused, created_at, updated_at, seconds_left)
- bf_contributors(id, session_id, role_id, user_id, last_ping, is_active, created_at, updated_at)
- bf_methods_roles(id, method_id, role_id, created_at, updated_at)
- bf_ideas(id, created_at, updated_at, text_input, image_file_url, session_id, round, contributor_id, title, description, tag, original_idea_id)
- bf_votes(id, session_id, idea_id, contributor_id, vote_type, value, round, created_at, updated_at)
- api_logs(id, session_id, contributor_id, request_data, response_data, prompt_tokens, completion_tokens, created_at, updated_at)
- bf_survey_responses(id, user_id, session_id, timestamp, ideas_novelty_relevance, ideas_quantity_diversity, tool_ease_of_use, tool_thought_organization, anonymous_input_openness, ai_support_helpfulness, ai_suggestions_relevance, ai_inspiration, structure_method_facilitation, tool_effectiveness, idea_evaluation_transparency, rating_methods_understandability, result_pdf_usefulness, result_pdf_clarity, tool_future_use, tool_recommendation, session_expectations, known_method_635, known_method_walt_disney, known_method_crazy_8, known_method_brainstorming, known_method_6_thinking_hats, known_method_none, valuable_aspects, desired_improvements, unexpected_benefits_challenges, additional_comments, age, occupation, industry, created_at, updated_at)


## 4. Implementierungsdetails
Anwendungs-Logik
- Der Host erstellt eine Session und legt dabei Methode (session.method) und Ziel (session.target) fest
- Teilnehmer wählen ihre Rolle (icon) aus und treten der Session bei
- Eine Session kann verschiedene Zustände einnehmen: Sie ist entweder pausiert oder gestartet und befindet sich dabei entweder in der Sammel-Phase (collecting), Bewertungs-Phase (voting) oder Abschluss-Phase (closing)
- Der Phasenwechsel wird (vom Host-Client) entweder beim Ablaufen des Timers, beim Drücken eines Buttons (Runde Beenden) oder, wenn alle Teilnehmer abgestimmt haben initiiert, wobei gleichzeitig zum Datenbankupdate auch ein Reverb-Event ausgelöst wird.
- Die Sammel-Phase ist in verschiedene Runden unterteilt
- Je nach ausgewählter Methode (6-3-5, Walt Disney, 6-Thinking Hats oder Crazy 8) unterscheidet sich die Anzahl an Sammel-Runden und das Zeit- sowie Ideen-Limit einer Runde
- Zusätzlich dazu gibt es Methodenspezifische Funktionalitäten, wie das dynamische Rollen-Wechseln nach jeder Runde bei der 6-Thinking Hats Methode oder das Anzeigen der Nachbar-Ideen aus der Vorgängerrunde in der 6-3-5 Methode

//SessionController.php
private function rotateContributorRoles(Session $session)
{

    $contributors = $session->contributors;
    $roles = Role::whereHas('methods', function ($query) use ($session) {
        $query->where('bf_methods.id', $session->method_id);
    })->orderBy('bf_roles.created_at')->get();
    $rolesCount = $roles->count();
    
    foreach ($contributors as $index => $contributor) {
        $oldRoleId = $contributor->role_id;
        $newRoleIndex = ($index + $session->collecting_round - 1) % $rolesCount;
        $newRole = $roles[$newRoleIndex];
        $contributor->update(['role_id' => $newRole->id]);

        Log::info("Contributor {$contributor->id} role rotated: {$oldRoleId} -> {$newRole->id}");
    }
    event(new RotateContributorRoles($session->id));
}
    
//Collecting.vue
//Collecting.vue
const neighbourIdeas = computed(() => {
 
    const currentRound = parseInt(props.session.collecting_round);
    const currentContributorId = props.personalContributor.id;
    if (currentRound <= 1) {
        console.log('Round 1 or less, returning empty array');
        return [];
    }
    
    const validIdeas = props.ideas.filter(idea => {
        return idea.tag;
    });

    const neighbourIdeas = [];
    for (let i = 1; i < currentRound; i++) {
        const targetRound = currentRound - i;
        const neighbourId = findNeighbourId(props.contributors.map(c => c.id), currentContributorId, i);
        const neighbourIdeasInRound = validIdeas.filter(idea =>
            idea.contributor_id === neighbourId &&
            parseInt(idea.round) === targetRound
        );
        neighbourIdeas.push(...neighbourIdeasInRound);
    }
    return neighbourIdeas;
});

## 5. Testdokumentation
//Jmeter Lasttest für die Anwendung auf einem AWS EC2 T2.Micro

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
1. Session Erstellen
- Zielfrage festlegen
![IMG_0822](https://github.com/user-attachments/assets/cd5decce-c7e5-420e-84e5-af87faab10b1)
- Kreativ-Methode auswählen
![IMG_0823](https://github.com/user-attachments/assets/288c9d0d-aa11-4396-ade2-039422aeb7e1)
- Teilnehmer einladen
![IMG_0826](https://github.com/user-attachments/assets/3546d403-829d-4a32-94d9-efac4d8e5cc8)
3. Session beitreten
![IMG_0824](https://github.com/user-attachments/assets/303ebc74-66f6-4c3b-afca-819f6c8f1f4c)
4. Ideen Sammeln
- Texteingabe, Bildeingabe, KI Eisbrecher
- Zeitlimit & Ideenlimit
- Session pausieren
![IMG_0828](https://github.com/user-attachments/assets/3e34a932-9f08-4f57-8276-562e24f975c5)
  - Session Stats
 ![IMG_0829](https://github.com/user-attachments/assets/641f25e4-c1f3-40e7-bb4e-a59761164022)
5. Ideen Bewerten
- Links oder Rechts Voting
![IMG_0830](https://github.com/user-attachments/assets/361dae5d-136d-478a-a42d-cbae60a4a224)
- Swipe Voting
![IMG_0832](https://github.com/user-attachments/assets/8173108b-54eb-4454-913a-2c6813ddae83)
- Stern Voting
![IMG_0833](https://github.com/user-attachments/assets/e2896a24-849b-4869-a4e2-9666362ec44c)
- Rangliste Voting
![IMG_0834](https://github.com/user-attachments/assets/703e5138-b3d8-4885-a261-67e51ff17eeb)

7. Session Auswerten
![IMG_0837](https://github.com/user-attachments/assets/7245177f-c698-4e4f-9be0-b7be9a4281df)
- PDF verschicken, PDF Download, CSV Download
![IMG_0841](https://github.com/user-attachments/assets/9a014654-deaf-4e6e-b5e2-8e0eb2c234e8)
- Wortcluster
![IMG_0839](https://github.com/user-attachments/assets/e145677e-e782-4022-9dc8-aeddaf9d168b)
![IMG_0838](https://github.com/user-attachments/assets/146e2fd7-f557-419e-9b08-0cf0b574d562)
- Prozess-Darstellung
![IMG_0845](https://github.com/user-attachments/assets/1f298363-8999-4c22-9306-64fec8e52b02)

9. Session Löschen/Bearbeiten
![IMG_0844](https://github.com/user-attachments/assets/e137822d-2c44-401b-8bb0-0ce8239c71ad)

11. Registrieren/Anmelden
![IMG_0843](https://github.com/user-attachments/assets/7fc9a9a8-6cbd-443a-a455-b04cdd6e27c5)
![IMG_0842](https://github.com/user-attachments/assets/e634bd7c-5007-452b-a9e9-6d2c31c693c1)

13. Umfrage Teilnahme
- Datenweitergabe Zustimmung
![IMG_0835](https://github.com/user-attachments/assets/c7e81632-3166-4dd1-ac97-058482e5cd36)
- Email-Verifizierung
![IMG_0836](https://github.com/user-attachments/assets/98cd8e14-3f56-41ba-9bbe-3b8eda07e30f)
- Umfrage durchführen
![IMG_0847](https://github.com/user-attachments/assets/29a7d5ef-d4ef-4b4e-88ec-6a893ad38ea2)

## 7. Projektverlauf
Zeitplan und Meilensteine
- M1 - 01.09.24: Das Projekt ist vorbereitet und geplant 
- M2 - 15.09.24: Ein erster Prototyp (MVP) wurde implementiert
- M3 - 27.09.24: Testbare/Nutzbare Software ist bereitgestellt
- M4 - 12.10.24: Feinschliff ist erledigt
- M5 - 25.10.24: Quantitative und Qualitative Daten wurden gesammelt
- M6 - 30.10.24: Projektarbeit wurde präsentiert

Herausforderungen und Lösungsansätze
1. Laravel 10 Inkompatibilität mit Reverb
- nach etwa einer Woche Coden und einem ersten Prototypen (ohne Websockets) bin ich auf das Problem gestoßen, dass ich leider noch mit einer veralteten Laravel Version (10) gestartet habe und nun auf Laravel 11 upgraden musste um die Kompatibilität mit Reverb zu gewährleisten
2. Fehlerhafte Berechnung der Kosten für die OpenAI API
- den meisten Teil des Projekts über habe ich mit einem API-Token Preis kalkurliert, der um den Faktor 100 höher war, als der eigentliche Preis. Glück gehabt, so konnte ich eigentlich als "zu teuer" eingestufte Features wie die Bildererkennung letztlich doch noch einbauen
3. Unstrukturierter Code bzw. mangelnde Zustandsspeicherung einer "Brainframe Session"
- nach etwa 2/3 der Projektarbeit, nach vielen Wochen des kontinuierlichen hinzufügens neuer Features, ohne besonders auf die Lesbarkeit und Struktur des Codes zu achten, habe ich die "Reißleine" gezogen und den Großteil der Logik "neu" aufgesetzt und strukturiert.
- das habe ich deswegen gemacht, da es meiner Ansicht nach zeit-effizienter und professioneller es "neu" zu machen, als weiter im unübersichtlichen Code nach Fehlern zu suchen
- von da an war das Debugging wesentlich einfacher und schneller, da der Code nun verständlich und weniger verschachtelt ist
- die Hauptänderung betraf die Logik hinter dem Session-Zustand (Phase, Sekunden etc.) . Dieser wurde zunächst nicht bzw nicht vollständig in der Datenbank gespeichert sondern über komplizierte bedingungen vom Frontend errechnet. Das war fürs erste Prototyping schneller, aber defintiv nicht erweiterbar und wartbar
4. Weitere Schwierigkeiten
- Testen der Bildverarbeitung, weil die OpenAI API bei der lokalen Entwicklung natürlich nicht auf die auf dem Server liegenden Bilder zugreifen kann
- Ressourcen-sparendes Daten-Laden (ich habe es jetzt weitgehend über Websockets gelöst, anstatt ständig API Aufrufe ans Laravel Backend zu senden)
- Anwendungsdesign (Visuell)

## 8. Ausblick
Mögliche zukünftige Erweiterungen
- User-freundlicheres bzw. klassischeres App Design
- Kommentare von Nutzern / Chat oder Reaktionen
- andere Methodiken (Design Thinking)
- Custom-Methoden mit einstellbaren Phasen / Voting Methoden und Timern/Limits

## 9. Literaturverzeichnis
- [Vue3 Doku](https://vuejs.org/guide/introduction.html)
- [Laravel Forge Doku](https://forge.laravel.com/docs/introduction.html)
- [Laravel Reverb Doku](https://laravel.com/docs/11.x/reverb)
- [LDRS Animations](https://uiball.com/ldrs/)
- [Icons](https://uxwing.com)
- [Vue-QR-Code](https://gruhn.github.io/vue-qrcode-reader)
- [DOM Pdf](https://github.com/barryvdh/laravel-dompdf)
