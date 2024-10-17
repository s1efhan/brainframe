<template>
    <main class="sitemap">
      <h1>Error 404</h1>
      <h2>vielleicht hast du hier nach gesucht?</h2>
      <ul>
        <li>
          <router-link to="/brainframe/join">Startseite (Join)</router-link>
        </li>
        <li>
          <router-link to="/brainframe/create">Create</router-link>
        </li>
        <li>
          <router-link to="/brainframe/datenschutz">Datenschutz</router-link>
        </li>
        <li>
          <router-link to="/brainframe/impressum">Impressum</router-link>
        </li>
        <li>
          <router-link to="/brainframe/profile">Profil</router-link>
        </li>
        <li>
          <router-link to="/brainframe/sessions">Sessions</router-link>
        </li>
        <li>
          <router-link to="/brainframe/knowledge">Wissen</router-link>
          <ul>
            <li v-for="method in knowledgeMethods" :key="method.path">
              <router-link :to="'/brainframe/knowledge/' + method.path">
                {{ method.name }}
              </router-link>
            </li>
          </ul>
        </li>
      </ul>
    </main>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  
  const knowledgeMethods = ref([
    { name: '6-3-5 Methode', path: '6-3-5' },
    { name: 'Walt Disney Methode', path: 'walt-disney' },
    { name: '6 Thinking Hats', path: '6-thinking-hats' },
    { name: 'Crazy 8', path: 'crazy-8' },
  ]);
  </script>