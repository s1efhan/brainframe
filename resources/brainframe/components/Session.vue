<template>
  <h1>Ziel: {{ sessionDetails.target }}</h1>
<p v-if="personalContributor">
  Deine Rolle: {{ personalContributor.role_name }} {{ personalContributor.icon }}
</p>
<p v-if = "sessionDetails">Methode: {{ methodName }}</p>
  <Rollenwahl  v-if="!personalContributor" :userId="userId"  @contributorAdded="handleContributorAdded"/>
  <Method v-if="methodId && personalContributor" :sessionHostId="sessionHostId" :personalContributor="personalContributor" :contributors="contributors" :methodId ="methodId"/> 
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import Rollenwahl from './Rollenwahl.vue';
import Method from './Method.vue';
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

const methodId = ref(null);
const methodName = ref(null);
const sessionHostId = ref(null);
const sessionDetails = ref([])
const getSessionDetails = () => {
  console.log("sessionId.value", sessionId.value);
  axios.get(`/api/session/${sessionId.value}`)
    .then(response => {
      sessionDetails.value = response.data;
      methodId.value = sessionDetails.value.method_id;
      methodName.value = sessionDetails.value.method_name;
      sessionHostId.value = sessionDetails.value.session_host;
      console.log(sessionHostId.value, "sessionHost.value)")
    console.log("sessiondetails, Contributor ID which is the host: ", sessionHostId.value)
    })
    .catch(error => {
      console.error('Error fetching Session Details', error);
    });
};
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