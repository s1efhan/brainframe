<template>
  <component 
  :is="votingMethods[votingMethod]" 
  v-if="personalContributor && sessionId" 
  :contributorId="personalContributor.id"
  :ideas="ideas" 
  :ideasCount="ideasCount" 
  :votedIdeasCount="votedIdeasCount"
  :sessionId="sessionId" 
  :votingPhase="votingPhase" @lastVote="handleContributorsLastVote"
/>
</template>
<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import StarVote from '../voting-methods/StarVote.vue';
import RankingVote from '../voting-methods/RankingVote.vue';
import LeftRightVote from '../voting-methods/LeftRightVote.vue';
import SwipeVote from '../voting-methods/SwipeVote.vue';
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
  },
  votingPhase: {
    type: [Number, null],
    required: true
  }
});
const ideasCount = ref(null);
const votedIdeasCount = ref(null);
const votingPhase = ref(props.votingPhase);
const ideas = ref([]);
const votingMethod = ref(null);
const votingMethods = {
  StarVote,
  RankingVote,
  LeftRightVote,
  SwipeVote
};
const personalContributor = ref(props.personalContributor);
const sessionHostId = ref(props.sessionHostId);
const sessionId = ref(props.sessionId)
const emit = defineEmits(['switchPhase', 'wait']);
const getIdeas = () => {
  console.log('getIdeas, votingPhase', votingPhase.value);
  axios.get(`/api/ideas/${sessionId.value}/${votingPhase.value}/${personalContributor.value.id}`)
    .then(response => {
      ideas.value = response.data.ideas;
      ideasCount.value = response.data.ideasCount;
      votingMethod.value = response.data.votingMethod;
      votedIdeasCount.value = response.data.votedIdeasCount;
      console.log("ideasCount.value", ideasCount.value);
      console.log("votedIdeasCount.value", votedIdeasCount.value);
      console.log("votingMethod.value", votingMethod.value);
    })
    .catch(error => {
      console.error('Error fetching ideas', error);
      emit('wait');
    });
}
const handleContributorsLastVote = () => {
  console.log('lastVote');
  emit('wait');
}
watch(() => props.votingPhase, (newPhase, oldPhase) => {
  if (newPhase !== oldPhase) {
    votingPhase.value = newPhase;
    getIdeas();
  }
});
onMounted(() => {
  getIdeas();
});
</script>