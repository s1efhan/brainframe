<template>
    <h2>VotingPhase</h2>
    <SwipeVote v-if="ideasCount > 15 && votingPhaseNumber === 1" :ideas="ideas" :ideasCount ="ideasCount"/>
    <LeftRightVote v-if="ideasCount > 15 && votingPhaseNumber === 2" :ideas="ideas" :ideasCount ="ideasCount"/>
    <StarVote v-if="ideasCount > 5 && ideasCount <= 15":ideasCount ="ideasCount" :ideas="ideas"/>
    <RankingVote v-if="ideasCount <= 5":ideasCount ="ideasCount" :ideas="ideas"/>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import StarVote from '../voting-methods/StarVote.vue';
import RankingVote from '../voting-methods/RankingVote.vue';
import LeftRightVote from '../voting-methods/LeftRightVote.vue';
import SwipeVote from '../voting-methods/SwipeVote.vue';
const ideasCount = ref(null);
const votingPhaseNumber = ref(1);
const ideas = ref([]);
const personalContributor = ref(null);
const props = defineProps({
  personalContributor: {
    type: Object,
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
const sessionHostId = ref(null);
const sessionId = ref(null)
const getIdeas = () => {
  console.log('getIdeas');
  axios.get(`/api/ideas/${sessionId.value}/${votingPhaseNumber.value}`)
    .then(response => {
      ideas.value = response.data.ideas;
      ideasCount.value = response.data.ideasCount;
      console.log('Ideas:', JSON.parse(JSON.stringify(ideas.value)));
      console.log('Ideas Count:', ideasCount.value);
      console.log('votingPhaseNumber', votingPhaseNumber.value);
    })
    .catch(error => {
      console.error('Error fetching ideas', error);
    });
}
onMounted(()=>{
    sessionId.value = props.sessionId;
    personalContributor.value = props.personalContributor;
    sessionHostId.value = props.sessionHostId;
    getIdeas();
});
</script>
