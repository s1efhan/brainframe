<template>
  <h1 class="headline__session-target">{{ sessionDetails.target }}</h1>
  <div v-if="personalContributor && personalContributor.role_name != 'Default'" class="session__contributors">
    <ProfileIcon />
    <p>{{ contributorsCount }} | {{ contributorsAmount }}</p>
  </div>
  <div class="session__contributors">
    <p v-if="sessionDetails">{{ methodName }} Methode</p>
  </div>
  <div v-if="sessionDetails && personalContributor" class="session__contributors">
    <p>{{ personalContributor.icon }}</p>
    <p>{{ personalContributor.role_name }}</p>
  </div>
  <Rollenwahl v-if="!personalContributor || personalContributor.role_name === 'Default'" :userId="userId"
    @contributorAdded="handleContributorAdded" />
  <div class="methodPhase" v-if="method && personalContributor">
    methodPhase
    <CollectingPhase v-if="sessionPhase === 'collectingPhase'" :method="method" :sessionHostId="sessionHostId"
      :contributors="contributors" :sessionId="sessionId" :personalContributor="personalContributor" />
    <VotingPhase v-if="sessionPhase === 'votingPhase'" :sessionId="sessionId" :sessionHostId="sessionHostId"
      :personalContributor="personalContributor" />
    <ClosingPhase v-if="sessionPhase === 'closingPhase'" :sessionId="sessionId" :sessionHostId="sessionHostId"
      :personalContributor="personalContributor" />
  </div>

</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import Rollenwahl from './Rollenwahl.vue';
const route = useRoute();
import ProfileIcon from '../components/icons/ProfileIcon.vue';
import VotingPhase from '../components/phases/VotingPhase.vue';
import CollectingPhase from '../components/phases/CollectingPhase.vue';
import ClosingPhase from '../components/phases/ClosingPhase.vue';
const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});

const collectingTimer = ref(360);
const contributors = ref([]);
const personalContributor = ref(null);
const handleContributorAdded = () => {
  getContributors();
};
const sessionPhase = ref('collectingPhase');
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
const sessionDetails = ref([]);
const contributorsCount = ref(null);
const contributorsAmount = ref(null);
const method = ref(null);
const sessionId = ref(route.params.id);
const userId = ref(props.userId);
const getSessionDetails = () => {
  axios.get(`/api/session/${sessionId.value}`)
    .then(
      response => {
      sessionDetails.value = response.data;
      methodId.value = sessionDetails.value.method_id;
      console.log('methodId.value', methodId.value)
      methodName.value = sessionDetails.value.method_name;
      contributorsCount.value = "?";
      contributorsAmount.value = "?";
      sessionHostId.value = sessionDetails.value.session_host;
      getMethodDetails();
      getContributors();
    })
    .catch(error => {
      console.error('Error fetching Session Details', error);
    });
};
const switchPhase = (switchedPhase) => {
  console.log('switching to phase: ', switchedPhase)
  axios.post('/api/phase', {
    switched_phase: switchedPhase,
    session_id: sessionId.value
  })
    .then(response => {
      console.log('Server response:', response.data);
    })
    .catch(error => {
      console.error('Error switching Phase', error);
    });
};
const getMethodDetails = () => {
  axios.get(`/api/method/${methodId.value}`)
    .then(response => {
      method.value = response.data;
      console.log(method.value);
    })
    .catch(error => {
      console.error('Error fetching Method Details', error);
    });
};

onMounted(() => {
  sessionId.value = route.params.id;
    getSessionDetails();
    Echo.channel('session.' + sessionId.value)
      .listen('SwitchPhase', (e) => {
        console.log('SwitchPhase Event empfangen:', e);
        sessionPhase.value = e.phase;
      });
});
</script>