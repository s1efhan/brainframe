<template>
  <main>
    <h1 class="headline__session-target" @input="adjustHeadline" :style="{ height: headlineHeight }">
    {{ sessionDetails.target }}
  </h1>
    <div class="session_headline__details" v-if="sessionDetails && personalContributor" >
      <div>
      <ProfileIcon />
      <p v-if="personalContributor.role_name != 'Default'"> {{ contributorsCount }} | {{ contributorsAmount }} </p>
    </div>
    <div>
      <p>{{ methodName }} Methode</p>
    </div>
    <div>
      <p v-if="personalContributor.role_name != 'Default'" class="contributor-icon">{{ personalContributor.icon }}</p>
      <p>{{ personalContributor.role_name }}</p>
    </div>
  </div>
  <Rollenwahl v-if="!personalContributor || personalContributor.role_name === 'Default'" :userId="userId"
    @contributorAdded="handleContributorAdded" />

    <CollectingPhase @switchPhase="switchPhase" v-if="method && personalContributor && sessionPhase === 'collectingPhase' && personalContributor.role_name != 'Default' " :method="method" :sessionHostId="sessionHostId"
      :contributors="contributors" :sessionId="sessionId" :personalContributor="personalContributor" />
    <VotingPhase v-if=" method && personalContributor && sessionPhase === 'votingPhase' && personalContributor.role_name != 'Default' " :sessionId="sessionId" :sessionHostId="sessionHostId"
      :personalContributor="personalContributor" :contributorsCount="contributorsCount"/>
    <ClosingPhase v-if="method && personalContributor && sessionPhase === 'closingPhase' && personalContributor.role_name != 'Default' " :sessionId="sessionId" :sessionHostId="sessionHostId"
      :personalContributor="personalContributor" />
    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { sessionId } from '../js/eventBus.js'
import axios from 'axios';
import Rollenwahl from './Rollenwahl.vue';
import { useRoute } from 'vue-router';
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

const updateSessionId = () =>
{
    sessionId.value = route.params.id;
    emit('updateSessionId', sessionId.value);
}
const headlineHeight = ref('auto');

const adjustHeadline = (event) => {
  const headline = event.target;

  // Begrenzen Sie die Eingabe auf maximal 50 Zeichen
  if (headline.textContent.length > 60) {
    headline.textContent = headline.textContent.slice(0, 60);
  }

  // Passen Sie die Höhe des Headline an
  headlineHeight.value = `${headline.scrollHeight}px`;
};
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
const userId = ref(props.userId);
const getSessionDetails = () => {
  axios.get(`/api/session/${sessionId.value}`)
    .then(
      response => {
        sessionDetails.value = response.data;
        methodId.value = sessionDetails.value.method_id;
        methodName.value = sessionDetails.value.method_name;
        sessionPhase.value = sessionDetails.value.session_phase || 'collectingPhase';
        contributorsCount.value = sessionDetails.value.contributors_count;
        contributorsAmount.value = sessionDetails.value.contributors_amount;
        sessionHostId.value = sessionDetails.value.session_host;
        getMethodDetails();
        getContributors();
      })
    .catch(error => {
      console.error('Error fetching Session Details', error);
    });
};
const emit = defineEmits(['updateSessionId']);
const switchPhase = (switchedPhase) => {
  sessionPhase.value = switchedPhase;
  console.log('switching to phase: ', switchedPhase)
  console.log(personalContributor.value.id, sessionHostId.value);
  if(personalContributor.value.id == sessionHostId.value){
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
  }
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
  console.log("Session Mounted")
  Echo.channel('session.' + sessionId.value)
    .listen('SwitchPhase', (e) => {
      console.log('SwitchPhase Event empfangen:', e);
      sessionPhase.value = e.phase;
    });
    updateSessionId();
    getSessionDetails();
});
</script>