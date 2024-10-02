<template>
  <div v-if="methods" class="session-settings">
    <div class="session-settings__method-carousel">
      <div class="method-display">
        <h2>{{ currentMethod.name }}</h2>
        <div class="session-settings__method-carousel__buttons">
          <button @click="changeMethod(-1)"><ArrowLeftIcon/></button>
          <p v-html="currentMethod.description"></p>
          <button @click="changeMethod(1)"><ArrowRightIcon/></button>
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
const currentIndex = ref(0);

const currentMethod = computed(() => methods.value?.[currentIndex.value]);

const changeMethod = (direction) => {
  if (methods.value) {
    currentIndex.value = (currentIndex.value + direction + methods.value.length) % methods.value.length;
    emit('createSession', currentMethod.value.id);
  }
};

onMounted(() => {
  axios.get('/api/methods')
    .then(response => {
      methods.value = response.data;
      emit('createSession', response.data[0].id);
    })
    .catch(error => {
      console.error('Fehler beim Abrufen der Methoden', error);
    });
});
</script>