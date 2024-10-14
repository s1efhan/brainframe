<template>
 <section class="already_started" v-if="session.method.name === '6-3-5' && (session.collecting_round > 0 || session.phase === 'voting')">
    <p>Die Session hat schon gestartet. Leider kann man bei der 6-3-5 Methode nicht nachträglich beitreten. Du kannst dir aber am Ende das Ergebnis ansehen</p>
  </section>
  <section v-else>
  <div class="selectRole__head">
    <h2>Rollenwahl</h2>
    <div class="info__container">
      <div @click="showInfo = !showInfo" class="join__info">
        <p>i</p>
      </div>
    </div>
  </div>
  <div v-if="showInfo" class="info__text__container">
    <div class="info__text">
     
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
    <div  :class="{ 'glow-animation': !pickedARole }" class="role-select__container">
     <select @click = "pickedARole = true" id="roleSelect" v-model="selectedRoleId">
        <option v-for="role in roles" :key="role.id" :value="role.id">
          {{ role.name }}
        </option>
      </select>
      <div v-if="selectedRole.icon" class="role-icon">
        <component :is="getIconComponent(selectedRole.icon)" /> {{ selectedRole.name }}
      </div>
    </div>
    <button class="primary" type="submit" :class="{ 'glow-animation': pickedARole }">Rolle Wählen</button>
  </form>
</section>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import IconComponents from '../components/IconComponents.vue';
import SelectIcon from '../components/icons/SelectIcon.vue';
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
const pickedARole = ref(false);
const getRoles = async () => {
  try {
    const response = await axios.get(`/api/roles/${session.value.id}`);
    roles.value = response.data;
    if (roles.value.length > 0) {
      const randomIndex = Math.floor(Math.random() * roles.value.length);
      selectedRoleId.value = roles.value[randomIndex].id;
    }
  } catch (error) {
    console.error('Error fetching roles', error);
  }
};

const showInfo = ref(true);
const emit = defineEmits(['contributorAdded']);
const showSelectRole = ref(true);
const session = ref(props.session);
const userId = ref(props.userId);
const selectedRole = computed(() => {
  return roles.value.find(r => r.id === selectedRoleId.value) || { id: null, name: '', description: '', icon: '' };
});
const selectedRoleId = ref(null);
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
  updateSelectedRole();
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
  console.log("Rollenwahl Mounted: ", session.value, userId.value);
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