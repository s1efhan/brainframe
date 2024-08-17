<template>
    <textarea class="headline__session-target" v-model="sessionTarget" placeholder="< Zielfrage der Session >"
        :rows="rows" @input="adjustTextarea"></textarea>
    <div class="join__contributors__count">
        <ProfileIcon />
        <p>{{ contributorsCount }} | {{ contributorsAmount ? contributorsAmount : "?" }}</p>
        <input @change="generateQRCode" id="contributorsAmount" type="range" min="3" max="18" v-model="contributorsAmount" />
    </div>
    <div v-if="contributorsAmount" class="qr-code-container">
        <canvas class="qr-code" v-if="sessionLink" ref="qrcodeCanvas"></canvas>
    </div>
    <div @click="copyToClipboard(sessionLink)" class="session-link" v-if="sessionLink && contributorsAmount">
        <a :href="sessionLink">{{ sessionLink }} </a>
        <p><CopyIcon/> </p>
        
    </div>
    <div v-for="(email, index) in contributorEmailAddresses" :key="index">
        <input type="email" v-model="contributorEmailAddresses[index]" @input="validateEmail(index)"
            placeholder="E-Mail-Adresse eingeben">
    </div>
    <button @click="sessionInvite">Teilnehmer einladen</button>
    <SessionSettings v-if="userId && sessionId" :userId="userId" :sessionId="sessionId"
        :contributorEmailAddresses="contributorEmailAddresses" :sessionTarget="sessionTarget" />

</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import QRCode from 'qrcode';
import CopyIcon from '../components/icons/CopyIcon.vue';
import axios from 'axios';
import { updateSessionId } from '../js/eventBus.js';
import SessionSettings from '../components/SessionSettings.vue';
import ProfileIcon from '../components/icons/ProfileIcon.vue';
// Referenzen und reaktive Variablen
const router = useRouter();
const contributorsAmount = ref(null);
const props = defineProps({
    userId: {
        type: [String, Number],
        required: true
    }
});
const showQRCode = ref(false);
const sessionTarget = ref('');
const sessionLink = ref(null);
const qrcodeCanvas = ref(null);
const sessionId = ref(null);
const contributorsCount = ref(0);
const contributorEmailAddresses = ref(['']);
const userId = ref(props.userId);
const rows = ref(1);
// Methoden
const generateLink = () => {
    sessionId.value = Math.floor(10000 + Math.random() * 90000);
    sessionLink.value = `${window.location.origin}${router.resolve({ name: 'session', params: { id: sessionId.value } }).href}`;
    nextTick(generateQRCode);
    updateSessionId(sessionId.value)
};


const adjustTextarea = (event) => {
    const textarea = event.target;

    // Begrenzen Sie die Eingabe auf maximal 50 Zeichen
    if (textarea.value.length > 50) {
        textarea.value = textarea.value.slice(0, 50);
    }

    // Passen Sie die Höhe des Textarea an
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
    rows.value = textarea.value.split('\n').length;
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

const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

const sessionInvite = () => {
    const validEmails = contributorEmailAddresses.value.filter(isValidEmail);

    axios.post('/api/session/invite', {
        session_id: sessionId.value,
        host_id: userId.value,
        contributor_email_addresses: validEmails
    }).catch(error => {
        console.error('Error starting the session', error);
    });
};

const generateQRCode = () => {
    if (sessionLink.value && qrcodeCanvas.value && contributorsAmount && !showQRCode.value) {
        showQRCode.value = true;
        QRCode.toCanvas(qrcodeCanvas.value, sessionLink.value, (error) => {
            if (error) console.error(error);
            
        });
    }
};
// Lifecycle Hooks
onMounted(() => {
    generateLink();
});
</script>