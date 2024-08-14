<template>
  <section v-if="!showSetup">
    <h2>Erstelle deine eigene Session</h2>
    <button @click="showSetup=true">Session erstellen</button>
  </section>
  <Setup v-if="showSetup" :userId="userId"/>
  <input type="text" v-model="testNachricht" placeholder="Nachricht eingeben">
  <button @click="sendToApi">Send</button>
  <div v-if="apiAntwort">
  <div v-for="(idea, index) in apiAntwort" :key="index">
    <h3>{{ index }} {{ idea.ideaTitle }}</h3>
    <p>Contributor ID: {{ idea.contributor_id }}</p>
    <div v-html="idea.ideaDescription"></div>
    <p>Tag: {{ idea.tag }}</p>
  </div>
</div>

</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Setup from '../components/Setup.vue';

const testNachricht = ref('');
const showSetup = ref(false);
const apiAntwort = ref(null);

const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});

const sendToApi = () => {
  axios.post('/api/test/api', {
    test_nachricht: testNachricht.value,
    session_id: 76391
  })
  .then(response => {
    console.log('Server response:', response.data);
    // Extrahiere nur den Content aus der API-Antwort
    if (response.data.choices && response.data.choices.length > 0) {
      const content = response.data.choices[0].message.content;
      try {
        // Entferne die Markdown-Codeblöcke und parse als JSON
        const cleanedContent = content.replace(/```json|\n```/g, '').trim();
        apiAntwort.value = JSON.parse(cleanedContent);
      } catch (error) {
        console.error('Fehler beim Parsen des JSON:', error);
        apiAntwort.value = 'Fehler beim Verarbeiten der Antwort';
      }
    } else {
      apiAntwort.value = 'Keine Antwort erhalten.';
    }
  })
  .catch(error => {
    console.error('Fehler bei der API-Anfrage', error);
    apiAntwort.value = 'Fehler bei der API-Anfrage';
  });
};
</script>