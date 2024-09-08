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
    />
  </template>
  
  <script setup>
  import ContributorsBoard from '../ContributorsBoard.vue';
  import { defineProps, onMounted } from 'vue';
  
  const props = defineProps({
    method: Object,
    currentRound: Number,
    sessionHostId: [String, Number],
    contributors: Array,
    sessionId: [String, Number],
    personalContributor: Object,
    ideasCount: Object,
    maxIdeaInput: Number
  });
  const handleExit = () => {

  }
  onMounted(() => {
    console.log('method:', props.method);
    console.log('currentRound:', props.currentRound);
    console.log('sessionHostId:', props.sessionHostId);
    console.log('contributors:', props.contributors);
    console.log('sessionId:', props.sessionId);
    console.log('personalContributor:', props.personalContributor);
    console.log('ideasCount:', props.ideasCount);
  });
  </script>