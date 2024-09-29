<template>
  <main v-if="authToken">
    <div class="headline__login">
      <h1>Profile {{ userData.name }}</h1>
    </div>
    <div class="profile-data__containers">
      <ul>
        <li>Anzahl an Sessions: {{ userData.contributor_count }}</li>
        <li>Beigetragende Ideen: {{ userData.idea_count }}</li>
        <li>Letzte Session: {{ userData.last_activity }}</li>
      </ul>
    </div>
    <Logout @logout="logout" :userId="userId" />
  </main>
  <main v-else>
    <Login @login="login" v-if="userId" :userId="userId" />
  </main>
</template>

<script setup>
import { ref, onMounted, watch} from 'vue';
import axios from 'axios';
import Login from '../components/Login.vue';
import Logout from '../components/Logout.vue';
const emit = defineEmits(['logout', 'login']);
const props = defineProps({
  userId: {
    type: Number,
    required: true
  },
  authToken: {
    type: [String, null],
    required: false
  }
});
const authToken = ref(props.authToken);
const userData = ref({});
const userId = ref(props.userId);
const logout = () => {
  authToken.value = null;
  emit('logout');
}
const login = (token) => {
  emit('login');
  authToken.value = token;
  getUser();
}

watch(() => props.userId, (newVal) => {
  userId.value = newVal;
  getUser();
});
const getUser = () => {
  axios.get(`/api/user/${userId.value}/stats`, {
    headers: { Authorization: `Bearer ${authToken.value}` }
  })
    .then(response => {
      userData.value = response.data;
    })
    .catch(error => {
      console.error('Error fetching User Data', error);
    });
}
onMounted(() => {
  if (authToken.value) {
    getUser();
  }
});

</script>