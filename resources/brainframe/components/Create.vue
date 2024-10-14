<template>
  <main class="create">
    <div class="error" v-if="errorMsg">
      <p>{{ errorMsg }}</p>
    </div>
    <div v-if="!errorMsg" class="sessionTarget__container">
      <textarea ref="targetTextarea" @focus="onFocus" @blur="onBlur" :class="{ 'glow-animation': isEmptyAndNotFocused }"
        @keyup.enter="updateSessionTarget" @keydown.enter.prevent="updateSessionTarget" class="headline__session-target"
        :value="tempSessionTarget" @input="updateTempTarget" placeholder="< Zielfrage der Session >"
        :rows="rows"></textarea>
      <button @click="updateSessionTarget" class="safe__target primary">speichern</button>
    </div>
    <SessionSettings @createSession="createSession" @switchMethod="clickedTroughSettings = true"
      :clickedTroughSettings="clickedTroughSettings" v-if="userId && sessionId && sessionTarget && !errorMsg" />
    <div v-if="sessionTarget && !errorMsg" class="create__session__container">
      <button @click="startSession" :class="{ 'glow-animation': !isEmptyAndNotFocused && clickedTroughSettings}"
        class="primary">Session Erstellen</button>
    </div>
  </main>

</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { updateSessionId } from '../js/eventBus.js';
import SessionSettings from '../components/SessionSettings.vue';
const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});
const clickedTroughSettings = ref(false);
const isEmptyAndNotFocused = computed(() => {
  return tempSessionTarget.value.trim() === '' && !isTextareaFocused.value;
});
const router = useRouter();
const errorMsg = ref(null);
const sessionTarget = ref('');
const tempSessionTarget = ref('');
const sessionId = ref(null);
const sessionLink = ref(null);
const targetTextarea = ref(null);
const isTextareaFocused = ref(false);
const userId = ref(props.userId);
const rows = ref(1);
const startSession = () => {
  if (sessionId.value) {
    router.push(`/brainframe/${sessionId.value}`);
  }
};
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

const createSession = (selectedMethod) => {
  console.log("session_id: ", sessionId.value,
    "new_method: ", selectedMethod,
    "host_id: ", userId.value,
    "new_target: ", sessionTarget.value);
  axios.post('/api/session/create', {
    session_id: sessionId.value,
    method_id: selectedMethod,
    user_id: userId.value,
    target: sessionTarget.value,
  }).then(response => {
    console.log('Session created/updated successfully');
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