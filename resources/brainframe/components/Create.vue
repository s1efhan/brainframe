<template>
    <main>
      <div class="error" v-if="errorMsg"><p>{{ errorMsg }}</p></div>
        <div v-if ="!errorMsg" class="sessionTarget__container">
      <textarea 
        ref="targetTextarea"
        @focus="onFocus"
        @blur="onBlur"
        @keyup.enter="updateSessionTarget"
        @keydown.enter.prevent="updateSessionTarget"
        class="headline__session-target"
        :value="tempSessionTarget"
        @input="updateTempTarget"
        placeholder="< Zielfrage der Session >"
        :rows="rows"
      ></textarea>
      <button @click="updateSessionTarget" class="safe__target primary">speichern</button>
    </div>
      <SessionSettings 
        @updateSession="updateSession" 
        v-if="userId && sessionId && sessionTarget && !errorMsg"
      />
      <div v-if="sessionTarget && !errorMsg" class="create__session__container">
        <button @click="createSession" class="primary">Session Erstellen</button>
      </div>
      
    </main>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';
  import { updateSessionId } from '../js/eventBus.js';
  import SessionSettings from '../components/SessionSettings.vue';
  
  // Props
  const props = defineProps({
    userId: {
      type: [String, Number],
      required: true
    }
  });
  
  // Refs
  const router = useRouter();
const errorMsg = ref(null);
  const sessionTarget = ref('');
  const tempSessionTarget = ref('');
  const sessionLink = ref(null);
  const sessionId = ref(null);
  const targetTextarea = ref(null);
  const isTextareaFocused = ref(false);
  const userId = ref(props.userId);
  const rows = ref(1);
  const createSession = () => {
  if (sessionId.value) {
    router.push(`/brainframe/${sessionId.value}`);
  }
};
  // Methoden
  const onFocus = () => { 
    isTextareaFocused.value = true; 
  };
  
  const onBlur = () => { 
    isTextareaFocused.value = false;
    updateSessionTarget();
  };
  
  const updateTempTarget = (event) => {
    tempSessionTarget.value = event.target.value;
    adjustTextarea(event);
  };
  
  const updateSessionTarget = () => {
    sessionTarget.value = tempSessionTarget.value;
  };
  
  const adjustTextarea = (event) => {
    const textarea = event.target;
    if (textarea.value.length > 60) {
      textarea.value = textarea.value.slice(0, 60);
      tempSessionTarget.value = textarea.value;
    }
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
    rows.value = textarea.value.split('\n').length;
  };
  
  
  const generateLink = () => {
    sessionId.value = Math.floor(10000 + Math.random() * 90000);
    sessionLink.value = `${window.location.origin}${router.resolve({ name: 'session', params: { id: sessionId.value } }).href}`;
    updateSessionId(sessionId.value);
  };

  
  const updateSession = (selectedMethod) => {
    axios.post('/api/session', {
      session_id: sessionId.value,
      method_id: selectedMethod,
      host_id: userId.value,
      contributors_amount: 1,
      session_target: sessionTarget.value,
    }).catch(error => {
      errorMsg.value = error.response.data.message;
      console.error('Error saving session data', error);
    });
  };
  
  // Lifecycle Hooks
  onMounted(() => {
    generateLink();
    targetTextarea.value.focus();
  });
  </script>