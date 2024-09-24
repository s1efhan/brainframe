<template>
  <div v-if="isLoading">
    <l-quantum size="45" speed="1.75" color="white"></l-quantum>
  </div>
  <div v-else>
    <h2 class="collecting__header">
      <LightbulbIcon />
    </h2>
    <div class="roundCountInfo__container">

      <div class="roundCount">
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
    <!-- <button @click="getIdeasPassed">Test API Aufruf</button>-->

    <form v-if="collectingStarted" class="collectForm" @submit.prevent="handleSubmit">
      <input type="file" id="image" ref="fileInput" @change="handleFileChange" />
      <div class="Input__container">
        <textarea id="textInput" :placeholder="iceBreakerMsg" v-model="textInput" rows="12"></textarea>

        <div class="input__container" id="input__container">

          <!-- <button @click="openFileInput">  kostet leider viel zu viele Tokens (8.000)
       <img class="input__image" v-if="imageFileUrl" :src="imageFileUrl"
         alt="uploadedImageIdea" height="100">
       <DefaultimageIcon class="input__image" @click="openFileInput" v-else />
     </button>
      -->
          <!--
     <button v-if="!isListening" type="button" @click="isListening = true">
       <MicrophoneIcon />
     </button>
     <button v-else type="button" @click="isListening = false">
       <l-waveform size="35" stroke="2.5" speed="0.8" color="white"></l-waveform></button>
      -->
          <button @click="iceBreaker">

            <AiStarsIcon />
          </button>
        </div>

      </div>
      <!-- <p v-if="isListening" class="recording-status">Aufnahme läuft...</p>-->
      <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
    </form>
    <div v-if="passedIdeas && collectingStarted" class="passed-ideas__container">
      <h3>Inspirationen deiner Session Nachbarn</h3>
      <ul v-for="(idea, index) in passedIdeas">
        <li :class="'round-'+ idea.round">
          <div>{{ idea.idea_title }}</div>
          <div>
            <component :is="getIconComponent(idea.contributorIcon)" />
          </div>
        </li>
      </ul>
    </div>
    <div class="collecting__bottom__container">
      <div v-if="maxIdeaInput" class="ideasCount">
        {{ submittedIdeas }} | {{ maxIdeaInput }}
        <p class="ideas-icon" v-if="submittedIdeas === maxIdeaInput">✓</p>
      </div>
      <div v-else class="ideasCount">
        {{ submittedIdeas }}
      </div>
      <div class="collecting__buttons">
        <button class="primary"
          v-if="!collectingStarted && personalContributor && sessionHostId == personalContributor.id && personalContributor.role_name != 'Default' && showStartButton || collectingRounds === 1 && personalContributor.role_name != 'Default'"
          @click="callStartCollecting">Starte Runde</button>
        <button v-if="collectingStarted" class="primary" type="submit" @click="isListening = false, submitIdea(true);"
          :disabled="submittedIdeas >= maxIdeaInput && maxIdeaInput">Idee speichern</button>
        <button class="secondary"
          v-if="collectingStarted && personalContributor && sessionHostId == personalContributor.id"
          @click="callStopCollecting">Beende Runde</button>
      </div>
      <div class="timer__container">
        <SandclockIcon />
        <div class="timer" :style="{ '--progress': `${(1 - remainingTime / collectingTimer) * 360}deg` }">
          {{ remainingTime }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import axios from 'axios';
import IconComponents from '../IconComponents.vue';
import { quantum } from "ldrs";
import LightbulbIcon from '../icons/LightbulbIcon.vue';
import DefaultimageIcon from '../icons/DefaultimageIcon.vue';
import MicrophoneIcon from '../icons/MicrophoneIcon.vue';
import SandclockIcon from '../icons/SandclockIcon.vue';
import AiStarsIcon from '../icons/AiStarsIcon.vue';
import {waveform} from "ldrs"
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
  },
  currentRound: {
    type: [Number, null],
    required: true
  },
  ideasCount: {
    type: [Object, null],
    required: true
  }
});

const emit = defineEmits(['switchPhase', 'getContributors']);
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
quantum.register();
// Zeitmessung und Runden
const currentRound = ref(props.currentRound || 1);
const collectingTimer = ref(null);
const remainingTime = ref(null);
let timerTimeout;
// Sitzungsinformationen
const sessionId = ref(props.sessionId);
const sessionHostId = ref(props.sessionHostId);
const method = ref(props.method);
const collectingRounds = ref(null);

// Teilnehmer und Beiträge
const personalContributor = ref(props.personalContributor);
const contributors = ref(props.contributors);
const submittedIdeas = ref(props.ideasCount?.[currentRound.value]?.contributors?.[personalContributor.value.id] ?? 0);
const passedIdeas = ref(null);

// Eingabe und Interaktion
const textInput = ref('');
// const fileInput = ref(null);
 const imageFile = ref(null);
const imageFileUrl = ref('');
const maxIdeaInput = ref(null);

// UI-Zustände
const showInfo = ref(false);
const collectingStarted = ref(false);
// const isListening = ref(false);
const showStartButton = ref(true);
const isLoading = ref(false);

const errorMsg = ref('');
const iceBreakerMsg = ref('');

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
      maxIdeaInput.value = null;
      collectingTimer.value = 360;
      break;
    case 4: // 6 Thinking Hats
      collectingRounds.value = 6;
      collectingTimer.value = 360;
      break;
    default:
      console.warn('Unbekannte Methode ID:', method.value.id);
  }
  if (remainingTime.value === null) {
    remainingTime.value = collectingTimer.value;
  }
};

const getIdeasPassed = () => {
  isLoading.value = true;
  console.log("getIdeasPassed", sessionId.value, props.personalContributor.id, currentRound.value)
  axios.get(`/api/ideas/6-3-5/${sessionId.value}/${props.personalContributor.id}/${currentRound.value}`)
    .then(response => {
      passedIdeas.value = response.data;
      console.log('passedIdeas:', passedIdeas.value);
    })
    .catch(error => {
      console.error('Error fetching passedIdeas', error);
    })
    .finally(() => {
      isLoading.value = false;
    });
}

const callStartCollecting = () => {
  axios.post('/api/collecting/start', {
    session_id: sessionId.value,
    current_round: currentRound.value,
    collecting_timer: collectingTimer.value
  })
    .then(response => {
      console.log('Server response:', response.data);
    })
    .catch(error => {
      console.error('Error starting Collecting', error);
    });
};


const sendToGPT = (round = null) => {
  isLoading.value = true;
  console.log("API GPT");
  axios.post('/api/ideas/sendToGPT', {
    session_id: sessionId.value,
    method_name: method.value.name,
    host_id: sessionHostId.value,
    round: round
  })
    .then(response => {
      console.log('Server response:', response.data);
    })
    .catch(error => {
      console.error('Fehler bei der API-Anfrage', error);
    })
    .finally(() => {
      isLoading.value = false;
    });
};

const iceBreaker = () => {
    axios.post('/api/ice-breaker', {
        session_id: sessionId.value,
        contributor_id: personalContributor.value.id
    })
    .then(response => {
        iceBreakerMsg.value = response.data.iceBreaker_msg;
    })
    .catch(error => {
        if (error.response && error.response.status === 403) {
            errorMsg.value = 'Maximale Anzahl an Anfragen erreicht (3 pro Session)';
        } else {
            errorMsg.value = 'Ein Fehler ist aufgetreten';
        }
    });
}
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
isLoading.value = true;
    try {
      const response = await axios.post('/api/idea', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      console.log('Server response:', response.data);
      textInput.value = '';
      imageFile.value = null;
      imageFileUrl.value = '';
      submittedIdeas.value++;
      iceBreakerMsg.value = "";
    } catch (error) {
      console.error('Error saving idea', error);
    }
  } else {
    errorMsg.value = "Du musst entweder eine Text-Idee oder eine Bild-Idee einfügen, bevor du die Idee speicherst";
  }
  isLoading.value = false;
};


const startCollecting = () => {
  collectingStarted.value = true;
  console.log("collectingStarted.value = true");
  submittedIdeas.value = props.ideasCount?.[currentRound.value]?.contributors?.[personalContributor.value.id] ?? 0;
  showStartButton.value = false;
  updateTimerState();  // Starte den Timer direkt
};

const stopCollecting = () => {
  collectingStarted.value = false;
  clearTimeout(timerTimeout);
  timerTimeout = null;
console.log("stopCollecting aufgerufen");
  if (personalContributor.value.id == sessionHostId.value) {
    if (currentRound.value < collectingRounds.value) {
      if (method.value.id === 4) { // nur bei 6-Thinking Hats
        emit('getContributors');
      }
      if (method.value.name == '6-3-5') {
        console.log("1")
     sendToGPT(currentRound.value);
      }
      currentRound.value++;
      showStartButton.value = true;
    } else {
      showStartButton.value = false;
      if (method.value.name != '6-3-5') {
        console.log("2")
      sendToGPT();
      } else {
        // Für 6-3-5 Methode: Sende nur, wenn nicht bereits in der letzten Runde gesendet wurde
        if (currentRound.value === collectingRounds.value) {
          console.log("3")
       sendToGPT(currentRound.value);
       emit("switchPhase", "votingPhase")
        }
      }
    }
  } else {
    if (currentRound.value < collectingRounds.value) {
      currentRound.value++;
      showStartButton.value = true;
    } else {
      showStartButton.value = false;
    }
  }
  if(currentRound.value > collectingRounds.value){
    emit('switchPhase', 'votingPhase');
  }
};

const callStopCollecting = () => {
  if (sessionHostId.value === personalContributor.value.id) {
    axios.post('/api/collecting/stop', {
      current_round: currentRound.value + 1,
      session_id: sessionId.value
    })
      .then(response => {
        console.log('Server response:', response.data);
      })
      .catch(error => {
        console.error('Error stopping Collecting', error);
      });
  }
};

const updateTimerState = () => {
  if (remainingTime.value > 0) {
    remainingTime.value--;
    timerTimeout = setTimeout(updateTimerState, 1000);
  } else {
    collectingStarted.value = false;
  }
};

const getCountdown = () => {
  axios.get(`/api/collecting/timer/${props.sessionId}`)
    .then(response => {
      console.log("countdown", response.data)
      if (response.data.seconds_left >0) {
        collectingStarted.value = true;
        remainingTime.value = response.data.seconds_left;
          updateTimerState();
      }
    })
    .catch(error => console.error('Error fetching timer status:', error));
};
onMounted(() => {
  isLoading.value = true;
  setMethodParameters();
  if(currentRound.value > collectingRounds.value && personalContributor.value.id === sessionHostId.value){
    emit("switchPhase", "votingPhase");
  }
  Echo.channel(`session.${sessionId.value}`)
    .listen('StartCollecting', () => {
      if (currentRound.value > 1 && method.value.name === "6-3-5") {
        getIdeasPassed();
      }
      startCollecting();
    })
    .listen('StopCollecting', () => {
      console.log("stop Collecting Event empfangen")
      stopCollecting();
    });
  if (currentRound.value > 1 && method.value.name === "6-3-5") {
    getIdeasPassed();
  }
  getCountdown();
  isLoading.value = false;
});

watch(() => personalContributor.value.id, (newId, oldId) => {
  if (newId === sessionHostId.value && oldId !== sessionHostId.value) {
    isActiveTimer.value = true;
  }
});
watch(() => props.personalContributor, (newVal) => {
  personalContributor.value = newVal;
});
watch(() => props.sessionHostId, (newVal) => {
  sessionHostId.value = newVal;
});
watch(() => props.method, (newVal) => {
  method.value = newVal;
});

/*
const handleFileChange = (event) => {
  imageFile.value = event.target.files[0];
  imageFileUrl.value = URL.createObjectURL(imageFile.value)
  console.log('File selected:', imageFileUrl.value);
};

const openFileInput = () => {
  fileInput.value.click();
}

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
*/
</script>
