<template>
  <div class="vote__headline__container">
    <h2>Rank the Ideas descending <br>
      <PodiumIcon />
    </h2>
  </div>
  <table v-if="topIdeas.length" class="rankingVote__table">
    <tbody>
      <template v-for="(idea, index) in topIdeas" :key="idea.id">
        <tr :data-index="index"
            :class="{ 'expanded': expandedIds.includes(idea.id), 'dragging': isDragging && draggedIndex === index }">
          <td class="dragAndDrop" 
              @mousedown="startDrag(index, $event)" 
              @touchstart="startDrag(index, $event)"
              :class="{ 'active': isDragging && draggedIndex === index }">
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
        <tr v-if="expandedIds.includes(idea.id)" class="details-row"    :class="{ 'dragging': isDragging && draggedIndex === index }">
          <td @click="toggleDetails(idea.id)" colspan="6">
            <div class="details__container">
              <div v-html="idea.description"></div>
            </div>
          </td>
        </tr>
        <tr class="chevron" @click.stop="toggleDetails(idea.id)"
            :class="{ 'dragging': isDragging && draggedIndex === index }">
          <td colspan="6">
            <div class="chevron-container" :class="{ 'rotated': expandedIds.includes(idea.id) }">▼</div>
          </td>
        </tr>
      </template>
    </tbody>
  </table>
  <div v-if="topIdeas.length" class="ranking-vote__submit__container">
    <button class="primary" @click="submitRanking">Senden</button>
  </div>
  <div v-else>Fertig... Warte auf die anderen Teilnehmer</div>
</template>

<script setup>
import { ref, computed } from 'vue';
import ArrowUpIcon from '../icons/ArrowUpIcon.vue';
import ArrowDownIcon from '../icons/ArrowDownIcon.vue';
import PodiumIcon from '../icons/PodiumIcon.vue';
import IconComponents from '../IconComponents.vue';

const props = defineProps({
  ideas: { type: Object, required: true },
  votes: { type: Object, required: true },
  personalContributor: { type: Object, required: true },
  session: { type: Object, required: true }
});

const emit = defineEmits(['sendVote', 'wait']);

const expandedIds = ref([]);
const currentOrder = ref([]);
const isDragging = ref(false);
const draggedIndex = ref(null);

const topIdeas = computed(() => {
  const currentRound = props.session.vote_round;
  let selectedIdeas = currentRound > 1
    ? [...props.ideas].sort((a, b) => {
        const aVotes = props.votes.filter(v => v.idea_id === a.id && v.round === currentRound - 1);
        const bVotes = props.votes.filter(v => v.idea_id === b.id && v.round === currentRound - 1);
        const aAvg = aVotes.reduce((sum, v) => sum + v.value, 0) / aVotes.length || 0;
        const bAvg = bVotes.reduce((sum, v) => sum + v.value, 0) / bVotes.length || 0;
        return bAvg - aAvg;
      }).slice(0, 5)
    : props.ideas.slice(0, 5);

  const filteredIdeas = selectedIdeas.filter(idea =>
    !props.votes.some(v => v.idea_id === idea.id && v.round === currentRound && v.contributor_id === props.personalContributor.id)
  );

  if (currentOrder.value.length !== filteredIdeas.length) {
    currentOrder.value = filteredIdeas.map(idea => idea.id);
  }

  return filteredIdeas.sort((a, b) => currentOrder.value.indexOf(a.id) - currentOrder.value.indexOf(b.id));
});

const getIconComponent = (iconName) => IconComponents[iconName] || null;

const toggleDetails = (id) => {
  expandedIds.value = expandedIds.value.includes(id) ? [] : [id];
};

const moveUp = (index) => {
  if (index > 0) {
    const newOrder = [...currentOrder.value];
    [newOrder[index - 1], newOrder[index]] = [newOrder[index], newOrder[index - 1]];
    currentOrder.value = newOrder;
  }
};

const moveDown = (index) => {
  if (index < currentOrder.value.length - 1) {
    const newOrder = [...currentOrder.value];
    [newOrder[index], newOrder[index + 1]] = [newOrder[index + 1], newOrder[index]];
    currentOrder.value = newOrder;
  }
};

const startDrag = (index, event) => {
  if (event.type === 'touchstart') {
    event.preventDefault();
  }
  isDragging.value = true;
  draggedIndex.value = index;
  document.addEventListener('mousemove', drag);
  document.addEventListener('touchmove', drag, { passive: false });
  document.addEventListener('mouseup', endDrag);
  document.addEventListener('touchend', endDrag);
};

const drag = (event) => {
  if (!isDragging.value) return;
  event.preventDefault();
  
  const y = event.type.includes('mouse') ? event.clientY : event.touches[0].clientY;
  const rows = document.querySelectorAll('.rankingVote__table tr:not(.details-row):not(.chevron)');
  const draggedRow = rows[draggedIndex.value];
  const draggedDetails = draggedRow.nextElementSibling;
  const draggedChevron = draggedDetails.nextElementSibling;

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const rect = row.getBoundingClientRect();
    const middle = rect.top + rect.height / 2;

    if (y < middle && i < draggedIndex.value) {
      row.parentNode.insertBefore(draggedRow, row);
      row.parentNode.insertBefore(draggedDetails, draggedRow.nextSibling);
      row.parentNode.insertBefore(draggedChevron, draggedDetails.nextSibling);
      updateOrder(draggedIndex.value, i);
      draggedIndex.value = i;
      break;
    } else if (y > middle && i > draggedIndex.value) {
      const nextRow = rows[i + 1];
      if (nextRow) {
        row.parentNode.insertBefore(draggedRow, nextRow);
        row.parentNode.insertBefore(draggedDetails, draggedRow.nextSibling);
        row.parentNode.insertBefore(draggedChevron, draggedDetails.nextSibling);
      } else {
        row.parentNode.appendChild(draggedRow);
        row.parentNode.appendChild(draggedDetails);
        row.parentNode.appendChild(draggedChevron);
      }
      updateOrder(draggedIndex.value, i);
      draggedIndex.value = i;
      break;
    }
  }
};

const endDrag = () => {
  isDragging.value = false;
  draggedIndex.value = null;
  document.removeEventListener('mousemove', drag);
  document.removeEventListener('touchmove', drag);
  document.removeEventListener('mouseup', endDrag);
  document.removeEventListener('touchend', endDrag);
};

const updateOrder = (oldIndex, newIndex) => {
  const newOrder = [...currentOrder.value];
  const [removed] = newOrder.splice(oldIndex, 1);
  newOrder.splice(newIndex, 0, removed);
  currentOrder.value = newOrder;
};

const submitRanking = () => {
  topIdeas.value.forEach((idea, index) => {
    emit('sendVote', {
      ideaId: idea.id,
      voteType: 'ranking',
      voteValue: Math.max(topIdeas.value.length - index, 1)
    });
  });
  emit('wait');
};
</script>

<style scoped>
.rankingVote__table tr:not(.details-row):not(.chevron) {
  cursor: default;
}
.rankingVote__table tr.dragging {
  opacity: 0.5;
  background-color: rgba(0,0,0,0.5);
}
.dragAndDrop {
  cursor: move;
  user-select: none;
}
.dragAndDrop.active svg {
  fill: var(--primary);
}
.chevron {
  cursor: pointer;
}
.chevron div {
  transition: transform 0.3s ease;
}
.chevron .rotated {
  transform: rotate(180deg);
}
.chevron.dragging {
  opacity: 0.5;
  background-color: rgba(0,0,0,0.5);
}
</style>