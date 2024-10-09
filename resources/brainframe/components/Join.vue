<template>
  <main>
    <h1 class="headline__join">
      <BrainFrameIcon class="headline__join__icon" />
      <div class="headline__join__text">
        <p class="headline__join__brain">Brain</p>
        <p class="headline__join__frame">Frame</p>
      </div>
    </h1>
    <div @click="showInfo = !showInfo; showInfoWasActive = true" class="join__info__containerr">
      <div class="info__container">
        <div class="join__info" :class="{ 'glow-animation': !showInfoWasActive && !isInputFocused }">
          <p>i</p>
        </div>
      </div>
    </div>
    <div v-if="showInfo" class="info__text__container">
      <div class="info__text">
        <h3>Session Beitreten:</h3>
        <ul>
          <li>Bitte den Host deiner Ideen-Sammel Session darum dich in die Session einzuladen. Du kannst über
            <strong>einen Einladungslink, den Session PIN</strong> oder durch Scannen eines <strong>QR Codes
            </strong>beitreten.
          </li>
        </ul>
      </div>
    </div>
    <form @submit.prevent class="join__form" v-if="activeButton === 'Pin'">
      <input @keyup.enter="showInfo = false" @blur="showInfo = false; isInputFocused = false"
        @focus="isInputFocused = true" :class="{ 'glow-animation': showInfoWasActive && !sessionId }"
        class="join__form__input" v-model="sessionId" type="integer" placeholder="Session-PIN">
      <button :disabled="!sessionId || !/^\d{5}$/.test(sessionId)" @keyup.enter="joinSession" @click="joinSession"
        :class="{
    'glow-animation': sessionId && /^\d{6}$/.test(sessionId), 
    'primary': !(isInputFocused) && !showInfo, 
    'secondary': isInputFocused || showInfo
  }" class="join__form__submit">
        Session beitreten
      </button>
    </form>
    <div class="join__qr-code_container" v-if="activeButton === 'QrCode'">
      <div class="join__qr-code">
        <qrcode-stream @decode="onDecode" @init="onInit" @detect="onDetect"></qrcode-stream>
      </div>
    </div>
    <div class="join_buttons">
      <button @click="switchPinQrCode('Pin')" :class="{'active': activeButton === 'Pin'}" class="join_buttons__Pin">
        <PinIcon />
        <p>PIN eingeben</p>
      </button>
      <button @click="switchPinQrCode('QrCode')" :class="{'active': activeButton === 'QrCode'}"
        class="join_buttons__Scan">
        <QrScannerIcon />
        <p>QR-Code scannen</p>
      </button>
    </div>
    <div v-if="error" class="error">
      {{ error }}
    </div>
  </main>
</template>
<script setup>
import { QrcodeStream } from 'vue-qrcode-reader';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import BrainFrameIcon from '../components/icons/BrainFrameIcon.vue';
import QrScannerIcon from '../components/icons/QrScannerIcon.vue';
import PinIcon from '../components/icons/PinIcon.vue';

const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});
const isInputFocused = ref(false);
const showInfoWasActive = ref(false);
const showInfo = ref(false);
const sessionId = ref(null);
const router = useRouter();
const error = ref(null);
const activeButton = ref('Pin');

const onDecode = (result) => {
  console.log('QR Code decoded:', result);
  sessionId.value = result;
  joinSession();
};

const onInit = (promise) => {
  promise
    .then(() => {
      console.log('QR Code scanner initialized successfully');
    })
    .catch(error => {
      console.error('Failed to initialize QR Code scanner:', error);
      if (error.name === 'NotAllowedError') {
        error.value = 'ERROR: you need to grant camera access permission';
      } else if (error.name === 'NotFoundError') {
        error.value = 'ERROR: no camera on this device';
      } else if (error.name === 'NotSupportedError') {
        error.value = 'ERROR: secure context required (HTTPS, localhost)';
      } else if (error.name === 'NotReadableError') {
        error.value = 'ERROR: is the camera already in use?';
      } else if (error.name === 'OverconstrainedError') {
        error.value = 'ERROR: installed cameras are not suitable';
      } else if (error.name === 'StreamApiNotSupportedError') {
        error.value = 'ERROR: Stream API is not supported in this browser';
      } else if (error.name === 'InsecureContextError') {
        error.value = 'ERROR: Camera access is only permitted in secure context. Use HTTPS or localhost rather than HTTP.';
      } else {
        error.value = `ERROR: Camera error (${error.name})`;
      }
    });
};
const onDetect = (detections) => {
  if (detections && detections.length > 0) {
    const detection = detections[0];
    console.log('QR Code detected:', detection);

    if (detection.rawValue) {
      const detectedUrl = detection.rawValue;

      // Überprüfen, ob die URL mit Ihrer Domain beginnt
      if (detectedUrl.startsWith('https://stefan-theissen.de/brainframe/')) {
        // Extrahieren Sie die Session-ID aus der URL
        const sessionId = detectedUrl.split('/').pop();

        // Verwenden Sie Vue Router für die Navigation
        router.push(`/brainframe/${sessionId}`);
      } else {
        console.error('Ungültige URL erkannt:', detectedUrl);
        error.value = 'Ungültiger QR-Code-Inhalt erkannt.';
      }
    }
  }
};

const switchPinQrCode = (button_type) => {
  activeButton.value = button_type;  // setzt den aktiven Button
}
const joinSession = () => {
  const sessionIdPattern = /^\d{5}$/;

  if (sessionId.value && sessionIdPattern.test(sessionId.value)) {
    router.push(`/brainframe/${sessionId.value}`);
  } else {
    sessionId.value = null;
    error.value = 'Bitte eine gültige Session-PIN eingeben. Diese muss aus genau 5 Ziffern bestehen.';
  }
}

onMounted(() => {
  sessionId.value = null;
});

</script>
