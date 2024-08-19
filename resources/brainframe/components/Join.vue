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
    <qrcode-stream
    v-if="activeButton === 'QrCode'"
    @decode="onDecode"
    @init="onInit"
    @detect="onDetect"
  ></qrcode-stream>
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

const onDetect = (promise) => {
  promise
    .then(result => {
      console.log('QR Code detected:', result);
    })
    .catch(error => {
      console.error('Failed to detect QR Code:', error);
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
