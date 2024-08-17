<template>
    <div class="session-settings">
      <div v-if="methods" class="session-settings__method-carousel">
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
  </template>
  
<script setup>
import axios from 'axios';
import { ref, onMounted, watch, toRef, computed} from 'vue';
const props = defineProps({
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
    console.log("Update Session")
    axios.post('/api/session', {
        session_id: sessionId.value,
        method_id: selectedMethod.value.id,
        host_id: userId.value,
        session_target: sessionTarget.value,
        contributor_email_addresses: contributorEmailAddresses.value
    }).catch(error => {
        console.error('Error saving session data', error);
    });
};

// Beobachtungen
watch([selectedMethod, sessionTarget], updateSession);


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