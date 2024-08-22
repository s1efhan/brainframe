<template>
  <h2>Swipe to Vote 🔥</h2>
  <div v-if="currentIdea" class="idea-card" @touchstart="touchStart" @touchend="touchEnd">
    <h3>{{ currentIdea.ideaTitle }}</h3>
    <div class="idea__description__container">
      <button class="swipe__arrow__left secondary" @click="swipeLeft">←</button>
      <div class="idea__description" v-html="currentIdea.ideaDescription"></div>
      <button class="swipe__arrow__right secondary" @click="swipeRight">→</button>
    </div>
    <div class="idea-card__bottom">
      <button @click="undoLastDecision" class="secondary":disabled="!previousIdea">↺</button>
      <div class="tag">#{{ currentIdea.tag }}</div>
      <div class="contributor__icon">{{currentIdea.contributorIcon}}</div>
    </div>
  </div>
  <p v-else>Fertig. Du musst warten, bis der Rest fertig mit Voten ist.</p>

  <div v-if="ideasCount" class="ideasCount">
    {{ decisionsMade }}/{{ ideasCount }}
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const currentIdea = ref(null);
const previousIdea = ref(null);
const decisionsMade = ref(0);
const ideasCount = ref(null);
const ideas = ref(null);

const props = defineProps({
  ideasCount: {
    type: [String, Number],
    required: true
  },
  ideas: {
    type: Array,
    required: true
  }
});

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
  // Logik für "Nicht mögen"
  ideas.value.shift();
  setNextIdea();
};

const swipeRight = () => {
  // Logik für "Mögen"
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
