<template>
  <div class="vote__headline__container">
    <h2>Gefällt dir die Idee?<SwipeIcon/></h2>
  </div>
  <div v-if="currentIdea" class="idea-card" @touchstart="touchStart" @touchend="touchEnd">
    <h3>{{ currentIdea.title }}</h3>
    <div class="idea__description__container">
      <button class="swipe__arrow__left secondary" @click="swipeLeft"><ArrowLeftIcon/> Dislike</button>
      <div class="idea__description" v-html="currentIdea.description || currentIdea.text_input"></div>
      <button class="swipe__arrow__right secondary" @click="swipeRight">Like<ArrowRightIcon/></button>
    </div>
    <div class="idea-card__bottom">
      <button @click="undoLastDecision" class="secondary undo" :disabled="!previousIdea">↺</button>
      <div class="tag" v-if="currentIdea.tag">#{{ currentIdea.tag }}</div>
      <div class="contributor__icon"><ProfileIcon/></div>
    </div>
  </div>
  <p v-else>Fertig. Du musst warten, bis der Rest fertig mit Voten ist.</p>
  <div class="ideasCount">
    {{ votedIdeas }}/{{ totalIdeasCount }}
  </div>
</template>

<script setup>
import { ref, onMounted, toRef} from 'vue';
import ProfileIcon from '../icons/ProfileIcon.vue';
import SwipeIcon from '../icons/SwipeIcon.vue';
import ArrowLeftIcon from '../icons/ArrowLeftIcon.vue';
import ArrowRightIcon from '../icons/ArrowRightIcon.vue';

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
const currentIdea = ref(null);
const previousIdea = ref(null);
const ideasToVote = ref([]);

const initialize = () => {
  const votesMap = votes.value.reduce((acc, vote) => {
    if (!acc[vote.idea_id]) {
      acc[vote.idea_id] = [];
    }
    acc[vote.idea_id].push(vote);
    return acc;
  }, {});

  let relevantIdeas = ideas.value;

  // Nur wenn nicht die erste Abstimmungsrunde ist
  if (session.value.vote_round !== 1) {
    // Berechne den durchschnittlichen vote_value für jede Idee
    const avgVoteValues = ideas.value.map(idea => {
      const ideaVotes = votesMap[idea.id] || [];
      const avgVoteValue = ideaVotes.reduce((sum, vote) => sum + vote.vote_value, 0) / (ideaVotes.length || 1);
      return { id: idea.id, avgVoteValue };
    });

    // Sortiere Ideen nach durchschnittlichem vote_value und wähle die oberen 50%
    const sortedIdeas = avgVoteValues.sort((a, b) => b.avgVoteValue - a.avgVoteValue);
    const topHalfIds = new Set(sortedIdeas.slice(0, Math.ceil(sortedIdeas.length / 2)).map(idea => idea.id));
    relevantIdeas = ideas.value.filter(idea => topHalfIds.has(idea.id));
  }

  ideasToVote.value = relevantIdeas.filter(idea =>
    !votesMap[idea.id] ||
    !votesMap[idea.id].some(vote =>
      vote.contributor_id === personalContributor.value.id &&
      vote.round === session.value.vote_round
    )
  );

  totalIdeasCount.value = relevantIdeas.length;
  votedIdeas.value = totalIdeasCount.value - ideasToVote.value.length;
  setNextIdea();
};

const setNextIdea = () => {
  if (ideasToVote.value.length > 0) {
    previousIdea.value = currentIdea.value;
    currentIdea.value = ideasToVote.value.shift();
  } else {
    emit('wait');
    currentIdea.value = null;
  }
};

const vote = (voteValue) => {
  emit('sendVote', { ideaId: currentIdea.value.id, voteType: 'swipe', voteValue: voteValue });
  votedIdeas.value++;
  setNextIdea();
};

const swipeLeft = () => {
  vote(0);
};

const swipeRight = () => {
  vote(1);
};

const undoLastDecision = () => {
  if (previousIdea.value) {
    ideasToVote.value.unshift(currentIdea.value);
    currentIdea.value = previousIdea.value;
    previousIdea.value = null;
    votedIdeas.value--;
  }
};

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
  if (touchEndX < touchStartX - swipeThreshold) {
    swipeLeft();
  } else if (touchEndX > touchStartX + swipeThreshold) {
    swipeRight();
  }
};

onMounted(() => {
  initialize();
});
</script>