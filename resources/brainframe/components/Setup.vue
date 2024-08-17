<template>

</template>

<script setup>
import { ref, nextTick, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import CopyIcon from '../components/icons/CopyIcon.vue';
const router = useRouter();
import QRCode from 'qrcode';
import axios from 'axios';
const contributorsAmount = ref(3);
const props = defineProps({
    userId: {
        type: [String, Number],
        required: true
    }
});
const selectedMethod = ref(({ id: '', name: '', description: '' }));
const sessionLink = ref('');
const sessionTarget = ref('');
const qrcodeCanvas = ref(null);
const sessionId = ref('');
const contributorEmailAddresses = ref(['']);

watch([selectedMethod, sessionTarget], () => {
    updateSession();
});

const userId = ref(null);
const methods = ref([]);
const generateLink = () => {
    sessionId.value = Math.floor(10000 + Math.random() * 90000); // Generate a random session ID
    sessionLink.value = `${window.location.origin}${router.resolve({ name: 'session', params: { id: sessionId.value } }).href}`;

    nextTick(() => {
        generateQRCode();
    });
};
const updateSelectedMethod = () => {
    const method = methods.value.find(m => m.id === selectedMethod.value.id);
    if (method) {
        selectedMethod.value = { ...method };
    }
};

const updateSession = () => {
    axios.post('/api/session', {
        session_id: sessionId.value,
        method_id: selectedMethod.value.id,
        host_id: userId.value,
        session_target: sessionTarget.value,
        contributor_email_addresses: contributorEmailAddresses.value
    })
        .then(response => {
            console.log('Server response:', response.data);
        })
        .catch(error => {
            console.error('Error saving session data', error);
        });
};
const validateEmail = (index) => {
  const email = contributorEmailAddresses.value[index];
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  
  if (emailRegex.test(email) && 
      index === contributorEmailAddresses.value.length - 1 && 
      contributorEmailAddresses.value.length < contributorsAmount.value) {
    contributorEmailAddresses.value.push('');
  }
};
const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

const sessionInvite = () => {
    const validEmails = contributorEmailAddresses.value.filter(email => isValidEmail(email));

    axios.post('/api/session/invite', {
        session_id: sessionId.value,
        method_id: selectedMethod.value.id,
        host_id: userId.value,
        contributor_email_addresses: validEmails
    })
        .then(response => {
            console.log('Server response:', response.data);
        })
        .catch(error => {
            console.error('Error starting the session', error);
        });
};

const generateQRCode = () => {
    if (sessionLink.value && qrcodeCanvas.value) {
        QRCode.toCanvas(qrcodeCanvas.value, sessionLink.value, (error) => {
            if (error) console.error(error);
        });
    }
};
const getMethods = () => {
    axios.get('/api/methods')
        .then(response => {
            methods.value = response.data;
            console.log('Server response:', response.data);
            // Wähle die Methode mit ID 1 aus
            const defaultMethod = methods.value.find(m => m.id === 1);
            if (defaultMethod) {
                selectedMethod.value = { ...defaultMethod };
            }
        })
        .catch(error => {
            console.error('Error fetching methods', error);
        });
};


onMounted(() => {
    getMethods();
    generateLink();
    userId.value = props.userId;
});
</script>