<template>
     <div v-if="isLoading"> 
<l-quantum
  size="45"
  speed="1.75"
  color="white" 
></l-quantum>
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
        <button>
          <img class="input__image" v-if="imageFileUrl" @click="openFileInput" :src="imageFileUrl"
            alt="uploadedImageIdea" height="100">
          <DefaultimageIcon class="input__image" @click="openFileInput" v-else />
        </button>
        <button v-if="!isListening" type="button" @click="isListening = true">
          <MicrophoneIcon />
        </button>
        <button v-else type="button" @click="isListening = false">
          <l-waveform size="35" stroke="2.5" speed="0.8" color="white"></l-waveform></button>
        <button @click="iceBreaker">
          <AiStarsIcon />
        </button>
      </div>
    </div>
    <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
  </form>
  <div v-if="passedIdeas && collectingStarted" class="passed-ideas__container">
    <h3>Inspirationen deiner Session Nachbarn</h3>
    <ul v-for="(idea, index) in passedIdeas">
      <li :class="'round-'+ idea.round"><div>{{ idea.idea_title }}</div>
        <div><component :is="getIconComponent(idea.contributorIcon)" /></div></li>
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
import 'ldrs/quantum';
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

import LightbulbIcon from '../icons/LightbulbIcon.vue';
import DefaultimageIcon from '../icons/DefaultimageIcon.vue';
import MicrophoneIcon from '../icons/MicrophoneIcon.vue';
import SandclockIcon from '../icons/SandclockIcon.vue';
import AiStarsIcon from '../icons/AiStarsIcon.vue';
import 'ldrs/waveform'
const showInfo = ref(false);
const collectingStarted = ref(false);
const currentRound = ref(props.currentRound || 1);
const remainingTime = ref(0);
const textInput = ref('');
const errorMsg = ref('');
const fileInput = ref(null);
const sessionId = ref(props.sessionId);
const isListening = ref(false);
const showStartButton = ref(true);
const isLoading = ref(false);
const personalContributor = ref(props.personalContributor);
const passedIdeas = ref(null);
const method = ref(props.method);
const contributors = ref( props.contributors);
const maxIdeaInput = ref(0);
const collectingRounds = ref(null);
const collectingTimer = ref(null);
const imageFile = ref(null)
const imageFileUrl = ref('')
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const recognition = new SpeechRecognition();
const submittedIdeas = ref(props.ideasCount?.[currentRound.value]?.contributors?.[personalContributor.value.id] ?? 0);
const sessionHostId = ref(props.sessionHostId);
let timer = null;
const emit = defineEmits(['switchPhase','getContributors']);

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
};

const callStartCollecting = () => {
  axios.post('/api/collecting/start', {
    session_id: sessionId.value,
    current_round: currentRound.value
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
  console.log("collectingStarted.value =true")
  submittedIdeas.value =  props.ideasCount?.[currentRound.value]?.contributors?.[personalContributor.value.id] ?? 0;
  startTimer();
  showStartButton.value = false;
};

const stopCollecting = () => {
  collectingStarted.value = false;
  clearInterval(timer);
  if (currentRound.value < collectingRounds.value) {
    if(method.value.id === 4)// nur bei 6-Thinking Hats
    { emit('getContributors');}
    if (personalContributor.value.id == sessionHostId.value && method.value.name == '6-3-5') {
  console.log('post /sendtoGPT');
  isLoading.value = true;

  axios.post('/api/ideas/sendToGPT', {
    session_id: sessionId.value,
    method_name: method.value.name,
    round: currentRound.value
  })
  .then(response => {
    console.log('Server response:', response.data);
  })
  .catch(error => {
    console.error('Fehler bei der API-Anfrage', error.message.error);
  })
  .finally(() => {
    isLoading.value = false;
  });
}
    currentRound.value++;
    showStartButton.value = true;
  } else {
    showStartButton.value = false;
    if (personalContributor.value.id == sessionHostId.value)
    isLoading.value = true; 
      axios.post('/api/ideas/sendToGPT', {
        session_id: sessionId.value,
        method_name: method.value.name
      })
        .then(response => {
          console.log('Server response:', response.data);
          emit('switchPhase', 'votingPhase');
        })
  
        .catch(error => {
          console.error('Fehler bei der API-Anfrage', error);
          emit('switchPhase', 'lobby');
        })
        .finally(() => {
          isLoading.value = false;
        });
       
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
        console.error('Error stoping Collecting', error);
      });
  }
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
        submittedIdeas.value = props.ideasCount?.[currentRound.value]?.contributors?.[personalContributor.value.id] ?? 0;
        console.log('CurrentRound: ', currentRound.value)
        console.log('submittedIdeas: ', submittedIdeas.value)
        startTimer();
      } else {
        callStopCollecting();
      }
    }
  }, 1000);
};

const openFileInput = () => {
  fileInput.value.click();
  //timer starten
}
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
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
watch(() => props.personalContributor, (newVal) => {
  personalContributor.value = newVal;
});
watch(() => props.sessionHostId, (newVal) => {
  sessionHostId.value = newVal;
});
watch(() => props.method, (newVal) => {
  method.value = newVal;
});


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
  setMethodParameters();
/*
    if(currentRound.value >= collectingRounds.value){
      emit('switchPhase', 'votingPhase');
    }
  */
  Echo.channel('session.' + sessionId.value)
    .listen('StartCollecting', (e) => {
      console.log('StartCollecting Event empfangen:', e);
      console.log(currentRound.value);
      if (currentRound.value > 1 && method.value.name === "6-3-5") {
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
        if (remainingTime.value < collectingTimer.value) {
          collectingStarted.value = true;
        }
      });
  }
});
</script>
