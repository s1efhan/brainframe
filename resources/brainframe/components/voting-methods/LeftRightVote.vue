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
        <button  class="primary" @click="selectIdea(0)">L</button>
      </div>
      <div class="idea-card__bottom tag">
        <p> {{currentPair[0].contributorIcon }}</p>
        <p>#{{ currentPair[0].tag }}</p>
      </div>
    </div>
    <div class="idea-card">
      <h3>{{ currentPair[1].ideaTitle }}</h3>
      <div class="idea__description__container">
        <div class="idea__description" v-html="currentPair[1].ideaDescription"></div>
      </div>
      <div  class="idea-card__bottom button"> <button class="primary" @click="selectIdea(1)">R</button></div>
      <div class="idea-card__bottom tag">
        <p> {{currentPair[1].contributorIcon }}</p>
        <p>#{{ currentPair[1].tag }}</p>
      </div>
    </div>
  </div>
  
  <p v-else>Fertig. Du musst warten, bis der Rest fertig mit Voten ist.</p>
  <button  class="secondary undo" @click="undoLastDecision" :disabled="previousPair.length === 0">↺</button>
  <div v-if="ideasCount" class="ideasCount">
    {{ decisionsMade }}/{{ ideasCount }}
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const currentPair = ref([]);
const previousPair = ref([]); // Um das letzte Paar zu speichern
const decisionsMade = ref(0);

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

const ideasCount = ref(null);
const ideas = ref(null);

onMounted(() => {
  ideasCount.value = props.ideasCount;
  ideas.value = props.ideas;
  setNextPair();
});

const setNextPair = () => {
  if (ideas.value.length >= 2) {
    previousPair.value = [...currentPair.value]; // Speichert das aktuelle Paar für das Rückgängig machen
    currentPair.value = ideas.value.slice(0, 2);
    decisionsMade.value = props.ideasCount - ideas.value.length;
  } else {
    currentPair.value = [];
  }
};

const selectIdea = (selectedIndex) => {
  // Entfernt die nicht ausgewählte Idee
  ideas.value.splice(1 - selectedIndex, 1);
  setNextPair();
};

const undoLastDecision = () => {
  if (previousPair.value.length === 2) {
    // Setzt das vorherige Paar zurück
    ideas.value.unshift(previousPair.value[1 - ideas.value.length]);
    currentPair.value = [...previousPair.value];
    previousPair.value = [];
    decisionsMade.value = props.ideasCount - ideas.value.length;
  }
};
</script>