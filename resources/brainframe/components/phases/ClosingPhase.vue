<template>
    <div v-if="sessionDetails">
      <table class="session-data">
        <thead>
          <tr>
            <th>Ziel</th>
            <th>Teilnehmerzahl</th>
            <th>Gesammelte Ideen</th>
            <th>Dauer</th>
            <th>Datum</th>
            <th>Ort</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ sessionDetails.target }}</td>
            <td>{{ sessionDetails.contributorsCount }}</td>
            <td>{{ sessionDetails.ideasCount }}</td>
            <td>{{ sessionDetails.duration }} Minuten</td>
            <td>{{ sessionDetails.date }}</td>
            <td>{{ sessionDetails.place }}</td>
          </tr>
        </tbody>
      </table>
  
      <div class="top-ideas">
        <h2>Top Ideen</h2>
        <table>
          <thead>
            <tr>
              <th>Platzierung</th>
              <th>Idee</th>
              <th>Beschreibung</th>
              <th>Ideengeber</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(idea, index) in sessionDetails.topIdeas" :key="idea.id">
              <td>{{ index + 1 }}</td>
              <td>{{ idea.title }}</td>
              <td>{{ idea.description }}</td>
              <td> {{ idea.contributor_icon }}</td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <div class="session-data">
        <table>
          <thead>
            <tr>
              <th>Methode</th>
              <th>Session PIN</th>
              <th>Link</th>
              <th>Verbrauchte Token</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{ sessionDetails.method }}</td>
              <td>{{ sessionDetails.id }}</td>
              <td>
                <a :href="'https://stefan-theissen.de/brainframe/' + sessionDetails.id">
                  stefan-theissen.de/brainframe/{{ sessionDetails.id }}
                </a>
              </td>
              <td>
                {{ sessionDetails.inputToken }} (input) {{ sessionDetails.outputToken }} (output) =>
                {{ calculateCost }} ct
              </td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <div class="collecting-process">
    <h2>{{ sessionDetails.method }}</h2>
    <div class="timeline">
      <div v-for="(groupedIdeas, round) in groupedIdeasByRound" :key="round" class="tag">
        <div class="round">{{ round }}</div>
        <ul>
          <li v-for="idea in groupedIdeas" :key="idea.id">{{ idea.contributor_icon }}</li>
        </ul>
      </div>
    </div>
  </div>
      <div class="word-cluster">
    <h2>Wort-Cluster</h2>
    <ul>
      <li v-for="item in parsedWordCloudData" :key="item.word">
        {{ item.word }}: {{ item.word_count }}
      </li>
    </ul>
  </div>
      <div class="tags-list">
        <h2>Tags</h2>
        <ul>
          <li v-for="tag in sessionDetails.tagList" :key="tag.tag">
            {{ tag.tag }}: {{ tag.count }}
          </li>
        </ul>
      </div>
  
      <div class="next-steps">
        <h3>Nächste Schritte und Empfehlungen</h3>
        <p>{{ sessionDetails.nextSteps }}</p>
      </div>
  
      <button @click="sendSummary">Zusammenfassung senden</button>
      <button @click="downloadPDF">PDF herunterladen</button>
  
      <div class="start-new-session">
        <router-link to="/brainframe/create">Neue Sitzung starten</router-link>
        <router-link to="/brainframe/register">Registrieren, um Sitzungen zu speichern</router-link>
      </div>
    </div>
  </template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';

const route = useRoute();
const sessionDetails = ref(null);
const feedbackResults = ref(null);
const surveySubmitted = ref(false);

const props = defineProps({
    sessionHostId: {
        type: [String, Number],
        required: true
    },
    sessionId: {
        type: [String, Number],
        required: true
    }
});
const parsedWordCloudData = computed(() => {
  if (!sessionDetails.value || !sessionDetails.value.wordCloudData || !sessionDetails.value.wordCloudData.content) {
    return [];
  }
  try {
    const content = sessionDetails.value.wordCloudData.content
      .replace(/^```json\n|\n```$/g, '') // Entfernt ```json am Anfang und ``` am Ende
      .trim();
    return JSON.parse(content);
  } catch (error) {
    console.error('Error parsing wordCloudData', error);
    return [];
  }
});
const groupedIdeasByRound = computed(() => {
  if (!sessionDetails.value || !sessionDetails.value.ideas) return {};

  return sessionDetails.value.ideas.reduce((acc, idea) => {
    if (idea.round != null) {  // Prüfe, ob `round` nicht `null` ist
      if (!acc[idea.round]) {
        acc[idea.round] = [];
      }
      acc[idea.round].push(idea);
    }
    return acc;
  }, {});
});

const calculateCost = computed(() => {
  if (!sessionDetails.value) return 0;
  return ((sessionDetails.value.inputToken * 0.000015 + sessionDetails.value.outputToken * 0.000060) * 100).toFixed(2);
});

const getSessionDetails = () => {
    console.log('getSessionDetails', props.sessionId)
    axios.get(`/api/${props.sessionId}/details`)
    .then(response => {
      sessionDetails.value = response.data;
    })
    .catch(error => {
      console.error('Error fetching session details', error);
    });
};

const downloadPDF = () => {
  const sessionId = route.params.sessionId;
  axios.get(`/api/${sessionId}/pdf`, { responseType: 'blob' })
    .then(response => {
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = `session_summary_${sessionId}.pdf`;
      link.click();
    })
    .catch(error => {
      console.error('Error Downloading PDF', error);
    });
};

const sendSummary = () => {
  const sessionId = route.params.sessionId;
  axios.post(`/api/${sessionId}/summary`)
    .then(() => {
      alert('Zusammenfassung erfolgreich an alle Teilnehmer gesendet!');
    })
    .catch(error => {
      console.error('Error sending Summary to Contributors', error);
      alert('Fehler beim Senden der Zusammenfassung. Bitte versuchen Sie es erneut.');
    });
};

onMounted(() => {
  getSessionDetails();
});
</script>