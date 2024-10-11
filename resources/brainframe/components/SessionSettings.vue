<template>
  <div v-if="methods" class="session-settings">
    <div class="session-settings__method-carousel">
      <div class="method-display">
        <h2>{{ currentMethod.name }}</h2>
        <div class="session-settings__method-carousel__buttons">
          <button @click="changeMethod(-1)"><ArrowLeftIcon  :class="{ 'svg-animation': !props.clickedTroughSettings }"/></button>
          <p v-html="currentMethod.description"></p>
          <button @click="changeMethod(1)"><ArrowRightIcon  :class="{ 'svg-animation': !props.clickedTroughSettings }"/></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted} from 'vue';
import axios from 'axios';
import ArrowLeftIcon from './icons/ArrowLeftIcon.vue';
import ArrowRightIcon from './icons/ArrowRightIcon.vue';
const emit = defineEmits(['createSession']);
const methods = ref(null);
const currentIndex = ref(3);
const props = defineProps({
    clickedTroughSettings: {
        type: Boolean,
        required: true
    }
  });
const defineEmits = ('switchMethod')
const currentMethod = computed(() => methods.value?.[currentIndex.value]);

const changeMethod = (direction) => {
  if (methods.value) {
    emit('switchMethod');
    currentIndex.value = (currentIndex.value + direction + methods.value.length) % methods.value.length;
    emit('createSession', currentMethod.value.id);
  }
};

onMounted(() => {
  axios.get('/api/methods')
    .then(response => {
      methods.value = response.data;
      emit('createSession', response.data[3].id);
    })
    .catch(error => {
      console.error('Fehler beim Abrufen der Methoden', error);
    });
});
</script>