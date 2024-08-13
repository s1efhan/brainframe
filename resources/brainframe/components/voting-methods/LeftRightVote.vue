<template>
    <section>
      <h2>LeftRightVote</h2>
      <p class="left-right-mode-icon">↔</p>
      <p>Ideas Count: {{ decisionsMade }}/{{ ideasCount }}</p>
      <h3>Pick Your Preferred Idea</h3>
      <table>
        <thead v-if="currentPair.length === 2">
          <tr>
            <th>{{ currentPair[0].ideaTitle }}</th>
            <th>{{ currentPair[1].ideaTitle }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="currentPair.length === 2">
            <td v-html="currentPair[0].ideaDescription"></td>
            <td v-html="currentPair[1].ideaDescription"></td>
          </tr>
          <tr v-if="currentPair.length === 2">
            <td><img :src="currentPair[0].contributorIcon" alt="Contributor Icon 1" width="24" height="24" /></td>
            <td><img :src="currentPair[1].contributorIcon" alt="Contributor Icon 2" width="24" height="24" /></td>
          </tr>
          <tr v-if="currentPair.length === 2">
            <td>#{{ currentPair[0].tag }}</td>
            <td>#{{ currentPair[1].tag }}</td>
          </tr>
          <tr v-if="currentPair.length === 2">
            <td><button @click="selectIdea(0)">L</button></td>
            <td><button @click="selectIdea(1)">R</button></td>
          </tr>
          <tr v-else>
            <td colspan="2">No more ideas to compare.</td>
          </tr>
        </tbody>
      </table>
      <button @click="undoLastDecision" :disabled="previousPair.length === 0">↺</button> <!-- Entscheidung rückgängig machen -->
    </section>
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
  