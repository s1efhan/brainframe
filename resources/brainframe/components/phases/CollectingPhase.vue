<template>
  <div class="roundCountInfo__container">
    <div class="roundCount">
      <button @click="currentRound++">+</button><button @click="currentRound--">-</button>
      <div v-if="collectingRounds > 1" v-for="round in collectingRounds" :key="round" class="round-item">
        <div class="round-circle" :class="{ 'completed': round <= currentRound }">
          {{ round }}
        </div>
        <div v-if="round < collectingRounds" class="connecting-line" :class="{ 'completed': round < currentRound }">
        </div>
      </div>
    </div>
    <div @click="showInfo = !showInfo" class="info__container">
      <div class="join__info">
        <p>i</p>
      </div>
    </div>
  </div>
  <div v-if="showInfo" class="info__text__container">
    <div class="info__text">
      <h3>Sammel Phase:</h3>
      <ul>
        <li>Team sammelt Ideen.</li>
        <li>Je nach Methode: mehrere Runden mit <strong>Zeit- </strong> oder <strong>Ideen-Limit.</strong></li>
        <li>Einreichung: <strong>Bild</strong> (PNG, PDF, JPEG), <strong>Sprach- </strong> oder
          <strong>Texteingabe.</strong>
        </li>
        <li>Fotos werden von der <strong>BrainFrame KI in Text </strong> umgewandelt.</li>
      </ul>
    </div>
  </div>
  <button @click="getIdeasPassed">Test API Aufruf</button>
  <div class="passed-ideas__container">
    <h3>Inspirationen deiner Session Nachbarn</h3>
    <ul v-if="passedIdeas && collectingStarted" v-for="(idea, index) in passedIdeas">
      <li :class="'round-'+ idea.round">{{ idea.idea_title }}
        {{ idea.contributorIcon }}</li>
    </ul>
  </div>
  <div class="startCollecting" v-if="!collectingStarted">
    <button class="primary"
      v-if="personalContributor && sessionHostId == personalContributor.id && personalContributor.role_name != 'Default' && showStartButton || collectingRounds === 1 && personalContributor.role_name != 'Default'"
      @click="callStartCollecting">Starte Runde</button>
  </div>

  <form v-if="collectingStarted" class="collectForm" @submit.prevent="handleSubmit">
    <input type="file" id="image" ref="fileInput" @change="handleFileChange" />
    <div class="Input__container">
      <textarea id="textInput" :placeholder="iceBreakerMsg" v-model="textInput" rows="5"></textarea>
      <div class="inputImage__container">
        <img @click="openFileInput" :src="imageFileUrl ? imageFileUrl : '/storage/brainframe/images/404.png'"
          alt="uploadedImageIdea" height="100">
        <button class="secondary" v-if="!isListening" type="button" @click="isListening = true">🎙️</button>
        <button class="secondary" v-else type="button" @click="isListening = false">⏸</button>
        <button @click="iceBreaker">✨</button>
      </div>
    </div>
    <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
  </form>
  <div class="stopCollecting" v-if="collectingStarted">
    <button class="primary" type="submit" @click="isListening = false, submitIdea(true);"
      :disabled="submittedIdeas >= maxIdeaInput && maxIdeaInput">Idee speichern</button>
    <button class="secondary" v-if="personalContributor && sessionHostId == personalContributor.id"
      @click="callStopCollecting">Beende Runde</button>
  </div>
  <div class="timer" :style="{ '--progress': `${(1 - remainingTime / collectingTimer) * 360}deg` }">
    {{ remainingTime }}
  </div>
  <div v-if="maxIdeaInput" class="ideasCount">
    {{ submittedIdeas }} | {{ maxIdeaInput }}
    <p class="ideas-icon" v-if="submittedIdeas === maxIdeaInput">✓</p>
  </div>
  <div v-else class="ideasCount">
    {{ submittedIdeas }}
  </div>

</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
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
const showInfo = ref(false);
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
const personalContributor = ref(props.personalContributor);
const passedIdeas = ref(null);
let timer = null;
const getIdeasPassed = () => {
  console.log("getIdeasPassed", sessionId.value, props.personalContributor.id, currentRound.value)
  axios.get(`/api/ideas/6-3-5/${sessionId.value}/${props.personalContributor.id}/${currentRound.value}`)
    .then(response => {
      passedIdeas.value = response.data;
      console.log('passedIdeas:', passedIdeas.value);
    })
    .catch(error => {
      console.error('Error fetching passedIdeas', error);
    });
}
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
const emit = defineEmits(['switchPhase']);
const stopCollecting = () => {
  collectingStarted.value = false;
  clearInterval(timer);
  if (currentRound.value < collectingRounds.value) {
    if (personalContributor.value.id == sessionHostId.value) {
      axios.post('/api/ideas/sendToGPT', {
        session_id: sessionId.value,
        method_name: method.name,
        round: currentRound.value
      })
        .then(response => {
          console.log('Server response:', response.data);
          // Extrahiere nur den Content aus der API-Antwort
          if (response.data.choices && response.data.choices.length > 0) {
            const content = response.data.choices[0].message.content;
            try {
              // Entferne die Markdown-Codeblöcke und parse als JSON
              const cleanedContent = content.replace(/```json|\n```/g, '').trim();
              apiAntwort.value = JSON.parse(cleanedContent);
            } catch (error) {
              console.error('Fehler beim Parsen des JSON:', error);
              apiAntwort.value = 'Fehler beim Verarbeiten der Antwort';
            }
          } else {
            apiAntwort.value = 'Keine Antwort erhalten.';
          }
        })
        .catch(error => {
          console.error('Fehler bei der API-Anfrage', error);
          apiAntwort.value = 'Fehler bei der API-Anfrage';
        });
    }
    currentRound.value++;
    showStartButton.value = true;
  } else {
    showStartButton.value = false;
    emit('switchPhase', 'votingPhase');
    if (personalContributor.value.id == sessionHostId.value && method.name != "6-3-5")
      axios.post('/api/ideas/sendToGPT', {
        session_id: sessionId.value
      })
        .then(response => {
          console.log('Server response:', response.data);
          // Extrahiere nur den Content aus der API-Antwort
          if (response.data.choices && response.data.choices.length > 0) {
            const content = response.data.choices[0].message.content;
            try {
              // Entferne die Markdown-Codeblöcke und parse als JSON
              const cleanedContent = content.replace(/```json|\n```/g, '').trim();
              apiAntwort.value = JSON.parse(cleanedContent);
            } catch (error) {
              console.error('Fehler beim Parsen des JSON:', error);
              apiAntwort.value = 'Fehler beim Verarbeiten der Antwort';
            }
          } else {
            apiAntwort.value = 'Keine Antwort erhalten.';
          }
        })
        .catch(error => {
          console.error('Fehler bei der API-Anfrage', error);
          apiAntwort.value = 'Fehler bei der API-Anfrage';
        });
  }
};
const iceBreakerMsg = ref('');
const iceBreaker = () => {
  axios.post('/api/ice-breaker',
    {
      session_id: sessionId.value,
      contributor_id: personalContributor.value.id
    })
    .then(response => {
      console.log(response.data);
      iceBreakerMsg.value = response.data.iceBreaker_msg;
      console.log(iceBreakerMsg.value);
    })
    .catch(error => {
      console.error('Error fetching iceBreaker', error);
      iceBreakerMsg.value = "Fehler beim IceBreaker!";
    });
}
const apiAntwort = ref(null);
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
      // Hier die Axios-Anfrage einfügen
      axios.post('/api/countdown/put', {
        current_round: currentRound.value,
        session_id: sessionId.value,
        current_phase: 'Collecting Phase',
        seconds_left: remainingTime.value
      })
        .then(response => {

        })
        .catch(error => {
          console.error('Error updating countdown', error);
        });

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
watch(() => props.personalContributor, (newVal) => {
  personalContributor.value = newVal;
});
watch(() => props.sessionHostId, (newVal) => {
  sessionHostId.value = newVal;
});
watch(() => props.method, (newVal) => {
  method.value = newVal;
});
const contributors = ref(null);
const maxIdeaInput = ref(0);
const collectingRounds = ref(null);
const collectingTimer = ref(null);

const setMethodParameters = () => {
  switch (method.value.id) {
    case 1: // 6-3-5
      console.log("setMethodParameters", contributors.value.length)
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
      collectingRounds.value = 6; //fraglich !!!!
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
  console.log(personalContributor.value)
  method.value = props.method;
  sessionHostId.value = props.sessionHostId;
  setMethodParameters();
  Echo.channel('session.' + sessionId.value)
    .listen('StartCollecting', (e) => {
      console.log('StartCollecting Event empfangen:', e);
      if (currentRound.value > 1) {
        getIdeasPassed();
      }
      startCollecting();
    })
    .listen('StopCollecting', (e) => {
      console.log('StopCollecting Event empfangen:', e);
      stopCollecting();
      console.log("collectingStarted", collectingStarted.value)
    });
  if (personalContributor.value.id != sessionHostId.value) {
    Echo.channel('session.' + sessionId.value)
      .listen('UpdateCountdown', (e) => {
        remainingTime.value = e.secondsLeft;
        currentRound.value = e.round;
        if (remainingTime.value < collectingTimer.value) {
          collectingStarted.value = true;
        }
      });
  }
});
</script>
