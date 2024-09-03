<template>
  <div v-if="isLoading" class="isLoading">
    <l-quantum size="45" speed="1.75" color="white"></l-quantum>
  </div>
  <div v-else>
    <table class="session-data">
      <thead>
        <tr>
          <th>Ziel</th>
          <th>Teilnehmerzahl</th>
          <th>Ideen</th>
          <th>Dauer</th>
          <th>Datum</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{ sessionDetails.target }}</td>
          <td>{{ sessionDetails.contributors_count }}</td>
          <td>{{ sessionDetails.ideas_count }}</td>
          <td>{{ sessionDetails.duration }} Minuten</td>
          <td>{{ formatDate(sessionDetails.date) }}</td>
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
            <th></th>
            <th>Punkte</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(idea, index) in [...sessionDetails.top_ideas].reverse()" :key="idea.id">
            <td>{{ index + 1 }}</td>
            <td>{{ idea.idea_title }}</td>
            <td v-html="idea.idea_description"></td>
            <td>
              <component :is="getIconComponent(idea.contributor_icon)" />
            </td>
            <td>#{{ idea.tag }}</td>
            <td>{{ parseFloat(idea.avg_vote_value).toFixed(1) }} /5.0</td>
          </tr>
        </tbody>
      </table>
    </div>

    <table class="session-data">
      <thead>
        <tr>
          <th>Methode</th>
          <th>Session ID</th>
          <th>Link</th>
          <th>Verbrauchte Token</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{ sessionDetails.method }}</td>
          <td>{{ sessionDetails.session_id }}</td>
          <td>
            <a :href="'https://stefan-theissen.de/brainframe/' + sessionDetails.session_id">
              stefan-theissen.de/brainframe/{{ sessionDetails.session_id }}
            </a>
          </td>
          <td>
            {{ sessionDetails.input_token }} (input) {{ sessionDetails.output_token }} (output) =>
            {{ calculateCost }} ct
          </td>
        </tr>
      </tbody>
    </table>

    <div class="collecting-process">
      <h2>{{ sessionDetails.method }}</h2>
      <div class="timeline">
        <div v-for="(groupedIdeas, round) in groupedIdeasByRound" :key="round" class="tag">
          <div class="round">{{ round }}</div>
          <ul>
            <li v-for="idea in groupedIdeas" :key="idea.id">
              <component :is="getIconComponent(idea.contributor_icon)" />{{ idea.contributor_icon }}
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="word-cluster" v-if="sessionDetails.word_cloud_data.content">
      <h2>Wort-Cluster</h2>
      <ul>
        <li :class="'count-' + item.count" v-for="item in sessionDetails.word_cloud_data.content" :key="item.word">
          {{ item.word }}
        </li>
      </ul>
    </div>


    <div class="tags-list">
      <h2>Tags</h2>
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
    <div class="next-steps" v-if="sessionDetails.next_steps.content">
      <h3>Nächste Schritte und Empfehlungen</h3>
      <p v-html="sessionDetails.next_steps.content"></p>
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
import 'ldrs/quantum';
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
      isLoading.value=false;
      emit('switchPhase', 'closingPhase');
    });
};

const downloadPDF = () => {
  console.log('downloadPDF: ', sessionDetails.value.target + '.pdf')
  axios.get(`/api/${props.sessionId}/pdf`, { responseType: 'blob' })
    .then(response => {
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', sessionDetails.value.target + ".pdf");
      document.body.appendChild(link);
      link.click();
      console.log('download erfolgreich')
    })
    .catch(error => {
      console.error('Error Downloading PDF', error);
    });
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
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('de-DE', options);
};

onMounted(() => {
  personalContributor.value = props.personalContributor
    getSessionDetails();
});
</script>