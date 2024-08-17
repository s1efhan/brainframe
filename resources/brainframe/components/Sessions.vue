<template>
    <div>
      <h1>User Sessions</h1>
      <table v-if="userSessions && userSessions.length > 0">
        <thead>
          <tr>
            <th>Session ID</th>
            <th>Target</th>
            <th>Role</th>
            <th>Updated At</th>
            <th>Method</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="session in userSessions" :key="session.session_id">
            <td> <router-link :to="'/brainframe/'+session.session_id">{{ session.session_id }}</router-link></td>
            <td>{{ session.target }}</td>
            <td>{{ session.role }}</td>
            <td>
            {{
              new Date(session.updated_at).toLocaleDateString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: '2-digit'
              }) + ' ' +
              new Date(session.updated_at).toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit'
              }) + ' Uhr'
            }}
          </td>
            <td>{{ session.method_name }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else>Keine Sitzungen gefunden.</p>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import axios from 'axios';
  
  const props = defineProps({
    userId: {
      type: [String, Number],
      required: true
    }
  });
  
  const userSessions = ref([]);
  const userId = ref(props.userId);
  
  const getUserSessions = () => {
    axios.get(`/api/${userId.value}/sessions`)
      .then(response => {
        userSessions.value = response.data;
        console.log('userSessions: ', userSessions.value);
      })
      .catch(error => {
        console.error('Error fetching sessions', error);
      });
  };
  
  onMounted(() => {
    getUserSessions();
  });
  </script>

  