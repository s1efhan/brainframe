<template>
  <section v-if="showSelectRole && !personalContributor">
    <form @submit.prevent="handleSubmit">
      <label for="roleSelect">Wähle eine Rolle:</label>
      <select id="roleSelect" v-model="selectedRole.id" @change="updateSelectedRole">
        <option v-for="role in roles" :key="role.id" :value="role.id">
          {{ role.name }}
        </option>
      </select>
      <p v-if="selectedRole">{{ selectedRole.description }}</p>
      <button type="button" @click="addContributor">Session Beitreten</button>
    </form>
  </section>
  <section v-if="personalContributor">
    <ol>
      <li v-for="(contributor, index) in contributors" :key="contributor.id">
        {{ index + 1 }}. Teilnehmer: {{ contributor.role_name }}
      </li>
    </ol>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  },
  personalContributor: {
    type: [Object, null],
    required: true
  }
});
const personalContributor = ref(null);
const showSelectRole = ref(true);
const sessionId = ref('');
const selectedRole = ref({ id: null, name: '', description: '' });
const roles = ref([]);
const userId = ref('');

const route = useRoute();

const updateSelectedRole = () => {
  const role = roles.value.find(r => r.id === selectedRole.value.id);
  if (role) {
    selectedRole.value = { ...role };
  }
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

const addContributor = () => {
  axios.post('/api/contributor', {
    session_id: sessionId.value,
    user_id: userId.value,
    role_id: selectedRole.value.id
  })
    .then(response => {
      console.log('Server response:', response.data);
      showSelectRole.value = false;
    })
    .catch(error => {
      showSelectRole.value = false;
      console.error('Error adding Contributor', error);
    });
};

onMounted(() => {
  sessionId.value = route.params.id;
  userId.value = props.userId;
  personalContributor.value = props.personalContributor;
  if (sessionId.value) {
    getRoles();
  }
});
</script>