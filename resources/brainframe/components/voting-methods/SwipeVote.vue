<template>
    <section>
      <h2>SwipeVote</h2>
      <p class="swipe-mode-icon">🔥</p>
      <p>Ideas Count: {{ decisionsMade }}/{{ ideasCount }}</p>
      <h3>Pick Your Preferred Idea</h3>
      <div v-if="currentIdea" class="idea-card" @touchstart="touchStart" @touchend="touchEnd">
        <h4>{{ currentIdea.ideaTitle }}</h4>
        <div v-html="currentIdea.ideaDescription"></div>
        <p >{{currentIdea.contributorIcon}}</p>
        <p>#{{ currentIdea.tag }}</p>
          <button @click="swipeLeft">←</button>
          <button @click="swipeRight">→</button>
      </div>
      <p v-else>No more ideas to swipe.</p>
      <button @click="undoLastDecision" :disabled="!previousIdea">↺</button>
    </section>
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
