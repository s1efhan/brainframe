<template>
    <h1 class="headline">
        <BrainFrameIcon class="headline__icon" />
        <div class="headline__text">
            <p class="headline__brain">Brain</p>
            <p class="headline__frame">Frame</p>
        </div>
    </h1>
    <div class="join__info"><p>i</p></div>
    <form @submit.prevent class="join__form">
        <input class="join__form__input" type="integer" placeholder="Session-PIN">
        <button class="join__form__submit primary">Session beitreten</button>
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
</template>

<script setup>
import BrainFrameIcon from '../components/icons/BrainFrameIcon.vue';
import QrScannerIcon from '../components/icons/QrScannerIcon.vue';
import PinIcon from '../components/icons/PinIcon.vue';
</script>
