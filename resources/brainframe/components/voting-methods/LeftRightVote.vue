<template>
  <div class="vote__headline__container">
    <h2>Pick Left or Right to Vote <br>↔</h2>
  </div>
  <div v-if="currentPair.length === 2" class="left-right__container">
    <div v-for="(idea, index) in currentPair" :key="idea.id" class="idea-card">
      <h3>{{ idea.text_input }}</h3>
      <div class="idea__description__container">
        <div class="idea__description" v-html="idea.description || idea.text_input"></div>
      </div>
      <div class="idea-card__bottom">
        <button class="primary" @click="selectIdea(index)">{{ index === 0 ? 'L' : 'R' }}</button>
        <div class="tag" v-if="idea.tag">#{{ idea.tag }}</div>
        <div class="contributor__icon"><ProfileIcon /></div>
      </div>
    </div>
  </div>
  <button @click="undoLastDecision" class="secondary undo" :disabled="!previousPair.length">↺</button>
  <p v-if="!currentPair.length">Fertig. Du musst warten, bis der Rest fertig mit Voten ist.</p>
  <div class="ideasCount">
    {{ votedIdeas }}/{{ totalIdeasCount }}
  </div>
</template>

<script setup>
import { ref, onMounted, toRef } from 'vue';
import ProfileIcon from '../icons/ProfileIcon.vue';

const props = defineProps({
  ideas: {
    type: Array,
    required: true
  },
  votes: {
    type: Array,
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

const emit = defineEmits(['sendVote', 'wait']);

const ideas = toRef(props, 'ideas');
const votes = toRef(props, 'votes');
const personalContributor = toRef(props, 'personalContributor');
const session = toRef(props, 'session');

const totalIdeasCount = ref(0);
const votedIdeas = ref(0);
const currentPair = ref([]);
const previousPair = ref([]);
const ideasToVote = ref([]);

const initialize = () => {
  const votesMap = votes.value.reduce((acc, vote) => {
    if (!acc[vote.idea_id]) {
      acc[vote.idea_id] = [];
    }
    acc[vote.idea_id].push(vote);
    return acc;
  }, {});

  ideasToVote.value = ideas.value.filter(idea =>
    !votesMap[idea.id] ||
    !votesMap[idea.id].some(vote =>
      vote.contributor_id === personalContributor.value.id &&
      vote.round === session.value.vote_round
    )
  );

  totalIdeasCount.value = ideas.value.length;
  votedIdeas.value = totalIdeasCount.value - ideasToVote.value.length;
  setNextPair();
};

onMounted(() => {
  initialize();
});

const setNextPair = () => {
  if (ideasToVote.value.length >= 2) {
    previousPair.value = [...currentPair.value];
    currentPair.value = ideasToVote.value.slice(0, 2);
    ideasToVote.value = ideasToVote.value.slice(2);
  } else {
    emit('wait');
    currentPair.value = [];
  }
};

const selectIdea = (selectedIndex) => {
  const selectedIdea = currentPair.value[selectedIndex];
  const unselectedIdea = currentPair.value[1 - selectedIndex];
  
  emit('sendVote', { ideaId: selectedIdea.id, voteType: 'left_right', voteValue: 1 });
  emit('sendVote', { ideaId: unselectedIdea.id, voteType: 'left_right', voteValue: 0 });
  
  votedIdeas.value += 2;
  setNextPair();
};

const undoLastDecision = () => {
  if (previousPair.value.length === 2) {
    ideasToVote.value.unshift(...currentPair.value);
    currentPair.value = [...previousPair.value];
    previousPair.value = [];
    votedIdeas.value -= 2;
  }
};
</script>