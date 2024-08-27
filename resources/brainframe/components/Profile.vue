<template>
  <main v-if="loggedIn">
    <div class="headline__login">
      <h1> Profile {{ userData.name }}</h1>
    </div>
    <div class="profile-data__containers"> <ul>
        <li>Anzahl an Sessions: {{ userData.contributor_count }}</li>
        <li>Beigetragende Ideen: {{ userData.idea_count }}</li>
        <li>Letzte Session: {{ userData.last_activity }}</li>
        <li> - wird noch gelöscht...  UserId: {{ userId }} -</li>
      </ul>
    </div>
  </main>
  <main v-else>
    <Login :userId="userId" @updateUserId="handleUserIdUpdate" />
  </main>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Login from '../components/Login.vue';

const userId = ref(0);
const userData = ref({});
const loggedIn = ref(false);

const getUser = () => {
  axios.get(`/api/user/${userId.value}`)
    .then(response => {
      userData.value = response.data;
      console.log(userData.value);
    })
    .catch(error => {
      console.error('Error fetching User Data', error);
    });
}

const handleUserIdUpdate = (newUserId) => {
  userId.value = newUserId;
  loggedIn.value = true;
  getUser();
}
</script>
