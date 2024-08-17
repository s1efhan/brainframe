<template>
  <div v-if="sessionId" class="headline">
    <h1 class="headline__session-pin">
      Session-PIN
      <p @click="copyToClipboard(sessionId)">
        {{ sessionId }}
        <CopyIcon />
      </p>
    </h1>
    <div class="headline__brainframe-icon">
      <BrainFrameIcon />
    </div>
  </div>
  <router-view :userId="userId"></router-view>
  <Footer />
  <Menu @resetSessionId="handleSessionIdUpdate"></Menu>
</template>

<script setup>
import { sessionId } from './js/eventBus.js'
import { onMounted, ref } from 'vue';
import Menu from './components/Menu.vue';
import Footer from './components/Footer.vue';
import CopyIcon from './components/icons/CopyIcon.vue';
import BrainFrameIcon from './components/icons/BrainFrameIcon.vue'
const route = useRoute();
import { useRoute } from 'vue-router';
const userId = ref('');
function handleSessionIdUpdate(newSessionId) {
  sessionId.value = newSessionId;
}
// Funktion zur Generierung und Setzen der User-ID
function initializeUserId() {
  userId.value = localStorage.getItem('user_id');
  if (!userId.value) {
    const array = new Uint32Array(1); // Erzeuge ein Array mit einem 32-Bit Integer
    window.crypto.getRandomValues(array); // Fülle das Array mit Zufallswerten
    userId.value = array[0];
    localStorage.setItem('user_id', userId.value);
  }
}

const copyToClipboard = (copyText) => {
  navigator.clipboard.writeText(copyText);
};


function getUserData() {
  if (userId.value) {
    userId.value = localStorage.getItem('user_id');
  }
}
const msg = ref([]);
const contributorSent = ref([]);
// Hier müssen Sie den Code zum Empfangen der Nachricht über WebSockets einfügen
Echo.channel('messages')
  .listen('MessageSent', (e) => {
    msg.value.push(e);
  })
  .listen('ContributorJoin', (e) => {
    console.log('ContributorJoin Event empfangen:', e);
    contributorSent.value.push(e);
  });

// Funktion zum Senden der User-ID
function updateUserId() {
  axios.post('/api/user', { user_id: localStorage.getItem('user_id') })
    .then(response => {
    })
    .catch(error => {
      console.error('Error sending user ID to server:', error);
    });
}

onMounted(() => {
  if (Number.isInteger(parseInt(route.params.id))) {
    sessionId.value = route.params.id;
  }
  initializeUserId();
  updateUserId();
  getUserData();
});


</script>
