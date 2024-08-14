<template>
    <h3>ClosingPhase</h3>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
const props = defineProps({
    sessionHostId: {
    type: [String, Number],
    required: true
  }
});
const sessionHostId = ref(null);
onMounted(()=>{
    sessionHostId.value = props.sessionHostId;
    
});
</script>

<!-- 
- Email mit Zusammenfassung PDF
- PDF Download möglich mit Zusammenfassung
- Interaktive Online-Ergebnisseite mit Zugriff für alle Teilnehmer

Inhalte des PDFs:

- Namen der Teilnehmer
- Datum
- Dauer
- Zielfrage der Ideen-Session
- Top-Ideen (Bewertungen, relevante Kritik Kommentaren)
- Darstellung des Ideen Auswahl Prozesses (Viele Kreise mit Tags -> weniger Kreise mit Ideen Titeln)
- verwendete Methoden (Zeitstrahl)
- Anzahl an Ideen, Anzahl an einzigartigen Ideen, Anzahl an Tags/Kategorien
- Wortcluster
- Nächste Schritte und Empfehlungen der KI
- KI generierte Skizze für die idee

- Teilnehmer Bewertung der session

- QR-Code und Link zu Website für neue Ideen-Session

-->