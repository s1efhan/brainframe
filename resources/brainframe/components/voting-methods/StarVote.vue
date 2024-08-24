<template>
  <div class="vote__headline__container">
    <h2>Rate This Idea <br>★★✰</h2>
  </div>
  <div v-if="currentIdea" class="idea-card star-vote" @touchstart="touchStart" @touchend="touchEnd">
    <h3>{{ currentIdea.ideaTitle }}</h3>
    <div class="idea__description__container">
      <div v-html="currentIdea.ideaDescription"></div>
    </div>
    <div class="star-rating">
      <button class="primary" @click="rate(1)" :class="{ active: tempRating >= 1 }">★</button>
      <button class="primary" @click="rate(2)" :class="{ active: tempRating >= 2 }">★</button>
      <button class="primary" @click="rate(3)" :class="{ active: tempRating >= 3 }">★</button>
    </div>
    <div class="idea-card__bottom">
      <button @click="undoLastDecision" class="secondary undo" :disabled="!previousIdea">↺</button>
      <div class="tag">#{{ currentIdea.tag }}</div>
      <div class="contributor__icon">
        <ProfileIcon />{{ currentIdea.contributorIcon }}
      </div>
    </div>
  </div>
  <p v-else>Fertig. Du musst warten, bis der Rest fertig mit Voten ist.</p>
  <div v-if="ideasCount" class="ideasCount">
    {{ decisionsMade }}/{{ ideasCount }}
  </div>
</template>

<script setup>
import { ref, onMounted, toRef } from 'vue';
import axios from 'axios';

const currentIdea = ref(null);
const previousIdea = ref(null);
const decisionsMade = ref(0);
const ideasCount = ref(null);
const ideas = ref(null);
const tempRating = ref(0);

const props = defineProps({
  ideasCount: {
    type: [String, Number],
    required: true,
  },
  ideas: {
    type: Array,
    required: true,
  },
  sessionId: {
    type: [String, Number],
    required: true,
  },
  contributorId: {
    type: [String, Number],
    required: true,
  },
});

const sessionId = toRef(props, 'sessionId');
const contributorId = toRef(props, 'contributorId');

const sendVote = (ideaId, voteValue) => {
  axios
    .post('/api/vote', {
      session_id: sessionId.value,
      idea_id: ideaId,
      contributor_id: contributorId.value,
      vote_type: 'star',
      vote_value: voteValue,
    })
    .then((response) => {
      console.log('Server response:', response.data);
    })
    .catch((error) => {
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
    tempRating.value = 0;
  } else {
    currentIdea.value = null;
  }
  decisionsMade.value = props.ideasCount - ideas.value.length;
};

const undoLastDecision = () => {
  if (previousIdea.value) {
    ideas.value.unshift(previousIdea.value);
    currentIdea.value = previousIdea.value;
    previousIdea.value = null;
    tempRating.value = 0;
    decisionsMade.value = props.ideasCount - ideas.value.length;
  }
};

const rate = (stars) => {
  tempRating.value = stars;
  
  // Send vote to the server
  sendVote(currentIdea.value.id, stars);

  // Entfernen Sie die aktuelle Idee und gehen Sie zur nächsten
  ideas.value.shift();
  setNextIdea();
};

// Touch-Funktionalität
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
  const swipeThreshold = 50;
  const swipeDistance = touchEndX - touchStartX;
  if (Math.abs(swipeDistance) > swipeThreshold) {
    const starRating = Math.min(
      3,
      Math.max(
        1,
        Math.ceil((3 * (swipeDistance + swipeThreshold)) / (2 * swipeThreshold))
      )
    );
    rate(starRating);
  }
};
</script>
