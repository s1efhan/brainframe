<template>
  <section>
    <table v-if="sessionHostId && personalContributor">
     <tr v-if="personalContributor && sessionHostId == personalContributor.id">
        <td @click="switchPhase('collectingPhase')"><button>Collecting</button></td>
        <td @click="switchPhase('votingPhase')"><button>Voting</button></td>
        <td @click="switchPhase('closingPhase')"><button>Closing</button></td>
      </tr>
    </table>
    <CollectingPhase  v-if="sessionHostId && personalContributor && method && sessionPhase === 'collectingPhase' && personalContributor" :method="method" :sessionHostId="sessionHostId" :contributors="contributors" :sessionId="sessionId" :personalContributor="personalContributor" />
    <VotingPhase v-if="sessionHostId && personalContributor && sessionPhase === 'votingPhase' && personalContributor" :sessionId="sessionId" :sessionHostId="sessionHostId" :personalContributor="personalContributor"/>
    <ClosingPhase v-if="sessionHostId && personalContributor && sessionPhase === 'closingPhase' && personalContributor" :sessionId="sessionId" :sessionHostId="sessionHostId" :personalContributor="personalContributor"/>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import VotingPhase from '../components/phases/VotingPhase.vue';
import CollectingPhase from '../components/phases/CollectingPhase.vue';
import ClosingPhase from '../components/phases/ClosingPhase.vue';
import axios from 'axios';

const props = defineProps({
  methodId: {
    type: [String, Number],
    required: true
  }, 
  personalContributor: {
    type: [Object, null],
    required: true
  },
  sessionHostId: {
    type: [String, Number],
    required: true
  },
  contributors: {
    type: [Object, null],
    required: true
  }
});

const sessionHostId = ref(null);
const contributors = ref(null);
const switchPhase = (switchedPhase) => {
  console.log ('switching to phase: ', switchedPhase)
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
const route = useRoute();
const methodId = ref(props.methodId);
const method = ref(null);
const sessionPhase = ref('collectingPhase');
const sessionId = ref('');

const personalContributor = ref(null);
const getMethodDetails = () => {
  axios.get(`/api/method/${methodId.value}`)
    .then(response => {
      method.value = response.data;
      console.log(method.value)
    })
    .catch(error => {
      console.error('Error fetching Method Details', error);
    });
};

onMounted(() => {
  sessionId.value = route.params.id;
  sessionPhase.value = route.params.phase || 'collectingPhase';
  methodId.value = props.methodId;
  contributors.value = props.contributors;
  sessionHostId.value = props.sessionHostId;
  personalContributor.value = props.personalContributor;
  console.log("contributor_id, host_id", personalContributor.value, sessionHostId.value);
    getMethodDetails();
    Echo.channel('session.' + sessionId.value)
  .listen('SwitchPhase', (e) => {
      console.log('SwitchPhase Event empfangen:', e);
      sessionPhase.value = e.phase;
  });
});
</script>