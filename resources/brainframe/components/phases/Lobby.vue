<template>
    <h2>Lobby</h2>
    <ContributorsBoard
      v-if="props.method && props.personalContributor"
      :method="props.method"@exit="handleExit"
      :currentRound="props.currentRound"
      :sessionHostId="props.sessionHostId"
      :contributors="props.contributors"
      :sessionId="props.sessionId"
      :personalContributor="props.personalContributor"
      :ideasCount="props.ideasCount" 
      :isLobby="true" :previousPhase="props.previousPhase"
    />
  </template>
  
  <script setup>
  import ContributorsBoard from '../ContributorsBoard.vue';

  const props = defineProps({
    method: Object,
    currentRound: Number,
    sessionHostId: [String, Number],
    contributors: Array,
    sessionId: [String, Number],
    personalContributor: Object,
    ideasCount: Object,
    maxIdeaInput: Number,
    isLobby: Boolean,
    previousPhase: String
  });
  const emit = defineEmits(['switchPhase']);
  const handleExit = () => {
    emit('switchPhase', 'previousPhase');
  }
  </script>