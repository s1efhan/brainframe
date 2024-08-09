<template>
  <section>
    <h2 v-if="method">{{method.name}}</h2>
    <table>
      <tr>
        <td @click="sessionPhase ='collectingPhase'"><button>Collecting</button></td>
        <td @click="sessionPhase ='votingPhase'"><button>Voting</button></td>
        <td @click="sessionPhase ='closingPhase'"><button>Closing</button></td>
      </tr>
    </table>
    <CollectingPhase v-if="sessionPhase === 'collectingPhase' && personalContributor" :personalContributor="personalContributor" />
    <VotingPhase v-if="sessionPhase === 'votingPhase' && personalContributor" :personalContributor="personalContributor"/>
    <ClosingPhase v-if="sessionPhase === 'closingPhase' && personalContributor" :personalContributor="personalContributor"/>
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
  }
});

const route = useRoute();
const methodId = ref(props.methodId);
const method = ref('');
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
  personalContributor.value = props.personalContributor;
    getMethodDetails();
});
</script>