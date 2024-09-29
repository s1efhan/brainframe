<template>
  <div class="vote__headline__container">
    <h2>Rank the Ideas descending <br><PodiumIcon/></h2>
  </div>
  <table v-if="topIdeas.length" class="rankingVote__table">
    <tbody>
      <template v-for="(idea, index) in topIdeas" :key="idea.id">
        <tr :data-index="index" draggable="true" @dragstart="dragStart(index, $event)"
          @dragover="dragOver(index, $event)" @drop="drop(index, $event)" @dragend="dragEnd($event)"
          @touchstart="touchStart(index, $event)" @touchmove="touchMove" @touchend="touchEnd"
          :class="{ 'expanded': expandedIds.includes(idea.id) }">
          <td class="dragAndDrop">
            <svg width="80px" height="80px" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"
                d="M9.5 8C10.3284 8 11 7.32843 11 6.5C11 5.67157 10.3284 5 9.5 5C8.67157 5 8 5.67157 8 6.5C8 7.32843 8.67157 8 9.5 8ZM9.5 14C10.3284 14 11 13.3284 11 12.5C11 11.6716 10.3284 11 9.5 11C8.67157 11 8 11.6716 8 12.5C8 13.3284 8.67157 14 9.5 14ZM11 18.5C11 19.3284 10.3284 20 9.5 20C8.67157 20 8 19.3284 8 18.5C8 17.6716 8.67157 17 9.5 17C10.3284 17 11 17.6716 11 18.5ZM15.5 8C16.3284 8 17 7.32843 17 6.5C17 5.67157 16.3284 5 15.5 5C14.6716 5 14 5.67157 14 6.5C14 7.32843 14.6716 8 15.5 8ZM17 12.5C17 13.3284 16.3284 14 15.5 14C14.6716 14 14 13.3284 14 12.5C14 11.6716 14.6716 11 15.5 11C16.3284 11 17 11.6716 17 12.5ZM15.5 20C16.3284 20 17 19.3284 17 18.5C17 17.6716 16.3284 17 15.5 17C14.6716 17 14 17.6716 14 18.5C14 19.3284 14.6716 20 15.5 20Z" />
            </svg>
          </td>
          <td class="index">
            <div>{{ index + 1 }}</div>
          </td>
          <td @click="toggleDetails(idea.id)" class="title">{{ idea.title }}</td>
          <td class="contributor__tag">
            <div class="contributor">
              <component :is="getIconComponent(idea.contributorIcon)" />
            </div>
            <div class="tag">#{{ idea.tag ? idea.tag : "ideaTag" }}</div>
          </td>
          <td class="buttons">
            <button @click.stop="moveUp(index)">
              <ArrowUpIcon />
            </button>
            <button @click.stop="moveDown(index)">
              <ArrowDownIcon />
            </button>
          </td>
        </tr>
        <tr v-if="expandedIds.includes(idea.id)" class="details-row">
          <td  @click="toggleDetails(idea.id)" colspan="6">
            <div class="details__container">
            <div v-html="idea.description"></div></div>
          </td>
        </tr>
        <tr class="chevron" @click.stop="toggleDetails(idea.id)">
          <td colspan="6">
            <div class="chevron-container" :class="{ 'rotated': expandedIds.includes(idea.id) }">▼</div>
          </td>
        </tr>
      </template>
    </tbody>
  </table>
  <div v-if ="topIdeas"class="ranking-vote__submit__container">
    <button class="primary" @click="submitRanking">Senden</button>
  </div>
  <div v-else>Fertig... Warte auf die anderen Teilnehmer</div>
</template>

<script setup>
import { ref, toRef, watchEffect } from 'vue';
import ArrowUpIcon from '../icons/ArrowUpIcon.vue';
import ArrowDownIcon from '../icons/ArrowDownIcon.vue';
import PodiumIcon from '../icons/PodiumIcon.vue';
import IconComponents from '../IconComponents.vue';

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
const ideas = toRef(props, 'ideas');
const votes = toRef(props, 'votes');
const personalContributor = toRef(props, 'personalContributor');
const session = toRef(props, 'session');
const expandedIds = ref([]);
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const dragOver = (index, event) => {
  event.preventDefault();
  const draggedOverItem = event.target.closest('tr');
  if (draggedOverItem && !draggedOverItem.classList.contains('dragging')) {
    // Entfernen Sie die Klasse von allen anderen Zeilen
    document.querySelectorAll('tr').forEach(row => row.classList.remove('drag-over'));
    // Fügen Sie die Klasse zur aktuellen Zeile hinzu
    draggedOverItem.classList.add('drag-over');
  }
};
const emit = defineEmits(['sendVote', 'wait']);
const submitRanking = () => {
  topIdeas.value.forEach((idea, index) => {
    emit('sendVote', { 
      ideaId: idea.id, 
      voteType: 'ranking', 
      voteValue: Math.max(topIdeas.value.length - index, 1) 
    });
  });
  topIdeas.value = [];
  emit('wait');
};


const toggleDetails = (id) => {
  if (expandedIds.value.includes(id)) {
    expandedIds.value = [];
  } else {
    expandedIds.value = [id];
  }
};


const moveUp = (index) => {
  if (index > 0) {
    const newArray = [...topIdeas.value];
    [newArray[index - 1], newArray[index]] = [newArray[index], newArray[index - 1]];
    topIdeas.value = newArray;
  }
};

const moveDown = (index) => {
  if (index < topIdeas.value.length - 1) {
    const newArray = [...topIdeas.value];
    [newArray[index], newArray[index + 1]] = [newArray[index + 1], newArray[index]];
    topIdeas.value = newArray;
  }
};

const topIdeas = ref([]);
watchEffect(() => {
  const currentRound = session.value.vote_round;

  let selectedIdeas;

  if (currentRound > 1) {
    const prevRound = currentRound - 1;
    
    // Sortiere die Ideen basierend auf den Votes der vorherigen Runde
    const sortedIdeas = [...ideas.value].sort((a, b) => {
      const aVotes = votes.value.filter(v => v.idea_id === a.id && v.round === prevRound);
      const bVotes = votes.value.filter(v => v.idea_id === b.id && v.round === prevRound);
      const aAvg = aVotes.reduce((sum, v) => sum + v.value, 0) / aVotes.length || 0;
      const bAvg = bVotes.reduce((sum, v) => sum + v.value, 0) / bVotes.length || 0;
      return bAvg - aAvg;
    });
    
    // Wähle die Top 5 Ideen aus
    selectedIdeas = sortedIdeas.slice(0, 5);
  } else {
    // Für die erste Runde: Nehme einfach die 5 Ideen
    selectedIdeas = ideas.value.slice(0, 5);
  }

  // Filtere die ausgewählten Ideen, für die der aktuelle Contributor in dieser Runde noch nicht abgestimmt hat
  topIdeas.value = selectedIdeas.filter(idea => 
    !votes.value.some(v => 
      v.idea_id === idea.id && 
      v.round === currentRound && 
      v.contributor_id === personalContributor.value.id
    )
  );
  if (topIdeas.value.length < 1){
    emit('wait');
  }
});

// Touch functionality
const isDragging = ref(false);
const draggedItem = ref(null);
const draggedItemIndex = ref(null);

const touchStart = (index, event) => {
  isDragging.value = true;
  draggedItemIndex.value = index;
  draggedItem.value = topIdeas.value[index];
  event.target.closest('tr').classList.add('dragging');
};

const touchMove = (event) => {
  if (!isDragging.value) return;
  event.preventDefault();
  const touch = event.touches[0];
  const moveTarget = document.elementFromPoint(touch.clientX, touch.clientY);
  const tableRow = moveTarget?.closest('tr');

  if (tableRow && tableRow.dataset.index) {
    const newIndex = parseInt(tableRow.dataset.index);
    if (newIndex !== draggedItemIndex.value) {
      const removedItem = topIdeas.value.splice(draggedItemIndex.value, 1)[0];
      topIdeas.value.splice(newIndex, 0, removedItem);
      draggedItemIndex.value = newIndex;
    }
  }
};

const touchEnd = (event) => {
  if (draggedItem.value) {
    isDragging.value = false;
    event.target.closest('tr')?.classList.remove('dragging');
    draggedItem.value = null;
    draggedItemIndex.value = null;
  }
};

// Drag and drop functionality for desktop
const dragStart = (index, event) => {
  draggedItemIndex.value = index;
  event.target.closest('tr').classList.add('dragging');
};
const drop = (index, event) => {
  if (draggedItemIndex.value !== null) {
    const draggedItem = topIdeas.value.splice(draggedItemIndex.value, 1)[0];
    topIdeas.value.splice(index, 0, draggedItem);
    draggedItemIndex.value = null;

    document.querySelectorAll('tr').forEach(row => {
      row.classList.remove('dragging', 'drag-over');
    });
  }
};

const dragEnd = (event) => {
  // Entfernen Sie die Klassen von allen Zeilen
  document.querySelectorAll('tr').forEach(row => {
    row.classList.remove('dragging', 'drag-over');
  });
};
</script>
<style scoped>
/* Fügen Sie hier die notwendigen Stile hinzu */

.chevron {
  cursor: pointer;
}

.chevron div {
  transition: transform 0.3s ease;
}

.chevron .rotated {
  transform: rotate(180deg);
}
</style>