<template>
  <div v-if="isLoading" class="isLoading__container">
    <div class="isLoading">
      <l-dot-pulse size="70" speed="1" color="#33d2ca"></l-dot-pulse>
    </div>
  </div>
  <div v-else class="collecting-pdf ">
    <table  class="session-data" id="first-table">
      <thead>
        <tr>
          <th><TargetIcon/></th>
          <th><ProfileIcon/></th>
          <th><LightbulbIcon/></th>
          <th><CalendarIcon/></th>
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
          <th><PinIcon/></th>
          <th><AiStarsIcon/></th>
          <th><SandclockIcon/></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="center">{{ sessionDetails.method }}</td>
          <td class="center"><a :href="'https://stefan-theissen.de/brainframe/' + sessionDetails.session_id">
              {{ sessionDetails.session_id }}
            </a>
          </td>
          <td class="token">
            {{ sessionDetails.input_token }} (in) <br>{{ sessionDetails.output_token }} (out) <br> =>
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
      <table class="fade-in-section" v-if="sessionDetails && sessionDetails.top_ideas">
        <thead class="fade-in-section">
          <tr  class="fade-in-section">
            <th><PodiumIcon/></th>
            <th><LightbulbIcon/></th>
            <th>Beschreibung</th>
            <th><ProfileIcon/></th>
            <th><StarIcon/></th>
          </tr>
        </thead>
        <tbody class="fade-in-section">
          <tr  class="fade-in-section" v-for="(idea, index) in [...sessionDetails.top_ideas].sort((a, b) => b.avg_vote_value - a.avg_vote_value)"
            :key="idea.id">
            <td class="center fade-in-section">{{ index + 1 }}</td>
            <td  class="fade-in-section">{{ idea.idea_title }}</td>
            <td  class="fade-in-section"v-html="idea.idea_description"></td>
            <td class="center fade-in-section">
              <component :is="getIconComponent(idea.contributor_icon)" />
            </td>
            <!-- <td class="center">#{{ idea.tag }}</td>-->
            <td class="center fade-in-section">{{ parseFloat(idea.avg_vote_value).toFixed(1) }} /5.0</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="collecting-process">
      <h2 class="fade-in-section">{{ sessionDetails.method }}</h2>
      <div class="timeline  fade-in-section">
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
      <h2 class="fade-in-section">Wort-Cluster</h2>
      <ul>
        <li :class="'count-' + item.count + ' fade-in-section'" v-for="item in sessionDetails.word_cloud_data" :key="item.word">
          {{ item.word }}
        </li>
      </ul>
    </div>


    <div class="tags-list">
      <h2 class="fade-in-section">#Tags</h2>
      <ul>
        <li :class="'count- ' + tag.count + ' fade-in-section'" v-for="tag in sessionDetails.tag_list" :key="tag.tag">
          #{{ tag.tag }}
        </li>
      </ul>
    </div>
    <div class="summary__buttons">
      <button class="secondary" @click="toggleshowSendContainer">Zusammenfassung senden</button>
      <button class="accent" @click="downloadPDF">PDF herunterladen</button>
    </div>
    <div v-if="showSendContainer" class="send__container">
      <div class="email-list">
        <div v-for="email in validatedEmails" :key="email" class="validated-email">
          {{ email }} <span @click="removeEmail(email)" class="remove-email">x</span>
        </div>
      </div>
      <div class="email-input__container">
        <input type="email" v-model="newEmail" @keyup.enter="addEmail" @blur="addEmail"
          placeholder="E-Mail-Adresse eingeben">
        <button class="secondary" @click="sendSummary">PDF Senden</button>
      </div>
    </div>
    <div v-if="errorMsg" class="error">{{ errorMsg }}</div>
    <div class="next-steps" v-if="sessionDetails.next_steps">
      <h2 class="fade-in-section">Nächste Schritte und Empfehlungen</h2>
      <p class="fade-in-section"v-html="sessionDetails.next_steps"></p>
    </div>

    <div class="newSession__buttons fade-in-section">
      <button class="accent" @click="router.push('/brainframe/create')">Neue Session Starten</button>
      <button class="primary" @click="router.push('/brainframe/profile')">Account anlegen & Session speichern!</button>
    </div>
  </div>

</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import CalendarIcon from '../icons/CalendarIcon.vue';
import PinIcon from '../icons/PinIcon.vue';
import PodiumIcon from '../icons/PodiumIcon.vue';
import SandclockIcon from '../icons/SandclockIcon.vue';
import TargetIcon from '../icons/TargetIcon.vue';
import ProfileIcon from '../icons/ProfileIcon.vue';
import StarIcon from '../icons/StarIcon.vue';
import AiStarsIcon from '../icons/AiStarsIcon.vue';
import LightbulbIcon from '../icons/LightbulbIcon.vue';
import { useRoute } from 'vue-router';
import {dotPulse} from "ldrs";
dotPulse.register();
const route = useRoute();
const sessionDetails = ref(null);
const showSendContainer = ref(false);
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
const newEmail = ref('');
const validatedEmails = ref([]);
const contributorEmailAddresses = ref(['']);
const addEmail = () => {
  const email = newEmail.value.trim();
  if (isValidEmail(email) && !validatedEmails.value.includes(email)) {
    validatedEmails.value.push(email);
    newEmail.value = '';
  }
};

const validateEmail = (index, event) => {
  const email = contributorEmailAddresses.value[index];
  if (isValidEmail(email) && (event.key === 'Enter' || event.type === 'blur')) {
    if (!validatedEmails.value.includes(email)) {
      validatedEmails.value.push(email);
    }
    contributorEmailAddresses.value[index] = '';
    if (index === contributorEmailAddresses.value.length - 1) {
      contributorEmailAddresses.value.push('');
    }
  }
};

const personalContributor = ref(null);
import IconComponents from '../IconComponents.vue';
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

const toggleshowSendContainer = () => {
  showSendContainer.value = !showSendContainer.value;
  errorMsg.value = null;
  if (showSendContainer.value && sessionDetails.value && sessionDetails.value.contributor_emails) {
    sessionDetails.value.contributor_emails.forEach(email => {
      if (isValidEmail(email) && !validatedEmails.value.includes(email)) {
        validatedEmails.value.push(email);
      }
    });
  }
};

const removeEmail = (email) => {
  validatedEmails.value = validatedEmails.value.filter(e => e !== email);
};

const errorMsg = ref(null);
const sendSummary = () => {
  axios.post(`/api/session/summary/send`, {
    contributor_emails: validatedEmails.value,
    session_id: props.sessionId
  })
    .then(response => {
      showSendContainer.value = !showSendContainer.value;
      errorMsg.value = "Zusammenfassung Erfolgreich versendet"
    }).catch(error => {
      console.error('Error sending the summary', error);
    });
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
  return ((sessionDetails.value.input_token * 0.00000015 + sessionDetails.value.output_token * 0.00000060) * 100).toFixed(2);
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
  const format = 'pdf'; // Ändern Sie dies zu 'pdf' für den finalen Download
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

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'numeric', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('de-DE', options);
};

const checkVisibility = () => {
  const sections = document.querySelectorAll('.fade-in-section');
  sections.forEach((section) => {
    if (isElementInViewport(section)) {
      section.classList.add('is-visible');
    }
  });
};

const isElementInViewport = (el) => {
  const rect = el.getBoundingClientRect();
  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
};

onMounted(() => {
  personalContributor.value = props.personalContributor;
  getSessionDetails();
  window.addEventListener('scroll', checkVisibility);
  checkVisibility(); // Initial check
});
</script>
<style>
.fade-in-section {
  opacity: 0;
  transform: translateY(20px);
  visibility: hidden;
  transition: opacity 0.6s ease-out, transform 0.6s ease-out, visibility 0.6s ease-out;
  will-change: opacity, transform, visibility;
}

.is-visible {
  opacity: 1;
  transform: none;
  visibility: visible;
}
</style>