<template>
<header v-if="sessionId && route.path !== '/brainframe/join'" class="headline">
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
  </header>
  <router-view 
    v-if="route.name === 'Session'"
    @updateSessionId="handleSessionIdUpdate"
    :userId="userId"
  ></router-view>
  <router-view 
    v-else
    :userId="userId"
  ></router-view>
  <Footer/>
  <Menu :sessionId="sessionId" @resetSessionId="handleSessionIdUpdate"></Menu>
</template>

<script setup>
import { sessionId } from './js/eventBus.js'
import { onMounted, ref } from 'vue';
import Menu from './components/Menu.vue';
import Footer from './components/Footer.vue';
import CopyIcon from './components/icons/CopyIcon.vue';
import BrainFrameIcon from './components/icons/BrainFrameIcon.vue'
import { useRoute } from 'vue-router';

const route = useRoute();
const userId = ref(0);

function handleSessionIdUpdate(newSessionId) {
  sessionId.value = newSessionId;
}

function initializeUserId() {
  userId.value = Number(localStorage.getItem('user_id'));
  if (!userId.value) {
    const array = new Uint32Array(1); // Erzeuge ein Array mit einem 32-Bit Integer
    window.crypto.getRandomValues(array); // Fülle das Array mit Zufallswerten
    userId.value = Number(array[0]);
    localStorage.setItem('user_id', userId.value.toString());
  }
}

const copyToClipboard = (copyText) => {
  navigator.clipboard.writeText(copyText);
};

function getUserData() {
  
  if (userId.value) {
    userId.value = Number(localStorage.getItem('user_id'));
  }
}

// Funktion zum Senden der User-ID
function updateUserId() {
  axios.post('/api/user', { user_id: Number(userId.value) })
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
