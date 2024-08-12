<template>
    <section>
      <h2>StarVote</h2>
      <p class="star-vote-icon">★★✰</p>
      <p>Ideas Count: {{ decisionsMade }}/{{ ideasCount }}</p>
      <h3>Rate This Idea From 1-3 Stars</h3>
      <div v-if="currentIdea" class="idea-card" @touchstart="touchStart" @touchend="touchEnd">
        <h4>{{ currentIdea.ideaTitle }}</h4>
        <div v-html="currentIdea.ideaDescription"></div>
        <img :src="currentIdea.contributorIcon" alt="Contributor Icon" width="24" height="24" />
        <p>#{{ currentIdea.tag }}</p>
        <div class="star-rating">
          <button @click="rate(1)" :class="{ active: tempRating >= 1 }">★</button>
          <button @click="rate(2)" :class="{ active: tempRating >= 2 }">★</button>
          <button @click="rate(3)" :class="{ active: tempRating >= 3 }">★</button>
        </div>
      </div>
      <p v-else>No more ideas to rate.</p>
      <button @click="undoLastDecision" :disabled="!previousIdea">↺</button>
    </section>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  
  const fakeIdeaData = ref([
    {
      id: 1,
      ideaTitle: 'Improve User Interface',
      ideaDescription: '<ul><li>Lorem Ipsum Dolor</li><li>Lorem Ipsum Dolor</li><li>Lorem Ipsum Dolor</li></ul>',
      contributorIcon: 'path/to/contributor1.png',
      tag: 'UI/UX'
    },
    {
      id: 2,
      ideaTitle: 'Optimize Performance',
      ideaDescription: '<ul><li>Lorem Ipsum Dolor</li><li>Lorem Ipsum Dolor</li><li>Lorem Ipsum Dolor</li></ul>',
      contributorIcon: 'path/to/contributor2.png',
      tag: 'Performance'
    },
    {
      id: 3,
      ideaTitle: 'Add Dark Mode',
      ideaDescription: '<ul><li>Lorem Ipsum Dolor</li><li>Lorem Ipsum Dolor</li><li>Lorem Ipsum Dolor</li></ul>',
      contributorIcon: 'path/to/contributor3.png',
      tag: 'Feature'
    }
  ]);
  
  const currentIdea = ref(null);
  const previousIdea = ref(null);
  const decisionsMade = ref(0);
  const ideasCount = ref(null);
  const ideas = ref(null);
  const tempRating = ref(0);
  
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
    if (fakeIdeaData.value.length > 0) {
      previousIdea.value = currentIdea.value;
      currentIdea.value = fakeIdeaData.value[0];
      tempRating.value = 0;
    } else {
      currentIdea.value = null;
    }
    decisionsMade.value = props.ideasCount - fakeIdeaData.value.length;
  };
  
  const rate = (stars) => {
    tempRating.value = stars;
    // Hier können Sie die Bewertung speichern oder verarbeiten
    console.log(`Idea rated with ${stars} stars`);
    // Entfernen Sie die aktuelle Idee und gehen Sie zur nächsten
    fakeIdeaData.value.shift();
    setNextIdea();
  };
  
  const undoLastDecision = () => {
    if (previousIdea.value) {
      fakeIdeaData.value.unshift(previousIdea.value);
      currentIdea.value = previousIdea.value;
      previousIdea.value = null;
      tempRating.value = 0;
      decisionsMade.value = props.ideasCount - fakeIdeaData.value.length;
    }
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
      const starRating = Math.min(3, Math.max(1, Math.ceil(3 * (swipeDistance + swipeThreshold) / (2 * swipeThreshold))));
      rate(starRating);
    }
  };
  </script>
  
  <style scoped>
  .idea-card {
    /* Ihre bestehenden Stile */
    touch-action: pan-y;
  }
  
  .star-rating {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
  }
  
  .star-rating button {
    font-size: 2rem;
    background: none;
    border: none;
    cursor: pointer;
    color: #ccc;
    transition: color 0.2s;
  }
  
  .star-rating button.active {
    color: gold;
  }
  </style>