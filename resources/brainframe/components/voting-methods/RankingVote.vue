<template>
  <section>
    <h2>RankingVote</h2>
    <p class="ranking-mode-icon"> 🥇🥈🥉</p>
    <p>Ideas Count: {{ ideasCount }}</p>
    <h3>Ideas:</h3>
    <table>
      <tbody>
        <tr v-for="(idea, index) in fakeIdeaData" :key="idea.id" draggable="true" @dragstart="dragStart(index)" @dragover.prevent @drop="drop(index)">
          <td>
            <svg width="80px" height="80px" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd"
                d="M9.5 8C10.3284 8 11 7.32843 11 6.5C11 5.67157 10.3284 5 9.5 5C8.67157 5 8 5.67157 8 6.5C8 7.32843 8.67157 8 9.5 8ZM9.5 14C10.3284 14 11 13.3284 11 12.5C11 11.6716 10.3284 11 9.5 11C8.67157 11 8 11.6716 8 12.5C8 13.3284 8.67157 14 9.5 14ZM11 18.5C11 19.3284 10.3284 20 9.5 20C8.67157 20 8 19.3284 8 18.5C8 17.6716 8.67157 17 9.5 17C10.3284 17 11 17.6716 11 18.5ZM15.5 8C16.3284 8 17 7.32843 17 6.5C17 5.67157 16.3284 5 15.5 5C14.6716 5 14 5.67157 14 6.5C14 7.32843 14.6716 8 15.5 8ZM17 12.5C17 13.3284 16.3284 14 15.5 14C14.6716 14 14 13.3284 14 12.5C14 11.6716 14.6716 11 15.5 11C16.3284 11 17 11.6716 17 12.5ZM15.5 20C16.3284 20 17 19.3284 17 18.5C17 17.6716 16.3284 17 15.5 17C14.6716 17 14 17.6716 14 18.5C14 19.3284 14.6716 20 15.5 20Z"
                fill="#121923" />
            </svg>
          </td>
          <td>{{ index + 1 }}</td>
          <td>{{ idea.ideaTitle }}</td>
          <td>
            <img :src="idea.contributorIcon" alt="Contributor Icon" width="24" height="24" />
          </td>
          <td>{{ idea.tag ? idea.tag : "ideaTag" }}</td>
          <td>
            <button @click="moveUp(index)">⬆</button>
            <button @click="moveDown(index)">⬇</button>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const fakeIdeaData = ref([
  {
    id: 1,
    ideaTitle: 'Improve User Interface',
    contributorIcon: 'path/to/contributor1.png',
    tag: 'UI/UX'
  },
  {
    id: 2,
    ideaTitle: 'Optimize Performance',
    contributorIcon: 'path/to/contributor2.png',
    tag: 'Performance'
  },
  {
    id: 3,
    ideaTitle: 'Add Dark Mode',
    contributorIcon: 'path/to/contributor3.png',
    tag: 'Feature'
  }
]);

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
});

let draggedItemIndex = null;

const dragStart = (index) => {
  draggedItemIndex = index;
};

const drop = (index) => {
  if (draggedItemIndex !== null) {
    const draggedItem = fakeIdeaData.value.splice(draggedItemIndex, 1)[0];
    fakeIdeaData.value.splice(index, 0, draggedItem);
    draggedItemIndex = null;
  }
};

const moveUp = (index) => {
  if (index > 0) {
    const temp = fakeIdeaData.value[index];
    fakeIdeaData.value[index] = fakeIdeaData.value[index - 1];
    fakeIdeaData.value[index - 1] = temp;
  }
};

const moveDown = (index) => {
  if (index < fakeIdeaData.value.length - 1) {
    const temp = fakeIdeaData.value[index];
    fakeIdeaData.value[index] = fakeIdeaData.value[index + 1];
    fakeIdeaData.value[index + 1] = temp;
  }
};
</script>
