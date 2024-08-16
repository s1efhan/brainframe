<template>
  <router-view :userId="userId"></router-view>
  <Menu></Menu>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Menu from './components/Menu.vue';
import axios from 'axios';
const route = useRoute();
import { useRoute } from 'vue-router';
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
const sessionId = (null);
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
  if(route.params.id)
{sessionId.value = route.params.id;}
  initializeUserId(); // Initialisiere die User-ID beim Mounten der Komponente
  updateUserId(); //senden der user id  aus der local storage an die Datenbank über POST /user
  getUserData();
});


</script>
