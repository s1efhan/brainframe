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
      <ul v-if="props.methodName =='6 Thinking Hats'">
        <li>Die Wahl deiner "Rolle" hat <strong>großen Einfluss</strong> auf den weiteren Verlauf der Ideen-Sammel Session. <br>Sie bestimmt deine <strong>Perspektive beim Sammeln und Bewerten </strong>und ermöglicht es anonym über Ideen zu sprechen.</li>
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
        <li>Die Wahl deiner "Rolle" hat <strong>keinen Einfluss</strong> auf den weiteren Verlauf der Ideen-Sammel Session. <br>Sie ist rein <strong>ästhetischer Natur </strong>und dient dazu vereinfacht und anonym über Ideen zu sprechen.</li>
      </ul>
    </div>
  </div>
  <form class="selectRole" v-if="showSelectRole" @submit.prevent="handleSubmit">
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
import { useRoute } from 'vue-router';
import axios from 'axios';
import IconComponents from '../components/IconComponents.vue';

const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  },
  methodName: {
    type: [String, null],
    required: true
  }
});

const showInfo = ref(false);
const emit = defineEmits(['contributorAdded']);
const showSelectRole = ref(true);
const sessionId = ref('');
const selectedRole = ref({ id: null, name: '', description: '', icon: '' });
const roles = ref([]);
const userId = ref('');

const route = useRoute();

const updateSelectedRole = () => {
  const role = roles.value.find(r => r.id === selectedRole.value.id);
  if (role) {
    selectedRole.value = { ...role };
  }
};

const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};

const getRoles = () => {
  axios.get(`/api/sessions/${sessionId.value}/roles`)
    .then(response => {
      roles.value = response.data;
      console.log('roles.value', roles.value);
      if (roles.value.length > 0) {
        selectedRole.value = { ...roles.value[0] };
        console.log('selected Role:', selectedRole.value)
      }
    })
    .catch(error => {
      console.error('Error fetching roles', error);
    });
};

const handleSubmit = () => {
  addContributor();
};

const addContributor = () => {
  axios.post('/api/contributor', {
    session_id: sessionId.value,
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
  sessionId.value = route.params.id;
  userId.value = props.userId;
  if (sessionId.value) {
    getRoles();
  }
});
</script>