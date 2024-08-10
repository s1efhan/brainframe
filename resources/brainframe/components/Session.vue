<template>
  <h1>Ziel: {{ sessionDetails.target }}</h1>
<p v-if="personalContributor">
  Deine Rolle: {{ personalContributor.role_name }}
</p>
  <Rollenwahl  v-if="!personalContributor" :userId="userId"  @contributorAdded="handleContributorAdded"/>
  <Method v-if="methodId && personalContributor" :personalContributor="personalContributor" :methodId ="methodId"/> 
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Rollenwahl from './Rollenwahl.vue';
import Method from './Method.vue';
import axios from 'axios';
const route = useRoute();

const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});
const contributors = ref([]);
const personalContributor = ref(null);
const handleContributorAdded = () => {
  getContributors();
};
const getContributors = () => {
  axios.get(`/api/contributors/${sessionId.value}/${userId.value}`)
    .then(response => {
      contributors.value = response.data.contributors;
      personalContributor.value = response.data.personal_contributor;
      console.log('Contributors:', contributors.value);
      console.log('Personal Contributor:', personalContributor.value);
    })
    .catch(error => {
      console.error('Error fetching contributors', error);
    });
};

const methodId = ref('');
const sessionDetails = ref([])
const getSessionDetails = () => {
  axios.get(`/api/session/${sessionId.value}`)
    .then(response => {
      sessionDetails.value = response.data;
      methodId.value = sessionDetails.value.method_id;
    
    })
    .catch(error => {
      console.error('Error fetching Session Details', error);
    });
};
const showLobby = ref(true);
const sessionId = ref(route.params.id);
const userId = ref(props.userId);

onMounted(() => {
  sessionId.value = route.params.id;
  userId.value = props.userId;
  if (sessionId.value) {
    getSessionDetails();
    getContributors();
  }
});
</script>