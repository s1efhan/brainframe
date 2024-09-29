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
        <button class="primary" @click="selectIdea(0)">L</button>
      </div>
      <div class="idea-card__bottom tag">
        <p><component :is="getIconComponent(currentPair[0].contributorIcon)" /></p>
        <p>#{{ currentPair[0].tag }}</p>
      </div>
    </div>
    <div class="idea-card">
      <h3>{{ currentPair[1].ideaTitle }}</h3>
      <div class="idea__description__container">
        <div class="idea__description" v-html="currentPair[1].ideaDescription"></div>
      </div>
      <div class="idea-card__bottom button">
        <button class="primary" @click="selectIdea(1)">R</button>
      </div>
      <div class="idea-card__bottom tag">
        <p>{{currentPair[1].contributorIcon }}</p>
        <p>#{{ currentPair[1].tag }}</p>
      </div>
    </div>
  </div>
  <button class="secondary undo" @click="undoLastDecision" :disabled="previousPair.length === 0">↺</button>
  <div v-if="ideasCount" class="ideasCount">
    {{ decisionsMade }}/{{ ideasCount + props.votedIdeasCount}}
  </div>
</template>

<script setup>
import { ref, onMounted, toRef} from 'vue';
import axios from 'axios';
import IconComponents from '../IconComponents.vue';
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};

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

const currentPair = ref([]);
const previousPair = ref([]);
const decisionsMade = ref(props.votedIdeasCount);

const setNextPair = () => {
  if (ideas.value.length >= 2) {
    previousPair.value = [...currentPair.value];
    currentPair.value = ideas.value.slice(0, 2);
    decisionsMade.value = props.votedIdeasCount;
  } else {
    emit('wait');
    currentPair.value = [];
  }
};
const emit = defineEmits(['sendVote', 'wait']);

const selectIdea = (selectedIndex) => {
  const selectedIdea = currentPair.value[selectedIndex];
  const unselectedIdea = currentPair.value[1 - selectedIndex];
  emit('sendVote', { ideaId: selectedIdea.id, voteType: 'LeftRightVote', voteValue: 1 });
  emit('sendVote', { ideaId: unselectedIdea.id, voteType: 'LeftRightVote', voteValue: 0 });
  ideas.value.splice(0, 2);  // Entfernt beide Ideen aus dem Array
  setNextPair();
};

const undoLastDecision = () => {
  if (previousPair.value.length === 2) {
    ideas.value.unshift(...previousPair.value);
    currentPair.value = [...previousPair.value];
    previousPair.value = [];
    decisionsMade.value = props.votedIdeasCount + (ideasCount.value - ideas.value.length);
  }
};

onMounted(() => {
  setNextPair();
  console.log("ContributorId: Pops", props.contributorId);
  console.log("ideas.value, ideasCount.value", ideas.value, ideasCount.value)
});

</script>