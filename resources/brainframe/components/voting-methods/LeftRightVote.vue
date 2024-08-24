<template>
  <div class="vote__headline__container">
    <h2>Pick Left or Right to Vote <br>↔</h2>
  </div>
  <div class="left-right__container" v-if="currentPair.length === 2">
    <div class="idea-card">
      <h3>{{ currentPair[0].ideaTitle }}</h3>
      <div class="idea__description__container">
        <div class="idea__description" v-html="currentPair[0].ideaDescription"></div>
      </div>
      <div class="idea-card__bottom button">
        <button class="primary" @click="selectIdea(0)">L</button>
      </div>
      <div class="idea-card__bottom tag">
        <p>{{currentPair[0].contributorIcon }}</p>
        <p>#{{ currentPair[0].tag }}</p>
      </div>
    </div>
    <div class="idea-card">
      <h3>{{ currentPair[1].ideaTitle }}</h3>
      <div class="idea__description__container">
        <div class="idea__description" v-html="currentPair[1].ideaDescription"></div>
      </div>
      <div class="idea-card__bottom button">
        <button class="primary" @click="selectIdea(1)">R</button>
      </div>
      <div class="idea-card__bottom tag">
        <p>{{currentPair[1].contributorIcon }}</p>
        <p>#{{ currentPair[1].tag }}</p>
      </div>
    </div>
  </div>

  <p v-else>Fertig. Du musst warten, bis der Rest fertig mit Voten ist.</p>
  <button class="secondary undo" @click="undoLastDecision" :disabled="previousPair.length === 0">↺</button>
  <div v-if="ideasCount" class="ideasCount">
    {{ decisionsMade }}/{{ ideasCount }}
  </div>
</template>

<script setup>
import { ref, onMounted, toRef} from 'vue';
import axios from 'axios';

const props = defineProps({
  ideasCount: {
    type: [String, Number],
    required: true
  },
  ideas: {
    type: Array,
    required: true
  },
  sessionId: {
    type: [String, Number],
    required: true
  },
  contributorId: {
    type: [String, Number],
    required: true
  },
});


const currentPair = ref([]);
const previousPair = ref([]);
const decisionsMade = ref(0);
const ideasCount = ref(0);
const ideas = ref([]);
const sessionId = toRef(props, 'sessionId');
const contributorId = toRef(props, 'contributorId');

const sendVote = (ideaId, voteValue) => {
  console.log('sessionId.value, voteValue, ideaId, contributorId.value, left_right, voteValue', sessionId.value, voteValue, ideaId, contributorId.value, 'left_right', voteValue);
  axios.post('/api/vote', {
    session_id: sessionId.value,
    idea_id: ideaId,
    contributor_id: contributorId.value,
    vote_type: 'left_right',
    vote_value: voteValue
  })
  .then(response => {
    console.log('Server response:', response.data);
  })
  .catch(error => {
    console.error('Fehler beim Speichern deines Votes', error);
  });
};

const setNextPair = () => {
  if (ideas.value.length >= 2) {
    previousPair.value = [...currentPair.value];
    currentPair.value = ideas.value.slice(0, 2);
    decisionsMade.value = ideasCount.value - ideas.value.length;
  } else {
    currentPair.value = [];
  }
};

const selectIdea = (selectedIndex) => {
  const selectedIdea = currentPair.value[selectedIndex];
  const unselectedIdea = currentPair.value[1 - selectedIndex];
  
  sendVote(selectedIdea.id, 1);  // Ausgewählte Idee bekommt 1
  sendVote(unselectedIdea.id, 0);  // Nicht ausgewählte Idee bekommt 0
  
  ideas.value.splice(0, 2);  // Entfernt beide Ideen aus dem Array
  setNextPair();
};

const undoLastDecision = () => {
  if (previousPair.value.length === 2) {
    ideas.value.unshift(...previousPair.value);
    currentPair.value = [...previousPair.value];
    previousPair.value = [];
    decisionsMade.value = ideasCount.value - ideas.value.length;
  }
};

onMounted(() => {
  ideasCount.value = parseInt(props.ideasCount);
  ideas.value = [...props.ideas];
  setNextPair();
  console.log("ContributorId: Pops", props.contributorId);
});
</script>