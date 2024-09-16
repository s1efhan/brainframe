<template>
  <div class="vote__headline__container">
    <h2>Rank the Ideas descending <br>🥇🥈🥉</h2>
  </div>
  <table v-if="ideas && ideasCount" class="rankingVote__table">
    <tbody>
      <template v-for="(idea, index) in ideas" :key="idea.id">
        <tr draggable="true" @dragstart="dragStart(index)" @dragover.prevent @drop="drop(index)">
          <td class="dragAndDrop">
            <svg width="80px" height="80px" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"
                d="M9.5 8C10.3284 8 11 7.32843 11 6.5C11 5.67157 10.3284 5 9.5 5C8.67157 5 8 5.67157 8 6.5C8 7.32843 8.67157 8 9.5 8ZM9.5 14C10.3284 14 11 13.3284 11 12.5C11 11.6716 10.3284 11 9.5 11C8.67157 11 8 11.6716 8 12.5C8 13.3284 8.67157 14 9.5 14ZM11 18.5C11 19.3284 10.3284 20 9.5 20C8.67157 20 8 19.3284 8 18.5C8 17.6716 8.67157 17 9.5 17C10.3284 17 11 17.6716 11 18.5ZM15.5 8C16.3284 8 17 7.32843 17 6.5C17 5.67157 16.3284 5 15.5 5C14.6716 5 14 5.67157 14 6.5C14 7.32843 14.6716 8 15.5 8ZM17 12.5C17 13.3284 16.3284 14 15.5 14C14.6716 14 14 13.3284 14 12.5C14 11.6716 14.6716 11 15.5 11C16.3284 11 17 11.6716 17 12.5ZM15.5 20C16.3284 20 17 19.3284 17 18.5C17 17.6716 16.3284 17 15.5 17C14.6716 17 14 17.6716 14 18.5C14 19.3284 14.6716 20 15.5 20Z" />
            </svg>
          </td>
          <td class="index">
            <div>{{ index + 1 }}</div>
          </td>
          <td @click="toggleShowIdeaDetails(idea.id)" class="title">{{ idea.ideaTitle }}</td>
          <td class="contributor__tag">
            <div class="contributor"><component :is="getIconComponent(idea.contributorIcon)" /></div>
            <div class="tag">#{{ idea.tag ? idea.tag : "ideaTag" }}</div>
          </td>
          <td class="buttons">
            <button @click="moveUp(index)">⬆</button>
            <button @click="moveDown(index)">⬇</button>
          </td>
        </tr>
        <tr v-if="activeIdeaId === idea.id">
          <td class="description" colspan="5" v-html="idea.ideaDescription"></td>
        </tr>
      </template>
    </tbody>
  </table>
  <div class="ranking-vote__submit__container">
  <button class="primary" @click="submitRanking">Senden</button>
</div>
</template>

<script setup>
import { ref, onMounted, toRef } from 'vue';
import axios from 'axios';

const props = defineProps({
  ideas: {
    type: [Array, Object],
    required: true
  },
  ideasCount: {
    type: Number,
    required: true
  },votedIdeasCount: {
    type: Number,
    required: true
  },
  sessionId: {
    type: [String, Number],
    required: true
  },
  contributorId: {
    type: [String, Number],
    required: true
  },
  votingPhase: {
    type: Number,
    required:true
  }
});
import IconComponents from '../IconComponents.vue';
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const activeIdeaId = ref(null);
const ideasCount = toRef(props, 'ideasCount');
const ideas = toRef(props, 'ideas');
const emit = defineEmits(['lastVote']);
onMounted(() => {
  if (ideasCount.value && ideas.value) {
    console.log("ideasCount.value", ideasCount.value);
    console.log("ideas.value", JSON.parse(JSON.stringify(ideas.value)));
  }
});
const submitRanking = () => {
  const votes = ideas.value.map((idea, index) => ({
    session_id: props.sessionId,
    idea_id: idea.id,
    contributor_id: props.contributorId,
    vote_type: 'ranking',
    vote_value: Math.max(5 - index, 1), // Ändert die Punktevergabe
    voting_phase: props.votingPhase
  }));

  axios.post('/api/vote', { votes })
    .then(response => {
      console.log('Server response:', response.data);
      emit('lastVote');
    })
    .catch(error => {
      console.error('Fehler beim Speichern der Votes', error);
    });
};

const toggleShowIdeaDetails = (ideaId) => {
  activeIdeaId.value = activeIdeaId.value === ideaId ? null : ideaId;
};

let draggedItemIndex = null;

const dragStart = (index) => {
  draggedItemIndex = index;
};

const drop = (index) => {
  if (draggedItemIndex !== null) {
    const draggedItem = ideas.value.splice(draggedItemIndex, 1)[0];
    ideas.value.splice(index, 0, draggedItem);
    draggedItemIndex = null;
  }
};

const moveUp = (index) => {
  if (index > 0) {
    const temp = ideas.value[index];
    ideas.value[index] = ideas.value[index - 1];
    ideas.value[index - 1] = temp;
  }
};

const moveDown = (index) => {
  if (index < ideas.value.length - 1) {
    const temp = ideas.value[index];
    ideas.value[index] = ideas.value[index + 1];
    ideas.value[index + 1] = temp;
  }
};
</script>
