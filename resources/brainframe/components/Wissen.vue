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
          <li>Backend: Laravel 11, Reverb, Postgres</li>
          <li>Frontend: Vue3, SCSS, Vite</li>
          <li>Hosting: AWS (E2C), Ubuntu, Laravel Forge
          </li>
          <li>Analytics: Google Search Console</li>
        </ul>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref } from 'vue';
const showDetails = ref({
  methodik: false,
  motivation: false,
  techStack: false
});

const toggleDetailsVisibility = (section) => {
  showDetails.value[section] = !showDetails.value[section];
};
</script>