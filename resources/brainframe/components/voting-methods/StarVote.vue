<template>
  <div class="vote__headline__container">
    <h2>Rate This Idea <br>★★✰</h2>
  </div>
  <div v-if="currentIdea" class="idea-card star-vote">
    <h3>{{ currentIdea.title }}</h3>
    <div class="idea__description__container">
      <div v-html="currentIdea.description"></div>
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
  <div class="ideasCount">
  {{ votedIdeas }}/{{ totalIdeasCount }}
</div>
</template>

<script setup>
import { ref, onMounted, toRef } from 'vue';
import axios from 'axios';
import IconComponents from '../IconComponents.vue';
import ProfileIcon from '../icons/ProfileIcon.vue';
const props = defineProps({
  ideas: {
    type: Object,
    required: true
  },
  votes: {
    type: Object,
    required: true
  },
  personalContributor: {
    type: Object,
    required: true
  },
  session: {
    type: Object,
    required: true
  }
});
const totalIdeasCount = ref(0);
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const ideas = toRef(props, 'ideas');
const votes = toRef(props, 'votes');
const personalContributor = toRef(props, 'personalContributor');
const session = toRef(props, 'session');

const votedIdeas = ref(0);
const currentIdea = ref(null);
const previousIdea = ref(null);
const ideasToVote = ref([]);
const initialize = () => {
  // Konvertiere votes in ein besser nutzbares Format
  const votesMap = votes.value.reduce((acc, vote) => {
    if (!acc[vote.idea_id]) {
      acc[vote.idea_id] = [];
    }
    acc[vote.idea_id].push(vote);
    return acc;
  }, {});

  // Filtere Ideen, die in dieser Runde vom aktuellen Contributor noch nicht bewertet wurden
  ideasToVote.value = ideas.value.filter(idea => 
    !votesMap[idea.id] || 
    !votesMap[idea.id].some(vote => 
      vote.contributor_id === personalContributor.value.id && 
      vote.round === session.value.vote_round
    )
  );

  totalIdeasCount.value = ideas.value.length;
  
  votedIdeas.value = totalIdeasCount.value - ideasToVote.value.length;

  setNextIdea();
};

const hoverStar = (starNumber) => {
  const stars = document.querySelectorAll('.star');
  stars.forEach((star, index) => {
    star.classList.toggle('active', index < starNumber);
  });
};
const emit = defineEmits(['sendVote', 'wait']);
const resetStars = () => {
  const stars = document.querySelectorAll('.star');
  stars.forEach(star => star.classList.remove('active'));
};

onMounted(() => {
  initialize();
});

const setNextIdea = () => {
  if (ideasToVote.value.length > 0) {
    previousIdea.value = currentIdea.value;
    currentIdea.value = ideasToVote.value.shift();
    console.log("idea: Star: ",currentIdea.value);
  } else {
    emit('wait');
    currentIdea.value = null;
  }
};


const undoLastDecision = () => {
  if (previousIdea.value) {
    ideasToVote.value.unshift(currentIdea.value);
    currentIdea.value = previousIdea.value;
    previousIdea.value = null;
    votedIdeas.value--;
  }
};
const rate = (stars) => {
  console.log("rate", currentIdea.value.id, stars);
  emit('sendVote', { ideaId: currentIdea.value.id, voteType: 'star', voteValue: stars });
  votedIdeas.value++;
  setNextIdea();
};
</script>