<template>
    <div v-if="methods" class="session-settings">
      <div class="session-settings__method-carousel">
        <div class="method-display">
          <h2>{{ currentMethod.name }}</h2>
          <div class="session-settings__method-carousel__buttons">
            <button @click="previousMethod">&lt;</button>
            <p>{{ currentMethod.description }}</p>
            <button @click="nextMethod">&gt;</button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showInfo && methods" class="info__text__container">
    <div class="info__text">
  <h3>Erstelle eine {{currentMethod.name}} Session </h3>
  <ul v-if="currentMethod.name == '6-3-5'">
    <li>Brainstorming mit <strong>Weiterreichen</strong></li>
    <li><strong>3 Ideen pro Runde</strong> pro Teilnehmer</li>
    <li>5 Minuten pro Runde</li>
    <li><strong>Vorteile:</strong> Schnelle Ideenfindung.</li>
</ul>

<ul v-if="currentMethod.name == 'Walt Disney'">
    <li>Rollenwechsel (Träumer, Realist, Kritiker) zur Ideenbewertung.</li>
    <li><strong>Vorteile:</strong> Umfassende Perspektiven. <strong><br>Nachteile:</strong> Zeitaufwendig, erfordert Engagement.</li>
</ul>

<ul v-if="currentMethod.name == 'Crazy 8'">
    <li>In 8 Minuten 8 Ideen pro Person skizzieren.</li>
    <li><strong>Vorteile:</strong> Schnelle Ideenfindung. <strong><br>Nachteile:</strong> Kann oberflächlich sein.</li>
</ul>

<ul v-if="currentMethod.name == '6 Thinking Hats'">
    <li>Verschiedene Denkrichtungen (Hüte) für umfassende Analyse.</li>
    <li>Unterschiedliche Rollen mit spezifischen <strong>Perspektiven</strong>
      <br>
   z.B: Emotional, Kritisch, Optimistisch, Sachlich, Kreativ oder mit Übersicht
</li>
</ul>

</div>
</div>
  </template>
  
<script setup>
import axios from 'axios';
import { ref, onMounted, watch, toRef, computed} from 'vue';
const props = defineProps({
  showInfo: {
    type: Boolean,
    required: true
  },
    userId: {
        type: [String, Number],
        required: true
    },
    sessionTarget: {
        type: String,
        required: true
    },
    contributorEmailAddresses: {
        type: Array,
        required: false
    },
    sessionId: {
        type: [String, Number],
        required: true
    },
    contributorsAmount: {
        type: [String, Number],
        required: true
    }
});
const currentMethodIndex = ref(0);
const copyToClipboard = (copyText) => {
    navigator.clipboard.writeText(copyText);
};

const currentMethod = computed(() => {
  return methods.value ? methods.value[currentMethodIndex.value] : null;
});

const nextMethod = () => {
  if (methods.value) {
    currentMethodIndex.value = (currentMethodIndex.value + 1) % methods.value.length;
    selectedMethod.value = currentMethod.value;
  }
};

const previousMethod = () => {
  if (methods.value) {
    currentMethodIndex.value = (currentMethodIndex.value - 1 + methods.value.length) % methods.value.length;
    selectedMethod.value = currentMethod.value;
  }
};
const sessionId = ref(props.sessionId);
const contributorEmailAddresses = ref(props.contributorEmailAddresses);
const userId = ref(props.userId);
const sessionTarget = toRef(props, 'sessionTarget');
const selectedMethod = ref(props.selectedMethod);
const methods = ref(null);
const updateSession = () => {
    axios.post('/api/session', {
        session_id: sessionId.value,
        method_id: selectedMethod.value.id,
        host_id: userId.value,
        contributors_amount: props.contributorsAmount,
        session_target: sessionTarget.value,
        contributor_email_addresses: contributorEmailAddresses.value
    }).catch(error => {
        console.error('Error saving session data', error);
    });
};

const contributorsAmount = toRef(props, 'contributorsAmount');

// Beobachtungen
watch([selectedMethod, sessionTarget, contributorsAmount], updateSession);


const getMethods = () => {
    axios.get('/api/methods')
        .then(response => {
            methods.value = response.data;
            const defaultMethod = methods.value.find(m => m.id === 1);
            if (defaultMethod) selectedMethod.value = { ...defaultMethod };
        }).catch(error => {
            console.error('Error fetching methods', error);
        });
};
onMounted(() => {
    getMethods();
    userId.value = props.userId;
});
</script>