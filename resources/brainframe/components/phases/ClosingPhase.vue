<template>
  <div v-if="isLoading" class="isLoading__container">
    <div class="isLoading">
      <l-dot-pulse size="70" speed="1" color="#33d2ca"></l-dot-pulse>
    </div>
  </div>
  <div v-else class="collecting-pdf">
    <table class="session-data">
      <thead>
        <tr>
          <th>Ziel</th>
          <th>Teilnehmerzahl</th>
          <th>Ideen</th>
          <th>Datum</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{ sessionDetails.target }}</td>
          <td class="center">{{ sessionDetails.contributors_count }}</td>
          <td class="center">{{ sessionDetails.ideas_count }}</td>
          <td class="center">{{ formatDate(sessionDetails.date) }}</td>
        </tr>
      </tbody>
    </table>

    <table class="session-data">
      <thead>
        <tr>
          <th>Methode</th>
          <th>Session ID</th>
          <th>Token</th>
          <th>Dauer</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="center">{{ sessionDetails.method }}</td>
          <td class="center"><a :href="'https://stefan-theissen.de/brainframe/' + sessionDetails.session_id">
            {{ sessionDetails.session_id }}
            </a>
          </td>
          <td >
            {{ sessionDetails.input_token }} (input) {{ sessionDetails.output_token }} (output) =>
            {{ calculateCost }} ct
          </td>
          <td class="center">
            {{ Math.floor(sessionDetails.duration / 60) }}h
            {{ Math.round(sessionDetails.duration % 60) }}min
          </td>
        </tr>
      </tbody>
    </table>
    <div class="top-ideas">
      <h2>Top Ideen</h2>
      <table v-if="sessionDetails && sessionDetails.top_ideas">
        <thead>
          <tr>
            <th>Platz</th>
            <th>Idee</th>
            <th>Beschreibung</th>
            <th></th>
            <th>Punkte</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(idea, index) in [...sessionDetails.top_ideas].reverse()" :key="idea.id">
            <td class="center">{{ index + 1 }}</td>
            <td>{{ idea.idea_title }}</td>
            <td v-html="idea.idea_description"></td>
            <td class="center">
              <component :is="getIconComponent(idea.contributor_icon)" />
            </td>
           <!-- <td class="center">#{{ idea.tag }}</td>-->
            <td class="center">{{ parseFloat(idea.avg_vote_value).toFixed(1) }} /5.0</td>
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
            <li v-for="idea in groupedIdeas" :key="idea.id">
              <component :is="getIconComponent(idea.contributor_icon)" />
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="word-cluster" v-if="sessionDetails.word_cloud_data">
      <h2>Wort-Cluster</h2>
      <ul>
        <li :class="'count-' + item.count" v-for="item in sessionDetails.word_cloud_data" :key="item.word">
          {{ item.word }}
        </li>
      </ul>
    </div>


    <div class="tags-list">
      <h2>#Tags</h2>
      <ul>
        <li :class="'count-' + tag.count" v-for="tag in sessionDetails.tag_list" :key="tag.tag">
          #{{ tag.tag }}
        </li>
      </ul>
    </div>
    <div class="summary__buttons">
      <button class="secondary" @click="sendSummary">Zusammenfassung senden</button>
      <button class="accent" @click="downloadPDF">PDF herunterladen</button>
    </div>
    <div class="next-steps" v-if="sessionDetails.next_steps">
      <h2>Nächste Schritte und Empfehlungen</h2>
      <p v-html="sessionDetails.next_steps"></p>
    </div>
    <div class="newSession__buttons">
      <button class="accent" @click="router.push('/brainframe/create')">Neue Session Starten</button>
      <button class="primary" @click="router.push('/brainframe/profile')">Account anlegen & Session speichern!</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import 'ldrs/dotPulse';
const route = useRoute();
const sessionDetails = ref(null);

const props = defineProps({
  sessionHostId: {
    type: [String, Number],
    required: true
  },
  personalContributor: {
    type: Object,
    required: true
  },
  sessionId: {
    type: [String, Number],
    required: true
  }
});
const personalContributor = ref(null);
import IconComponents from '../IconComponents.vue';
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const groupedIdeasByRound = computed(() => {
  if (!sessionDetails.value || !sessionDetails.value.ideas) return {};

  return sessionDetails.value.ideas.reduce((acc, idea) => {
    if (idea.round != null) {
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
  return ((sessionDetails.value.input_token * 0.000015 + sessionDetails.value.output_token * 0.000060) * 100).toFixed(2);
});
const emit = defineEmits(['switchPhase']);
const isLoading = ref(true);
const getSessionDetails = () => {
  isLoading.value = true;
  console.log('getSessionDetails', props.sessionId);

  axios.get(`/api/${props.sessionId}/details`)
    .then(response => {
      sessionDetails.value = response.data;
    })
    .catch(error => {
      console.error('Error fetching session details', error);
    })
    .finally(() => {
      isLoading.value = false;
      console.log('getSessionDetails', sessionDetails.value);
      emit('switchPhase', 'closingPhase');
    });
};

const downloadPDF = () => {
  const format = 'html'; // Ändern Sie dies zu 'pdf' für den finalen Download
  const url = `/api/${props.sessionId}/pdf?format=${format}`;
  
  if (format === 'html') {
    // Öffnen Sie die HTML-Vorschau in einem neuen Tab
    window.open(url, '_blank');
  } else {
    // Behalten Sie die bestehende PDF-Download-Logik bei
    axios.get(url, { responseType: 'blob' })
      .then(response => {
        const blob = new Blob([response.data]);
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = `${sessionDetails.value.target}.pdf`;
        link.click();
      })
      .catch(error => {
        console.error('Error Downloading PDF', error);
      });
  }
};

const sendSummary = () => {
  axios.post(`/api/${props.sessionId}/summary`)
    .then(() => {
      alert('Zusammenfassung erfolgreich an alle Teilnehmer gesendet!');
    })
    .catch(error => {
      console.error('Error sending Summary to Contributors', error);
      alert('Fehler beim Senden der Zusammenfassung. Bitte versuchen Sie es erneut.');
    });
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'numeric', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('de-DE', options);
};

onMounted(() => {
  personalContributor.value = props.personalContributor
  getSessionDetails();
});
</script>