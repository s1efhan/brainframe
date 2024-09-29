<template>
  <div class="vote__headline__container">
    <h2>Swipe to Vote <SwipeIcon/></h2>
  </div>
  <div v-if="currentIdea" class="idea-card" @touchstart="touchStart" @touchend="touchEnd">
    <h3>{{ currentIdea.ideaTitle }}</h3>
    <div class="idea__description__container">
      <button class="swipe__arrow__left secondary" @click="swipeLeft"><ArrowLeftIcon/><DislikeIcon/></button>
      <div class="idea__description" v-html="currentIdea.ideaDescription"></div>
      <button class="swipe__arrow__right secondary" @click="swipeRight"><ArrowRightIcon/><LikeIcon/></button>
    </div>
    <div class="idea-card__bottom">
      <button @click="undoLastDecision" class="secondary undo":disabled="!previousIdea">↺</button>
      <div class="tag">#{{ currentIdea.tag }}</div>
      <div class="contributor__icon"> <ProfileIcon/><component :is="getIconComponent(currentIdea.contributorIcon)" /></div>
    </div>
  </div>
  <p v-else>Fertig. Du musst warten, bis der Rest fertig mit Voten ist.</p>

  <div v-if="ideasCount" class="ideasCount">
    {{ decisionsMade }}/{{ ideasCount +  props.votedIdeasCount}}
  </div>
</template>

<script setup>
import { ref, onMounted, toRef } from 'vue';
import ProfileIcon from '../icons/ProfileIcon.vue';
import axios from 'axios';
import SwipeIcon from '../icons/SwipeIcon.vue';
import ArrowLeftIcon from '../icons/ArrowLeftIcon.vue';
import DislikeIcon from '../icons/DislikeIcon.vue';
import LikeIcon from '../icons/LikeIcon.vue';
import ArrowRightIcon from '../icons/ArrowRightIcon.vue';
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

import IconComponents from '../IconComponents.vue';
const currentIdea = ref(null);
const previousIdea = ref(null);
const decisionsMade = ref(props.votedIdeasCount);
const ideasCount = ref(null);
const ideas = ref([]);
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};

onMounted(() => {
  ideasCount.value = props.ideasCount;
  ideas.value = props.ideas;
  setNextIdea();
});
const emit = defineEmits(['sendVote', 'wait']);
const setNextIdea = () => {
  if (ideas.value.length > 0) {
    previousIdea.value = currentIdea.value;
    currentIdea.value = ideas.value[0];
  } else {
    emit('wait');
    currentIdea.value = null;
  }
  decisionsMade.value = props.votedIdeasCount + (props.ideasCount - ideas.value.length);
};

const swipeLeft = () => {
// Sende "Nicht mögen" Abstimmung
  emit('sendVote', { ideaId: currentIdea.value.id, voteType: 'SwipeVote', voteValue: 0 });
  ideas.value.shift();
  setNextIdea();
};
const swipeRight = () => {
  // "Mögen" Logic
  emit('sendVote', { ideaId: currentIdea.value.id, voteType: 'SwipeVote', voteValue: 1 });
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
