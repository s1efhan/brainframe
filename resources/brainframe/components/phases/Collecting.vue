<template>
    <h2 class="collecting__header">
        <LightbulbIcon />
    </h2>
    <div class="roundCountInfo__container">

        <div class="roundCount">
            <div v-for="round in session.method.round_limit" :key="round" class="round-item">
                <div class="round-circle" :class="{ 'completed': round <= session.collecting_round }">
                    {{ round }}
                </div>
                <div v-if="round < session.method.round_limit" class="connecting-line"
                    :class="{ 'completed': round < session.collecting_round }">
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
    <form class="collectForm" @submit.prevent="handleSubmit">
        <input type="file" id="image" ref="fileInput" @change="handleFileChange" />
        <div class="Input__container">
            <textarea @input="inActiveSince = 0" id="textInput"
                :placeholder="iceBreakerMsg ? iceBreakerMsg : placeholderMsg" v-model="textInput" rows="12"></textarea>

            <div class="input__container" id="input__container">

                <button class="file_send" @click="openFileInput">
                    <section> <img class="input__image" v-if="imageFileUrl && showImage" :src="imageFileUrl"
                            alt="uploadedImageIdea" height="100">
                        <l-dot-pulse v-if="!showImage" size="43" speed="1.3" color="#91b4b2"></l-dot-pulse>
                        <DefaultimageIcon class="input__image" v-else />
                        <p>Foto Idee</p>
                    </section>
                </button>
                <!--
     <button v-if="!isListening" type="button" @click="isListening = true">
       <MicrophoneIcon />
     </button>
     <button v-else type="button" @click="isListening = false">
       <l-waveform size="35" stroke="2.5" speed="0.8" color="white"></l-waveform></button>
      -->
                <button class="ice_breaker" @click="iceBreaker">

                    <section :class="{ 'ice-breaker-animation': inActiveSince > 15 }" v-if="!iceBreakerLoading">
                        <AiStarsIcon />
                        <p>Eisbrecher</p>
                    </section>

                    <l-dot-pulse v-else size="43" speed="1.3" color="#91b4b2"></l-dot-pulse>
                </button>
            </div>

        </div>
        <!-- <p v-if="isListening" class="recording-status">Aufnahme läuft...</p>-->
        <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
    </form>
    <div class="collecting__bottom__container">
        <div v-if="session.method.idea_limit > 0" class="ideasCount">
            {{ personalIdeasCount }} | {{ session.method.idea_limit }}
            <p class="ideas-icon" v-if="personalIdeasCount === session.method.idea_limit">✓</p>
        </div>
        <div v-else class="ideasCount">
            {{ personalIdeasCount }}
        </div>
        <div class="collecting__buttons">
            <button class="primary" type="submit" @click="submitIdea"
                :disabled="personalIdeasCount >= session.method.idea_limit && session.method.idea_limit">Idee
                speichern</button>
            <!-- <button class="secondary" v-if="personalContributor.isHost" @click="emit('stop')">Beende Runde</button>-->
        </div>
    </div>

    <div v-if="session.method.name === '6-3-5' && session.collecting_round > 1 && neighbourIdeas"
        class="passed-ideas__container">
        <ul v-for="(idea, index) in neighbourIdeas">
            <li :class="'round-'+ idea.round">
                <div>{{ idea.title }}</div>
                <div>
                    <component
                        :is="getIconComponent(props.contributors.find(c => c.id === idea.contributor_id).icon)" />
                </div>
            </li>
        </ul>
    </div>

</template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import LightbulbIcon from '../icons/LightbulbIcon.vue';
import DefaultimageIcon from '../icons/DefaultimageIcon.vue';
import MicrophoneIcon from '../icons/MicrophoneIcon.vue';
import IconComponents from '../IconComponents.vue';
import AiStarsIcon from '../icons/AiStarsIcon.vue';
const getIconComponent = (iconName) => {
    console.log("iconName", iconName);
    return IconComponents[iconName] || null;
};
import { dotPulse } from 'ldrs'
const inActiveSince = ref(0);

dotPulse.register()
const showInfo = ref(false);

// soll true werden, wenn der Nutzer seit 30 Sekunden nichts ins input eingibt. 
// soll false werden wenn Nutzer tippt
const props = defineProps({
    personalContributor: {
        type: Object,
        required: true
    },
    session: {
        type: Object,
        required: true
    },
    ideas: {
        type: Object,
        required: true
    },
    contributors: {
        type: Object,
        required: true
    }
});
const iceBreakerLoading = ref(false);
const neighbourIdeas = computed(() => {
    const currentRound = parseInt(props.session.collecting_round);
    const currentContributorId = props.personalContributor.id;
    console.log(`Current round: ${currentRound}, Current contributor ID: ${currentContributorId}`);

    if (currentRound <= 1) {
        console.log('Round 1 or less, returning empty array');
        return [];
    }

    console.log(`Total ideas in props: ${props.ideas.length}`);
    console.log('Sample of props.ideas:', props.ideas.slice(0, 2));

    const validIdeas = props.ideas.filter(idea => {
        console.log(`Checking idea:`, idea);
        console.log(`Has tag: ${Boolean(idea.tag)}`);
        return idea.tag;
    });
    console.log(`Valid ideas: ${validIdeas.length}`);

    if (validIdeas.length === 0) {
        console.log('No valid ideas found. Check if "tag" is the correct property to filter by.');
        console.log('Available properties on idea object:', Object.keys(props.ideas[0] || {}));
    }

    // Verwenden Sie props.contributors anstelle von sortedContributors
    console.log(`Contributors: ${props.contributors.map(c => c.id).join(', ')}`);

    const neighbourIdeas = [];
    for (let i = 1; i < currentRound; i++) {
        const targetRound = currentRound - i;
        const neighbourId = findNeighbourId(props.contributors.map(c => c.id), currentContributorId, i);
        console.log(`Looking for ideas from neighbour ${neighbourId} in round ${targetRound}`);

        const neighbourIdeasInRound = validIdeas.filter(idea =>
            idea.contributor_id === neighbourId &&
            parseInt(idea.round) === targetRound
        );
        console.log(`Found ${neighbourIdeasInRound.length} ideas for this neighbour and round`);
        neighbourIdeas.push(...neighbourIdeasInRound);
    }

    console.log(`Total neighbour ideas found: ${neighbourIdeas.length}`);
    return neighbourIdeas;
});

function findNeighbourId(contributorIds, currentId, offset) {
    const currentIndex = contributorIds.indexOf(currentId);
    const neighbourIndex = (currentIndex + offset) % contributorIds.length;
    const neighbourId = contributorIds[neighbourIndex];
    console.log(`Finding neighbour: current index ${currentIndex}, offset ${offset}, neighbour index ${neighbourIndex}, neighbour ID ${neighbourId}`);
    return neighbourId;
}

const personalIdeasCount = computed(() => {
    return props.ideas.filter(idea =>
        idea.round == props.session.collecting_round &&
        idea.contributor_id == props.personalContributor.id
    ).length;
});
const personalContributor = ref(props.personalContributor)
const emit = defineEmits(['stop', 'wait']);
const session = ref(props.session);
const textInput = ref('');
const fileInput = ref(null);
const imageFile = ref(null);
const imageFileUrl = ref('');
const errorMsg = ref('');
const iceBreakerMsg = ref('');
const placeholderMsg = computed(() => {
  if (session.value.method.name === 'Walt Disney') {
    switch (session.value.collecting_round) {
      case 1:
        return "Sei ein Träumer"
      case 2:
        return "Sei ein Realist"
      case 3:
        return "Sei ein Kritiker"
      default:
        return "Bitte gib deine Idee für diese Runde ein"
    }
  } else if (session.value.method.name === '6 Thinking Hats') {
    switch (personalContributor.value.name) {
      case 'gelber Hut':
        return "Denke optimistisch: Was sind die Vorteile und positiven Aspekte?"
      case 'roter Hut':
        return "Folge deiner Intuition: Was fühlst du bei dieser Idee?"
      case 'blauer Hut':
        return "Betrachte den Prozess: Wie können wir die Diskussion strukturieren?"
      case 'weißer Hut':
        return "Konzentriere dich auf Fakten: Welche Informationen haben wir?"
      case 'grüner Hut':
        return "Sei kreativ: Welche neuen Ideen oder Alternativen gibt es?"
      case 'schwarzer Hut':
        return "Sei vorsichtig: Welche Risiken oder Probleme siehst du?"
      default:
        return "Bitte gib deine Gedanken entsprechend deiner Hutfarbe ein"
    }
  } else {
    return "Bitte gib hier deine Idee ein."
  }
});

const iceBreaker = () => {
    iceBreakerMsg.value = null;
    iceBreakerLoading.value = true;
    const fileInput = ref(null);
    const textInput = ref('');
    axios.post('/api/session/ice-breaker', {
        session_id: session.value.id,
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
        })
        .finally(() => {
            inActiveSince.value = 0;
            iceBreakerLoading.value = false;
        });
}
const showImage = ref(true);
const submitIdea = async () => {
    if (personalIdeasCount >= session.value.method.idea_limit && session.value.method.idea_limit > 0) {
        errorMsg.value = "Maximale Anzahl an Ideen für diese Runde erreicht.";
        return;
    }
    if (imageFile.value) {
        showImage.value = false;
    }
    if (imageFile.value || textInput.value) {
        const compressedImage = imageFile.value ? await compressImage(imageFile.value) : null;

        const formData = new FormData();
        formData.append('contributor_id', personalContributor.value.id);
        formData.append('session_id', session.value.id);
        formData.append('round', session.value.collecting_round);

        if (compressedImage) {
            formData.append('image_file', compressedImage);
        }
        formData.append('text_input', textInput.value);
        try {
            const response = await axios.post('/api/idea/store', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            console.log('Server response:', response.data);
            textInput.value = '';
            imageFile.value = null;
            imageFileUrl.value = '';
            // personalIdeasCount++; geht nicht, stattdessen Event und ganze ideas updaten
            iceBreakerMsg.value = "Trage hier deine Idee ein.";
        } catch (error) {
            console.error('Error saving idea', error);
        }
        showImage.value = true;
    } else {
        errorMsg.value = "Du musst entweder eine Text-Idee oder eine Bild-Idee einfügen, bevor du die Idee speicherst";
    }
};

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
onMounted(() => {
    const intervalId = setInterval(() => {
        inActiveSince.value++;
    }, 1000);
    if (session.value.collecting_round > 1) {
        showInfo.value = false;
    }
    else { showInfo.value = true; }
    console.log("neighbourIdeas", neighbourIdeas.value);
});
</script>