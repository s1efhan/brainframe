<template>
  <div class="vote__headline__container">
    <h2>Rate This Idea <br>★★✰</h2>
  </div>
  <div v-if="currentIdea" class="idea-card star-vote">
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
import ProfileIcon from '../icons/ProfileIcon.vue';
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
  votingPhase: {
    type: Number,
    required: true,
  },
});

const sessionId = toRef(props, 'sessionId');
const contributorId = toRef(props, 'contributorId');
const votingPhase = toRef(props, 'votingPhase');

const sendVote = (ideaId, voteValue) => {
  console.log('Sending vote:', sessionId.value, voteValue, ideaId, contributorId.value, 'star', voteValue);
  axios.post('/api/vote', {
    votes: [
      {
        session_id: sessionId.value,
        idea_id: ideaId,
        contributor_id: contributorId.value,
        vote_type: 'star',
        vote_value: voteValue,
        voting_phase: votingPhase.value
      },
    ],
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
  ideas.value = Array.isArray(props.ideas) ? [...props.ideas] : { ...props.ideas };
  console.log(ideas.value);
  setNextIdea();
});

const setNextIdea = () => {
  if (Array.isArray(ideas.value) && ideas.value.length > 0) {
    previousIdea.value = currentIdea.value;
    currentIdea.value = ideas.value.shift();
    tempRating.value = 0;
  } else if (typeof ideas.value === 'object' && Object.keys(ideas.value).length > 0) {
    const nextKey = Object.keys(ideas.value)[0];
    previousIdea.value = currentIdea.value;
    currentIdea.value = ideas.value[nextKey];
    delete ideas.value[nextKey];
    tempRating.value = 0;
  } else {
    currentIdea.value = null;
  }
  updateDecisionsMade();
};

const updateDecisionsMade = () => {
  const remainingIdeas = Array.isArray(ideas.value) ? ideas.value.length : Object.keys(ideas.value).length;
  decisionsMade.value = props.ideasCount - remainingIdeas;
};

const undoLastDecision = () => {
  if (previousIdea.value) {
    if (Array.isArray(ideas.value)) {
      ideas.value.unshift(currentIdea.value);
    } else {
      ideas.value[currentIdea.value.id] = currentIdea.value;
    }
    currentIdea.value = previousIdea.value;
    previousIdea.value = null;
    tempRating.value = 0;
    updateDecisionsMade();
  }
};

const rate = (stars) => {
  tempRating.value = stars;
  sendVote(currentIdea.value.id, stars);
  setNextIdea();
};
</script>