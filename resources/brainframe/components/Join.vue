<template>
    <h1 class="headline__join">
        <BrainFrameIcon class="headline__join__icon" />
        <div class="headline__join__text">
            <p class="headline__join__brain">Brain</p>
            <p class="headline__join__frame">Frame</p>
        </div>
    </h1>
    <div class="join__info">
        <p>i</p>
    </div>
    <form @submit.prevent class="join__form" v-if="activeButton === 'Pin'">
        <input class="join__form__input" v-model="sessionId" type="integer" placeholder="Session-PIN">
        <button @click="joinSession" class="join__form__submit primary">Session beitreten</button>
    </form>
    <!-- QR Code Scanner anzeigen, wenn activeButton 'QrCode' ist -->
    <qrcode-stream v-if="activeButton === 'QrCode'" @decode="onDecode" @init="onInit"></qrcode-stream>
    <div class="join_buttons">
        <button @click="switchPinQrCode('Pin')" :class="{'active': activeButton === 'Pin'}" class="join_buttons__Pin">
            <PinIcon />
            <p>PIN eingeben</p>
        </button>
        <button @click="switchPinQrCode('QrCode')" :class="{'active': activeButton === 'QrCode'}" class="join_buttons__Scan">
            <QrScannerIcon />
            <p>QR-Code scannen</p>
        </button>
    </div>
    <div v-if="error" class="error">
        {{ error }}
    </div>
</template>
<script setup>
import { QrcodeStream } from 'vue-qrcode-reader';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import BrainFrameIcon from '../components/icons/BrainFrameIcon.vue';
import QrScannerIcon from '../components/icons/QrScannerIcon.vue';
import PinIcon from '../components/icons/PinIcon.vue';
const activeButton = ref('Pin');
const props = defineProps({
    userId: {
        type: [String, Number],
        required: true
    }
});
// Diese Methode wird aufgerufen, wenn ein QR-Code erfolgreich gescannt wurde
const onDecode = (result) => {
    sessionId.value = result;  // Setze die Session-ID auf das QR-Code-Ergebnis
    joinSession();  // Starte die Session
};

// Diese Methode wird aufgerufen, wenn der QR-Code-Scanner initialisiert wird
const onInit = (promise) => {
    promise.catch(error => {
        console.error(error);
        // Behandlung von Fehlern, z.B. wenn keine Kamera verfügbar ist
    });
};

const sessionId = ref(null);
const router = useRouter();
const error = ref(null);
const switchPinQrCode = (button_type) => {
    activeButton.value = button_type;  // setzt den aktiven Button
}
const joinSession = () => {
    const sessionIdPattern = /^\d{6}$/;

    if (sessionId.value && sessionIdPattern.test(sessionId.value)) {
        router.push(`/brainframe/${sessionId.value}`);
    } else {
        sessionId.value = null;
        error.value = 'Bitte eine gültige Session-PIN eingeben. Diese muss aus genau 6 Ziffern bestehen.';
    }
}

onMounted(() => {
  sessionId.value = null;
});

</script>
