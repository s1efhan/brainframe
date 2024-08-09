<template>
<!-- Methoden Karussel -->
<!-- Abgeschlossene Sessions Karussel -->
 <!-- Sessions wo man Contributor ist...-->
  <section v-if="!showSetup">
  <h2>Erstelle deine eigene Session</h2>
  <button @click="showSetup=true">Session erstellen</button>
</section>
  <Setup v-if="showSetup" :userId="userId"/>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import Setup from '../components/Setup.vue';
const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});
const showSetup = ref(false);
const userId = ref(props.userId);
onMounted(() => { 
  userId.value = props.userId;
});
</script>