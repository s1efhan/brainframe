<template>
  <section v-if="showSelectRole">
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
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});
const emit = defineEmits(['contributorAdded']);
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
    emit('contributorAdded'); // Emittieren Sie ein Event
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