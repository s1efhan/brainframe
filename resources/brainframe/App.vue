<template>
  <Menu></Menu>
  <!-- 
  <div v-if="msg.length > 0">
     <div v-for="message in msg" :key="message.id">
      <p>{{ message.name }}: {{ message.text }}</p>
    </div>
  </div> 
  
  ////
  // NUR ZUM TESTEN!
  ////
  
  <div v-if="contributorSent.length > 0">
    <div v-for="contributor in contributorSent" :key="contributor.id">
      <p>Beigetreten - SessionId: {{ contributor.session }} - ContributorId: {{ contributor.user }} - Contributor-Role:
        {{ contributor.role }}</p>
    </div>
  </div>
  <div v-else>
    <p>Keine Nachrichten empfangen.</p>
  </div>
  -->
  <router-view :userId="userId"></router-view>
  <Footer></Footer>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Menu from './components/Menu.vue';
import Footer from './components/Footer.vue';
import axios from 'axios';
const userId = ref('');
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
  initializeUserId(); // Initialisiere die User-ID beim Mounten der Komponente
  updateUserId(); //senden der user id  aus der local storage an die Datenbank über POST /user
  getUserData();
});


</script>
