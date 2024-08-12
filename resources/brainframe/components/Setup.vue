<template>
    <section>
        <h1>Session Setup</h1>
        <form @submit.prevent>
            <h3>Methode auswählen</h3>
            <label for="methodSelect">Wähle eine Methode:</label>
            <select id="methodSelect" v-model="selectedMethod.id" @change="updateSelectedMethod">
                <option v-for="method in methods" :key="method.id" :value="method.id">
                    {{ method.name }}
                </option>
            </select>
            <p v-if="selectedMethod">{{ selectedMethod.description }}</p>
            <!-- Detail Einstellungen /Timer etc.-->
            <!-- Teilnehmer einladen (Email) -->
            <h3>Ziel definieren</h3>
            <label for="sessionTarget">Zielfrage:</label>
            <input type="text" v-model="sessionTarget"
                placeholder="Die Problemstellung deiner Session">
            <!-- Slider mit Teilnehmer Anzahl-->
            <!-- Karussel mit Methoden-Auswahl-->
            <!-- Auswahl des Bewertungssystems-->
            <!-- Tool-Tip mit Erklärung und Link zur detaillierten Anleitung-->
            <!-- Slider mit Timer-->
            <h3>Teilnehmer einladen</h3>
            <div v-for="(email, index) in contributorEmailAddresses" :key="index">
                <input type="email" v-model="contributorEmailAddresses[index]"
                    @keydown.enter="handleEmailInput(index, email, $event)" @blur="handleEmailInput(index, email)"
                    placeholder="Enter email address">
            </div>
            <button @click="sessionInvite">Teilnehmer einladen</button>
            <!-- Kalender-Termin hinzufügen-->
            <p v-if="sessionLink">
                Your session link: <a :href="sessionLink">{{ sessionLink }}</a>
                <button @click="copyToClipboard(sessionLink)">
                    {{ copyIcon }}
                </button>
            </p>
            <p v-if="sessionId">Session ID: <button @click="copyToClipboard(sessionId)">{{ sessionId }}</button></p>
            <canvas v-if="sessionLink" ref="qrcodeCanvas"></canvas>
        </form>
    </section>
</template>

<script setup>
import { ref, nextTick, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
const router = useRouter();
import QRCode from 'qrcode';
import axios from 'axios';

const props = defineProps({
    userId: {
        type: [String, Number],
        required: true
    }
});
const selectedMethod = ref({ id: '', name: '', description: '' });
const sessionLink = ref('');
const sessionTarget = ref('');
const qrcodeCanvas = ref(null);
const copyIcon = ref('copy Link'); // durch Icon ersetzen
const sessionId = ref('');
const contributorEmailAddresses = ref(['']);
const addEmailInput = () => {
    contributorEmailAddresses.value.push('');
};
watch([selectedMethod, sessionTarget], () => {
  updateSession();
});
const handleEmailInput = (index, email, event) => {
    if (event && event.type === 'keydown' && event.key !== 'Enter') {
        return;
    }

    if (isValidEmail(email)) {
        if (index === contributorEmailAddresses.value.length - 1) {
            addEmailInput();
        }
    } else {
        contributorEmailAddresses.value.splice(index, 1);
        if (contributorEmailAddresses.value.length === 0) {
            addEmailInput();
        }
    }
};

const isValidEmail = (email) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
};

const userId = ref('');
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

const sessionInvite = () => {
    const validEmails = contributorEmailAddresses.value.filter(isValidEmail);
    console.log(contributorEmailAddresses.value, validEmails)
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
const copyToClipboard = (copyText) => {
    navigator.clipboard.writeText(copyText);
};

onMounted(() => {
    getMethods();
    generateLink();
    userId.value = props.userId;
});
</script>