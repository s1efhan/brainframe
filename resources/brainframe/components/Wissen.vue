<template>
  <main>
    <div class="knowledge__headline">
      <h1>BrainFrame Wissen</h1>
    </div>
    <div class="knowledge__container">
      <div>
        <h2 @click="toggleDetailsVisibility('methodik')">
          <div>Die Methodik</div>
          <div>v</div>
        </h2>
        <ul v-if="showDetails.methodik">
          <li><router-link to="/brainframe/knowledge/6-3-5">Die 6-3-5 Methode - Inspiration vom eigenen
              "Sitznachbarn"</router-link></li>
          <li><router-link to="/brainframe/knowledge/walt-disney">Walt Disney Methode - Ideenentwicklung nach
              amerikanischem Vorbild</router-link></li>
          <li><router-link to="/brainframe/knowledge/crazy-8">Crazy 8 - Schnelle Ideengenerierung in acht
              Minuten</router-link></li>
          <li><router-link to="/brainframe/knowledge/6-thinking-hats">6 Thinking Hats - Perspektivenwechsel für
              umfassende Lösungen</router-link></li>
        </ul>
      </div>
      <div>
        <h2 @click="toggleDetailsVisibility('motivation')">
          <div>Die Motivation </div>
          <div>v</div>
        </h2>
        <ul v-if="showDetails.motivation">
          <li><router-link to="/brainframe/knowledge/problems">Probleme herkömmlicher Ideen-Sammel Tools und
              Prozesse</router-link></li>
          <li><router-link to="/brainframe/knowledge/solution-brainframe">BrainFrame als Lösung - Organisiertes
              Brainstorming für verwertbare Ergebnisse </router-link></li>
        </ul>
      </div>
      <div>
        <h2 @click="toggleDetailsVisibility('techStack')">
          <div>
            <div>Der Tech Stack</div>
          </div>
          <div>v</div>
        </h2>
        <ul v-if="showDetails.techStack">
          <li><router-link to="/brainframe/knowledge/backend">Backend: Laravel 11, Reverb, Postgres</router-link></li>
          <li><router-link to="/brainframe/knowledge/frontend">Frontend: Vue3, SCSS, Vite, Rive</router-link></li>
          <li><router-link to="/brainframe/knowledge/hosting">Hosting: AWS (E2C), Ubuntu, Laravel Forge</router-link>
          </li>
          <li><router-link to="/brainframe/knowledge/analytics">Analytics: Google Search Console, Sitebehaviour.com,
              Proofreader.io</router-link></li>
        </ul>
      </div>
    </div>
    <button @click="fillValue">Start</button>
    <div v-if="!fakeValue">
      <l-quantum size="90" speed="3" color="white"></l-quantum>
    </div>
    <l-ring-2 
class="loader" 
color="#ffffff" 
size="50" 
stroke="6" 
bg-opacity="0.1" 
stroke-length="0.25" 
speed="0.8"
></l-ring-2>
  </main>
</template>

<script setup>
import { ref } from 'vue';
import 'ldrs/quantum';
import 'ldrs/ring2';
const showDetails = ref({
  methodik: false,
  motivation: false,
  techStack: false
});

const fakeValue = ref(null);

const fillValue = () => {
  fakeValue.value = null;
  setTimeout(() => {
    fakeValue.value = 1;
  }, 5000); // 5000 Millisekunden = 5 Sekunden
};

const toggleDetailsVisibility = (section) => {
  showDetails.value[section] = !showDetails.value[section];
};
</script>