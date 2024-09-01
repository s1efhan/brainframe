<template>
    <main>
        <textarea ref="targetTextarea" @focus="onFocus" @blur="onBlur" class="headline__session-target"
            v-model="sessionTarget" placeholder="< Zielfrage der Session >" :rows="rows"
            @input="adjustTextarea"></textarea>
        <div v-if="sessionTarget && !isTextareaFocused" class="join__contributors__info__container">
            <div class="join__contributors__count">
                <ProfileIcon />
                <p>{{ contributorsCount }} | {{ contributorsAmount ? contributorsAmount : "?" }}</p>
                <input @change="generateQRCode" id="contributorsAmount" type="range" min="3" max="18"
                    v-model="contributorsAmount" />
            </div>
            <div v-if="contributorsAmount" @click="showInfo = !showInfo" class="create__info__containerr">
                <div class="info__container">
                    <div class="join__info">
                        <p>i</p>
                    </div>
                </div>
            </div>
        </div>
        <SessionSettings v-if="userId && sessionId && contributorsAmount" :showInfo="showInfo"
            :contributorsAmount="contributorsAmount" :userId="userId" :sessionId="sessionId"
            :contributorEmailAddresses="contributorEmailAddresses" :sessionTarget="sessionTarget" />
        <div v-if="contributorsAmount" class="qr-code-container">
            <canvas class="qr-code" v-if="sessionLink" ref="qrcodeCanvas"></canvas>
        </div>

        <div @click="copyToClipboard(sessionLink)" class="session-link" v-if="sessionLink && contributorsAmount">
            <router-link :to="'/brainframe/' + sessionId">{{ sessionLink }} </router-link>
            <p>
                <CopyIcon />
            </p>
        </div>
        <div v-if="sessionLink && contributorsAmount" class="email-list">
        <div  v-for="email in validatedEmails" :key="email" class="validated-email">
            {{ email }} <span @click="removeEmail(email)" class="remove-email">x</span>
        </div>
    </div>
    <div v-if="sessionLink && contributorsAmount" class="email-input__container" v-for="(email, index) in contributorEmailAddresses" :key="index">
            <input type="email" v-model="contributorEmailAddresses[index]" @keyup.enter="validateEmail(index, $event)"
                @blur="validateEmail(index, $event)" placeholder="E-Mail-Adresse eingeben">
        
     <button class="secondary" v-if="sessionLink && contributorsAmount" @click="sessionInvite">Teilnehmer einladen</button>
    </div>
    </main>
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
const showInfo = ref(true);
const showQRCode = ref(false);
const sessionTarget = ref('');
const sessionLink = ref(null);
const qrcodeCanvas = ref(null);
const sessionId = ref(null);
const contributorsCount = ref(0);
const isTextareaFocused = ref(false);
const targetTextarea = ref(null);
const validatedEmails = ref([]);
// Fügen Sie diese Methoden hinzu
const onFocus = () => { isTextareaFocused.value = true; };
const onBlur = () => { isTextareaFocused.value = false; };
const contributorEmailAddresses = ref(['']);
const userId = ref(props.userId);
const rows = ref(1);
// Methoden

const removeEmail = (email) => {
    validatedEmails.value = validatedEmails.value.filter(e => e !== email);
};
const generateLink = () => {
    sessionId.value = Math.floor(10000 + Math.random() * 90000);
    sessionLink.value = `${window.location.origin}${router.resolve({ name: 'session', params: { id: sessionId.value } }).href}`;
    nextTick(generateQRCode);
    updateSessionId(sessionId.value)
};
const copyToClipboard = (copyText) => {
    navigator.clipboard.writeText(copyText);
};

const adjustTextarea = (event) => {
    const textarea = event.target;
    if (textarea.value.length > 60) {
        textarea.value = textarea.value.slice(0, 60);
    }

    // Passen Sie die Höhe des Textarea an
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
    rows.value = textarea.value.split('\n').length;
};
const validateEmail = (index, event) => {
    const email = contributorEmailAddresses.value[index];
    if (isValidEmail(email) && (event.key === 'Enter' || event.type === 'blur')) {
        validatedEmails.value.push(email);
        contributorEmailAddresses.value[index] = '';
    }
};
const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

const sessionInvite = () => {
    axios.post('/api/session/invite', {
        session_id: sessionId.value,
        host_id: userId.value,
        contributor_email_addresses: validatedEmails.value
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
    targetTextarea.value.focus();
});
</script>