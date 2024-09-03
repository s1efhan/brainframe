<template>
  <div class="vote__headline__container">
    <h2>Swipe to Vote 🔥</h2>
  </div>
  <div v-if="currentIdea" class="idea-card" @touchstart="touchStart" @touchend="touchEnd">
    <h3>{{ currentIdea.ideaTitle }}</h3>
    <div class="idea__description__container">
      <button class="swipe__arrow__left secondary" @click="swipeLeft">🚫</button>
      <div class="idea__description" v-html="currentIdea.ideaDescription"></div>
      <button class="swipe__arrow__right secondary" @click="swipeRight">💚</button>
    </div>
    <div class="idea-card__bottom">
      <button @click="undoLastDecision" class="secondary undo":disabled="!previousIdea">↺</button>
      <div class="tag">#{{ currentIdea.tag }}</div>
      <div class="contributor__icon"> <ProfileIcon/><component :is="getIconComponent(currentIdea.contributorIcon)" /></div>
    </div>
  </div>
  <p v-else>Fertig. Du musst warten, bis der Rest fertig mit Voten ist.</p>

  <div v-if="ideasCount" class="ideasCount">
    {{ decisionsMade }}/{{ ideasCount }}
  </div>
</template>

<script setup>
import { ref, onMounted, toRef } from 'vue';
import ProfileIcon from '../icons/ProfileIcon.vue';
import axios from 'axios';

const currentIdea = ref(null);
const previousIdea = ref(null);
const decisionsMade = ref(0);
const ideasCount = ref(null);
const ideas = ref([]);

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
  votingPhase: {
    type: Number,
    required:true
  }
});
import IconComponents from '../IconComponents.vue';
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const sessionId = toRef(props, 'sessionId');
const contributorId = toRef(props, 'contributorId');
const votingPhase = toRef(props, 'votingPhase');
const sendVote = (ideaId, voteValue) => {
  console.log('Sending vote:', sessionId.value, voteValue, ideaId, contributorId.value, 'left_right', voteValue);
  axios.post('/api/vote', {
    votes: [
      {
        session_id: sessionId.value,
        idea_id: ideaId,
        contributor_id: contributorId.value,
        vote_type: 'swipe',
        vote_value: voteValue,
        voting_phase: votingPhase.value
      }
    ]
  })
  .then(response => {
    console.log('Server response:', response.data);
  })
  .catch(error => {
    console.error('Fehler beim Speichern deines Votes', error);
  });
};
onMounted(() => {
  ideasCount.value = props.ideasCount;
  ideas.value = props.ideas;
  setNextIdea();
});

const setNextIdea = () => {
  if (ideas.value.length > 0) {
    previousIdea.value = currentIdea.value;
    currentIdea.value = ideas.value[0];
  } else {
    currentIdea.value = null;
  }
  decisionsMade.value = props.ideasCount - ideas.value.length;
};

const swipeLeft = () => {
  // "Nicht mögen" Logic
  sendVote(currentIdea.value.id, 0);  // Sende "Nicht mögen" Abstimmung
  ideas.value.shift();
  setNextIdea();
};

const swipeRight = () => {
  // "Mögen" Logic
  sendVote(currentIdea.value.id, 1);  // Sende "Mögen" Abstimmung
  ideas.value.shift();
  setNextIdea();
};

const undoLastDecision = () => {
  if (previousIdea.value) {
    ideas.value.unshift(previousIdea.value);
    currentIdea.value = previousIdea.value;
    previousIdea.value = null;
    decisionsMade.value = props.ideasCount - ideas.value.length;
  }
};

// Touch-Funktionalität für das Wischen
let touchStartX = 0;
let touchEndX = 0;

const touchStart = (event) => {
  touchStartX = event.changedTouches[0].screenX;
};

const touchEnd = (event) => {
  touchEndX = event.changedTouches[0].screenX;
  handleSwipe();
};

const handleSwipe = () => {
  const swipeThreshold = 50; // Mindestdistanz für ein Wischen
  if (touchEndX < touchStartX - swipeThreshold) {
    swipeLeft();
  } else if (touchEndX > touchStartX + swipeThreshold) {
    swipeRight();
  }
};
</script>
