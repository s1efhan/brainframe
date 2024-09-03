<template>
  <button class="primary" @click="finishedVoting">finishedVoting</button>
  <p>{{ votingPhase }}</p>
  <p>{{ votingMethod }}</p>
  <component 
  :is="votingMethods[votingMethod]" 
  v-if="personalContributor && sessionId" 
  :contributorId="personalContributor.id"
  :ideas="ideas" 
  :ideasCount="ideasCount" 
  :sessionId="sessionId" 
  :votingPhase="votingPhase"
/>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import StarVote from '../voting-methods/StarVote.vue';
import RankingVote from '../voting-methods/RankingVote.vue';
import LeftRightVote from '../voting-methods/LeftRightVote.vue';
import SwipeVote from '../voting-methods/SwipeVote.vue';
const ideasCount = ref(null);
const votingPhase = ref(1);
const ideas = ref([]);
const votingMethod = ref(null);
const votingMethods = {
  StarVote,
  RankingVote,
  LeftRightVote,
  SwipeVote
};

const props = defineProps({
  personalContributor: {
    type: Object,
    required: true
  },
  contributorsCount: {
    type: [String, Number, null],
    required: true
  },
  sessionId: {
    type: [String, Number],
    required: true
  },
  sessionHostId: {
    type: [String, Number],
    required: true
  }
});
const emit = defineEmits(['finishedVoting']);
const personalContributor = ref(props.personalContributor);
const sessionHostId = ref(null);
const sessionId = ref(null)
const getIdeas = () => {
  console.log('getIdeas');
  axios.get(`/api/ideas/${sessionId.value}/${votingPhase.value}/${personalContributor.value.id}`)
    .then(response => {
      ideas.value = response.data.ideas;
      ideasCount.value = response.data.ideasCount;
      votingMethod.value = response.data.votingMethod;
      console.log("votingMethod.value", votingMethod.value);
      console.log("ideas.value", ideas.value);
      console.log(Array.isArray(ideas.value)); // Gibt true oder false zurück
    })
    .catch(error => {
      console.error('Error fetching ideas', error);
    });
}
const finishedVoting = () =>{
  if(personalContributor.value.id == sessionHostId.value){
  console.log("finishedVoting", votingPhase.value);
  if(votingMethod.value != 'RankingVote'){
    votingPhase.value++;
    getIdeas();
    console.log("votingMethod != RankingVote")
  }
  else {
    console.log("switchPhase to closing")
    emit('finishedVoting');
  }
}
}
onMounted(() => {
  sessionId.value = props.sessionId;
  personalContributor.value = props.personalContributor;
  sessionHostId.value = props.sessionHostId;
  getIdeas();
  Echo.channel('session.' + sessionId.value)
    .listen('VotingFinished', (e) => {
      console.log('VotingFinished Event empfangen:', e);
      console.log("contributorsCount", props.contributorsCount);
    })
});
</script>