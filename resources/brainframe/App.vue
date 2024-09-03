<template>
<header v-if="sessionId && (route.path === '/brainframe/create' || route.name === 'session')" class="headline"><h1 class="headline__session-pin">
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
    :userId="userId" :authToken="authToken"
  ></router-view>
  <router-view 
    v-if="route.name === 'Profile'"
    @logout="handleLogout" @login="handleLogin"
    :userId="userId" :authToken="authToken"
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
const authToken = ref(null);

const initializeUserId = () => {
    const array = new Uint32Array(1);
    window.crypto.getRandomValues(array);
    userId.value = Number(array[0]);
    localStorage.setItem('user_id', userId.value.toString());
    axios.post('/api/user', { user_id: Number(userId.value) })
    .then(response => {

    })
    .catch(error => {
      console.error('Error sending user ID to server:', error);
    });
}

const getUserData = () => {
    userId.value = Number(localStorage.getItem('user_id'));
    authToken.value = localStorage.getItem('authToken');
}
const handleLogout = () => {
  getUserData() 
  if(!userId.value){
    initializeUserId();
  }
}
const handleLogin = () => {
  getUserData();
}
const handleSessionIdUpdate = (newSessionId) => {
  sessionId.value = newSessionId;
}
const copyToClipboard = (copyText) => {
  navigator.clipboard.writeText(copyText);
};


onMounted(() => {
  if (Number.isInteger(parseInt(route.params.id))) {
    sessionId.value = route.params.id;
  }
  getUserData() 
  if(!userId.value){
    initializeUserId();
  }
});


</script>
