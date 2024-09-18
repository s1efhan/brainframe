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
  <button class="primary star" @click="rate(1)" @mouseover="hoverStar(1)" @mouseleave="resetStars">★</button>
  <button class="primary star" @click="rate(2)" @mouseover="hoverStar(2)" @mouseleave="resetStars">★</button>
  <button class="primary star" @click="rate(3)" @mouseover="hoverStar(3)" @mouseleave="resetStars">★</button>
</div>
    <div class="idea-card__bottom">
      <button @click="undoLastDecision" class="secondary undo" :disabled="!previousIdea">↺</button>
      <div class="tag">#{{ currentIdea.tag }}</div>
      <div class="contributor__icon">
        <ProfileIcon />
        <component :is="getIconComponent(currentIdea.contributorIcon)" />
      </div>
    </div>
  </div>
  <p v-else>Fertig. Du musst warten, bis der Rest fertig mit Voten ist.</p>
  <div v-if="ideasCount" class="ideasCount">
    {{ decisionsMade }}/{{ ideasCount + votedIdeasCount }}
  </div>
</template>

<script setup>
import { ref, onMounted, toRef } from 'vue';
import axios from 'axios';
import IconComponents from '../IconComponents.vue';
import ProfileIcon from '../icons/ProfileIcon.vue';
const props = defineProps({
  ideasCount: {
    type: [String, Number],
    required: true,
  },
  votedIdeasCount: {
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
const emit = defineEmits(['lastVote']);
const currentIdea = ref(null);
const previousIdea = ref(null);
const decisionsMade = ref(props.votedIdeasCount);
const ideasCount = ref(null);
const ideas = ref(null);
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const sessionId = toRef(props, 'sessionId');
const contributorId = toRef(props, 'contributorId');
const votingPhase = toRef(props, 'votingPhase');
const hoverStar = (starNumber) => {
  const stars = document.querySelectorAll('.star');
  stars.forEach((star, index) => {
    star.classList.toggle('active', index < starNumber);
  });
};

const resetStars = () => {
  const stars = document.querySelectorAll('.star');
  stars.forEach(star => star.classList.remove('active'));
};

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
  } else if (typeof ideas.value === 'object' && Object.keys(ideas.value).length > 0) {
    const nextKey = Object.keys(ideas.value)[0];
    previousIdea.value = currentIdea.value;
    currentIdea.value = ideas.value[nextKey];
    delete ideas.value[nextKey];
  } else {
    emit('lastVote');
    currentIdea.value = null;
  }
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
    decisionsMade.value--;
  }
};

const rate = (stars) => {
  sendVote(currentIdea.value.id, stars);
  decisionsMade.value++;
  setNextIdea();
};
</script>