<template>
  <div class="selectRole__head">
    <h2>Wähle dein Pseudonym</h2>
    <div class="info__container">
      <div @click="showInfo = !showInfo" class="join__info">
        <p>i</p>
      </div>
    </div>
  </div>
  <div v-if="showInfo" class="info__text__container">
    <div class="info__text">
      <h3>Rollenwahl:</h3>
      <ul v-if="session.method.name =='6 Thinking Hats'">
        <li>Die Wahl deiner "Rolle" hat <strong>großen Einfluss</strong> auf den weiteren Verlauf der Ideen-Sammel
          Session. <br>Sie bestimmt deine <strong>Perspektive beim Sammeln und Bewerten </strong>und ermöglicht es
          anonym über Ideen zu sprechen.</li>
        <ul>
          <li><strong>Roter Hut:</strong> Emotionale Sichtweise, Gefühle und Intuition.</li>
          <li><strong>Schwarzer Hut:</strong> Kritische Sichtweise, Risiken und Probleme.</li>
          <li><strong>Gelber Hut:</strong> Positive Sichtweise, Vorteile und Chancen.</li>
          <li><strong>Weißer Hut:</strong> Faktenbasierte Sichtweise, objektive Informationen.</li>
          <li><strong>Grüner Hut:</strong> Kreative Sichtweise, neue Ideen und Alternativen.</li>
          <li><strong>Blauer Hut:</strong> Organisatorische Sichtweise, Prozesskontrolle und Zusammenfassung.</li>
        </ul>
      </ul>
      <ul v-else>
        <li>Die Wahl deiner "Rolle" hat <strong>keinen Einfluss</strong> auf den weiteren Verlauf der Ideen-Sammel
          Session. <br>Sie ist rein <strong>ästhetischer Natur </strong>und dient dazu vereinfacht und anonym über Ideen
          zu sprechen.</li>
      </ul>
    </div>
  </div>
  <form class="selectRole" v-if="showSelectRole" @submit.prevent="addContributor">
    <div class="role-select__container">
      <select id="roleSelect" v-model="selectedRole.id" @change="updateSelectedRole">
        <option v-for="role in roles" :key="role.id" :value="role.id">
          {{ role.name }}
        </option>
      </select>
      <div v-if="selectedRole.icon" class="role-icon">
        <component :is="getIconComponent(selectedRole.icon)" /> {{ selectedRole.name }}
      </div>
    </div>
    <button class="primary" type="submit">Rolle Wählen</button>
  </form>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import IconComponents from '../components/IconComponents.vue';

const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  },
  session: {
    type: [Object, null],
    required: true
  }
});

const getRoles = () => {
  axios.get(`/api/roles/${session.value.id}`)
    .then(response => {
      roles.value = response.data;
      if (roles.value.length > 0) {
        const randomIndex = Math.floor(Math.random() * roles.value.length);
        selectedRole.value = roles.value[randomIndex];
      }
    })
    .catch(error => {
      console.error('Error fetching roles', error);
    });
};

const showInfo = ref(false);
const emit = defineEmits(['contributorAdded']);
const showSelectRole = ref(true);
const session = ref(props.session);
const userId = ref(props.userId);
const selectedRole = ref({ id: null, name: '', description: '', icon: '' });
const roles = ref([]);


const updateSelectedRole = () => {
  const role = roles.value.find(r => r.id === selectedRole.value.id);
  if (role) {
    selectedRole.value = { ...role };
  }
};

const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};

const addContributor = () => {
  console.log("addContributor, session.value.id", session.value.id, "selectedRole.value", selectedRole.value.id, "userId.value",userId.value);
  axios.post('/api/contributor/create', {
    session_id: session.value.id,
    user_id: userId.value,
    role_id: selectedRole.value.id
  })
    .then(response => {
      console.log('Server response:', response.data);
      showSelectRole.value = false;
      emit('contributorAdded');
    })
    .catch(error => {
      showSelectRole.value = false;
      console.error('Error adding Contributor', error);
    });
};

onMounted(() => {
  console.log("Rollenwahl Mounted: ", session.value.id, userId.value, session.value.method)
  Echo.channel('session.' + session.value.id)
    .listen('UserPickedRole', (e) => {
      console.log("UserPickedRole event empfangen")
      getRoles();
    });
  if (session.value.id) {
    getRoles();
  }
});
</script>