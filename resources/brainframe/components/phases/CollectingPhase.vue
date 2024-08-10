<template>
  <h3>CollectingPhase</h3>
  <section>
    <form @submit.prevent="handleSubmit">
      <label for="image">Bild einfügen: </label>
      <br>
      <input type="file" id="image" ref="fileInput" @change="handleFileChange" />
      <img @click="openFileInput" :src="imageFileUrl ? imageFileUrl : '/fake-url'" alt="uploadedImageIdea" height="100">
      <br>
      <label for="textInput">Idee:</label>
      <input type="text" id="textInput" v-model="textInput">
      <button v-if="!isListening" type="button" @click="isListening = true">▶️</button>
      <button v-else type="button" @click="isListening = false">⏸️</button>
      <br>
      <button type="submit" @click="isListening = false, submitIdea(true);">Idee speichern</button>
      <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
    </form>
  </section>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';

const route = useRoute();
const textInput = ref('');
const errorMsg = ref('');
const fileInput = ref(null);
const sessionId = ref(route.params.id);
const isListening = ref(false);
const personalContributor = ref(null);

const openFileInput = () => {
  fileInput.value.click();
}
const props = defineProps({
  personalContributor: {
    type: Object,
    required: true
  }
});
const imageFile = ref(null)
const imageFileUrl = ref('')
const handleFileChange = (event) => {
  imageFile.value = event.target.files[0];
  imageFileUrl.value = URL.createObjectURL(imageFile.value)
  console.log('File selected:', imageFileUrl.value);
};
const compressImage = async (file, maxSizeInMB = 2) => {
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        const canvas = document.createElement('canvas');
        let width = img.width;
        let height = img.height;
        let quality = 0.7;
        let dataUrl;

        do {
          canvas.width = width;
          canvas.height = height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, width, height);
          dataUrl = canvas.toDataURL('image/jpeg', quality);
          
          if (dataUrl.length > maxSizeInMB * 1024 * 1024) {
            width *= 0.9;
            height *= 0.9;
          }
          
          quality *= 0.9;
        } while (dataUrl.length > maxSizeInMB * 1024 * 1024 && quality > 0.1);

        fetch(dataUrl)
          .then(res => res.blob())
          .then(blob => resolve(new File([blob], file.name, { type: 'image/jpeg' })));
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });
};

const submitIdea = async () => {
  if(imageFile.value || textInput.value){
  const compressedImage = imageFile.value ? await compressImage(imageFile.value) : null;
  
  const formData = new FormData();
  formData.append('contributor_id', personalContributor.value.id);
  formData.append('session_id', sessionId.value);

  if (compressedImage) {
    formData.append('image_file', compressedImage);
  }
  
  formData.append('text_input', textInput.value);

  try {
    const response = await axios.post('/api/idea', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    console.log('Server response:', response.data);
    textInput.value = '';
    imageFile.value = null;
    imageFileUrl.value = '';
  } catch (error) {
    console.error('Error saving idea', error);
  }
}else {errorMsg.value="Du musst entweder eine Text-Idee oder eine Bild-Idee einfügen, bevor du die Idee speicherst"}
};

onMounted(() => {
  sessionId.value = route.params.id;
  personalContributor.value = props.personalContributor;
  console.log(personalContributor.value, sessionId.value);
});

const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const recognition = new SpeechRecognition();

recognition.continuous = true;
recognition.interimResults = true;
recognition.lang = "de-DE";

recognition.onresult = (event) => {
  const transcript = Array.from(event.results)
    .map((result) => result[0])
    .map((result) => result.transcript)
    .join("");

  textInput.value = transcript;
};

watch(isListening, () => {
  if (isListening.value) {
    recognition.start();
  } else {
    recognition.stop();
  }
});
</script>
