<template>
  <h3>CollectingPhase</h3>
  <section>
    <div v-if="!collectingStarted">
      <p>Runde: {{ currentRound }} / {{ collectingRounds }} </p>
      <p>Ideen pro Runde: {{ maxIdeaInput }}</p>
      <p>Zeit pro Runde: {{ collectingTimer/60 }} Minute(n)</p>
      <button
        v-if="personalContributor &&  sessionHostId == personalContributor.id && showStartButton|| collectingRounds === 1"
        @click="callStartCollecting">Start Collecting</button>
    </div>

    <div v-if="collectingStarted">
      <p>Runde: {{ currentRound }} / {{ collectingRounds }} </p>
      <p>Verbleibende Zeit: {{ remainingTime }} Sekunden</p>
      <p>Eingereichte Ideen: {{ submittedIdeas }} {{"/". maxIdeaInput }}</p>

      <form @submit.prevent="handleSubmit">
        <label for="image">Bild einfügen: </label>
        <br>
        <input type="file" id="image" ref="fileInput" @change="handleFileChange" />
        <img @click="openFileInput" :src="imageFileUrl ? imageFileUrl : '/fake-url'" alt="uploadedImageIdea"
          height="100">
        <br>
        <label for="textInput">Idee:</label>
        <input type="text" id="textInput" v-model="textInput">
        <button v-if="!isListening" type="button" @click="isListening = true">▶️</button>
        <button v-else type="button" @click="isListening = false">⏸️</button>
        <br>
        <button type="submit" @click="isListening = false, submitIdea(true);"
          :disabled="submittedIdeas >= maxIdeaInput && maxIdeaInput">Idee speichern</button>
        <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
      </form>

      <button v-if="personalContributor && sessionHostId == personalContributor.id" @click="callStopCollecting">Stop
        Collecting</button>
    </div>
  </section>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
const collectingStarted = ref(false);
const currentRound = ref(1);
const remainingTime = ref(0);
const submittedIdeas = ref(0);
const textInput = ref('');
const errorMsg = ref('');
const fileInput = ref(null);
const sessionId = ref(null);
const isListening = ref(false);
const showStartButton = ref(true)
const personalContributor = ref(null);
let timer = null;
const callStartCollecting = () => {
  axios.post('/api/collecting/start', {
    current_round: currentRound.value,
    session_id: sessionId.value
  })
    .then(response => {
      console.log('Server response:', response.data);
    })
    .catch(error => {
      console.error('Error starting Collecting', error);
    });
};
const startCollecting = () => {
  collectingStarted.value = true;
  submittedIdeas.value = 0;
  startTimer();
  showStartButton.value = false;  
};

const stopCollecting = () => {
  collectingStarted.value = false;
  clearInterval(timer);
  if (currentRound.value < collectingRounds.value) {
    currentRound.value++;
    showStartButton.value = true;  
  } else {
    showStartButton.value = false; 
  }
};

const callStopCollecting = () => {
  axios.post('/api/collecting/stop', {
    current_round: currentRound.value,
    session_id: sessionId.value
  })
    .then(response => {
      console.log('Server response:', response.data);
    })
    .catch(error => {
      console.error('Error stoping Collecting', error);
    });
};
const startTimer = () => {
  remainingTime.value = collectingTimer.value;
  timer = setInterval(() => {
    if (remainingTime.value > 0) {
      remainingTime.value--;
    } else {
      clearInterval(timer);
      if (currentRound.value < collectingRounds.value) {
        currentRound.value++;
        submittedIdeas.value = 0;
        startTimer();
      } else {
        callStopCollecting();
      }
    }
  }, 1000);
};
const sessionHostId = ref(null);
const openFileInput = () => {
  fileInput.value.click();
  //timer starten
}
// wenn Timer abgelaufen ist, dann stopCollecting
const props = defineProps({
  personalContributor: {
    type: Object,
    required: true
  },
  sessionHostId: {
    type: [String, Number],
    required: true
  },
  sessionId: {
    type: [String, Number],
    required: true
  },
  method: {
    type: Object,
    required: true
  },
  contributors: {
    type: [Object, null],
    required: true
  }
});
const contributors = ref(null);
const maxIdeaInput = ref(null);
const collectingRounds = ref(null);
const collectingTimer = ref(null);

const setMethodParameters = () => {
  switch (method.value.id) {
    case 1: // 6-3-5
      collectingRounds.value = contributors.value.length;
      maxIdeaInput.value = 3;
      collectingTimer.value = 360;
      break;
    case 2: // Crazy 8
      collectingRounds.value = 8;
      maxIdeaInput.value = 1;
      collectingTimer.value = 60;
      break;
    case 3: // Walt Disney
      collectingRounds.value = 3;
      collectingTimer.value = 360;
      break;
    case 4: // 6 Thinking Hats
      collectingRounds.value = 6;
      collectingTimer.value = 360;
      break;
    default:
      console.warn('Unbekannte Methode ID:', method.value.id);
  }
};
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
  if (submittedIdeas.value >= maxIdeaInput.value && maxIdeaInput.value !== null) {
    errorMsg.value = "Maximale Anzahl an Ideen für diese Runde erreicht.";
    return;
  }

  if (imageFile.value || textInput.value) {
    const compressedImage = imageFile.value ? await compressImage(imageFile.value) : null;

    const formData = new FormData();
    formData.append('contributor_id', personalContributor.value.id);
    formData.append('session_id', sessionId.value);
    formData.append('round', currentRound.value);

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
      submittedIdeas.value++;
    } catch (error) {
      console.error('Error saving idea', error);
    }
  } else {
    errorMsg.value = "Du musst entweder eine Text-Idee oder eine Bild-Idee einfügen, bevor du die Idee speicherst";
  }
};
const method = ref(null);

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
onMounted(() => {
  sessionId.value = props.sessionId;
  contributors.value = props.contributors;
  console.log(contributors.value.length);
  personalContributor.value = props.personalContributor;
  method.value = props.method;
  sessionHostId.value = props.sessionHostId;
  console.log("method", method.value.id);
  console.log("Collecting Method Value", method.value)
  console.log(personalContributor.value, sessionId.value);
  setMethodParameters();
  Echo.channel('session.' + sessionId.value)
    .listen('StartCollecting', (e) => {
      console.log('StartCollecting Event empfangen:', e);
      startCollecting();
    })
    .listen('StopCollecting', (e) => {
      console.log('StopCollecting Event empfangen:', e);
      stopCollecting();
      console.log("collectingStarted", collectingStarted.value)
    });
});
</script>
