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
    <form @submit.prevent class="join__form">
        <input class="join__form__input" v-model="sessionId" type="integer" placeholder="Session-PIN">
        <button @click="joinSession" class="join__form__submit primary">Session beitreten</button>
    </form>
    <div class="join_buttons">
        <button class="join_buttons__Pin">
            <PinIcon />
            <p>PIN eingeben</p>
        </button>
        <button class="join_buttons__Scan">
            <QrScannerIcon />
            <p>QR-Code scannen</p>
        </button>
    </div>
    <div v-if="error" class="error">
        {{ error }}
    </div>
</template>
<script setup>
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


const sessionId = ref(null);
const router = useRouter();
const error = ref(null);
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
