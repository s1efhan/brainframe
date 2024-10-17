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
    <div class="role_challenge__container"
        v-if="session.method.name == '6 Thinking Hats' || session.method.name == 'Walt Disney' ">
        <p v-if="session.method.name == '6 Thinking Hats'"> Deine Rolle: {{ personalContributor.name }}</p>
        <p>Sammle Ideen: {{ roleChallengeMsg }}</p>
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
                <button :class="{ 'ice-breaker-animation': inActiveSince > 20 && !iceBreakerLoading}"
                    class="ice_breaker" @click="iceBreaker">

                    <section v-if="!iceBreakerLoading">
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
            <button class="primary" type="submit" @click="!ideaIsSending && submitIdea()"
                :disabled="personalIdeasCount >= session.method.idea_limit && session.method.idea_limit > 0">
                <template v-if="ideaIsSending">
                    <l-dot-pulse size="43" speed="1.3" color="#91b4b2"></l-dot-pulse>
                </template>
                <template v-else>
                    Idee speichern
                </template>
            </button>
        </div>
        <button class="accent stop" v-if="personalContributor.isHost" @click="confirmStop"> Runde beenden</button>
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
import { dotPulse } from 'ldrs'
dotPulse.register();
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

const showInfo = ref(false);
const iceBreakerLoading = ref(false);
const inActiveSince = ref(0); // soll true werden, wenn der Nutzer seit 30 Sekunden nichts ins input eingibt und false werden wenn Nutzer tippt
const personalContributor = ref(props.personalContributor)
const emit = defineEmits(['stop', 'wait']);
const session = ref(props.session);
const textInput = ref('');
const fileInput = ref(null);
const imageFile = ref(null);
const imageFileUrl = ref('');
const errorMsg = ref('');
const iceBreakerMsg = ref('');
const ideaIsSending = ref(false);
const showImage = ref(true);

const currentRoundIdeas = computed(() =>
    props.ideas.filter(idea => idea.round == session.value.collecting_round)
);

const getIconComponent = (iconName) => {
    return IconComponents[iconName] || null;
};
const confirmStop = () => {
    if (currentRoundIdeas.value.length > 0) {
        if (confirm('Aktuelle Runde vorzeitig beenden?')) {
            emit('stop');
        }
    } else {
        alert("Bitte geben Sie oder ein anderer Teilnehmer mindestens eine Idee ein");
    }
};

// Der folgende Codeabschnitt wurde mit Unterstützung von Claude 3.5 Sonnet erstellt
const neighbourIdeas = computed(() => {
    const currentRound = parseInt(props.session.collecting_round);
    const currentContributorId = props.personalContributor.id;
    if (currentRound <= 1) {
        return [];
    }
    const validIdeas = props.ideas.filter(idea => {
        return idea.tag;
    });

    const neighbourIdeas = [];
    for (let i = 1; i < currentRound; i++) {
        const targetRound = currentRound - i;
        const neighbourId = findNeighbourId(props.contributors.map(c => c.id), currentContributorId, i);
        const neighbourIdeasInRound = validIdeas.filter(idea =>
            idea.contributor_id === neighbourId &&
            parseInt(idea.round) === targetRound
        );
        neighbourIdeas.push(...neighbourIdeasInRound);
    }
    return neighbourIdeas;
});

function findNeighbourId(contributorIds, currentId, offset) {
    const currentIndex = contributorIds.indexOf(currentId);
    const neighbourIndex = (currentIndex + offset) % contributorIds.length;
    const neighbourId = contributorIds[neighbourIndex];
    return neighbourId;
}
//

const personalIdeasCount = computed(() => {
    return props.ideas.filter(idea =>
        idea.round == props.session.collecting_round &&
        idea.contributor_id == props.personalContributor.id
    ).length;
});

const placeholderMsg = computed(() => {
    if (session.value.method.name === 'Walt Disney') {
        switch (session.value.collecting_round) {
            case 1:
                return "Sammle deine Ideen ganz frei von Einschränkungen oder möglichen Problemen"
            case 2:
                return "Betrachte die Situation möglichst ausgewogen und realistisch"
            case 3:
                return "Überlege dir genau welche Probleme auftreten könnten und wähle deine neuen Ideen möglichst kritisch aus"
            default:
                return "Bitte gib deine Idee für diese Runde ein"
        }
    } else if (session.value.method.name === '6 Thinking Hats') {
        switch (personalContributor.value.name) {
            case 'gelber Hut':
                return "Egal wie unwahrscheinlich. Was wäre das schönste, tollste unvorstellbar beste Ergebnis? "
            case 'roter Hut':
                return "Was fühlst du? Lasse dich von deinem Bauchgefühl leiten"
            case 'blauer Hut':
                return "Woran muss alles gedacht werden, was vergessen manche vielleicht? Wähle deine Ideen möglichst allumfassend und ganzheitlich"
            case 'weißer Hut':
                return "Welche Informationen haben wir? Was für logische Schlüsse kann man daraus ziehen?"
            case 'grüner Hut':
                return "Welche neuen Ideen oder Alternativen gibt es? Was gibt es vielleicht noch nicht, wäre aber einen Versuch wert? Stürze dich ins Unbekannte"
            case 'schwarzer Hut':
                return "Welche Risiken oder Probleme siehst du? Wie ändern sich deine Ideen basierend auf diesen Befürchtungen?"
            default:
                return "Bitte gib deine Gedanken entsprechend deiner Hutfarbe ein"
        }
    } else {
        return "Bitte gib hier deine Idee ein."
    }
});
const roleChallengeMsg = computed(() => {
    if (session.value.method.name === 'Walt Disney') {
        switch (session.value.collecting_round) {
            case 1:
                return "Sei kreativ, sei ein Träumer"
            case 2:
                return "Jetzt bist du Realist."
            case 3:
                return "Schlüpfe in die Rolle eines Kritikers"
            default:
                return "Bitte gib deine Idee für diese Runde ein"
        }
    } else if (session.value.method.name === '6 Thinking Hats') {
        switch (personalContributor.value.name) {
            case 'gelber Hut':
                return "Denke optimistisch"
            case 'roter Hut':
                return "Folge deiner Intuition"
            case 'blauer Hut':
                return "Betrachte die Gesamtheit und nimm die Vogelperspektive ein"
            case 'weißer Hut':
                return "Konzentriere dich auf Fakten"
            case 'grüner Hut':
                return "Sei kreativ"
            case 'schwarzer Hut':
                return "Halte Ausschau nach Risiken"
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
    axios.post('/api/session/ice-breaker', {
        session_id: session.value.id,
        contributor_id: personalContributor.value.id
    })
        .then(response => {
            iceBreakerMsg.value = response.data.iceBreaker_msg;
        })
        .catch(error => {
            if (error.response && error.response.status === 403) {
                errorMsg.value = error.response.data.message;
            } else {
                errorMsg.value = 'Ein Fehler ist aufgetreten';
            }
        })
        .finally(() => {
            inActiveSince.value = 0;
            textInput.value = '';
            fileInput.value = null;
            iceBreakerLoading.value = false;
        });
}

const submitIdea = async () => {
    ideaIsSending.value = true;
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

        let totalSize = 0;
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                totalSize += value.size;
            } else {
                totalSize += new Blob([value]).size;
            }
        }

        try {
            const response = await axios.post('/api/idea/store', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            textInput.value = '';
            imageFile.value = null;
            imageFileUrl.value = '';
            iceBreakerMsg.value = "Trage hier deine Idee ein.";
            ideaIsSending.value = false;
        } catch (error) {
            console.error('Error saving idea', error);
            ideaIsSending.value = false;
            if (error.response) {
                console.log(error.response)
            }
        }
        showImage.value = true;
    } else {
        ideaIsSending.value = false;
        errorMsg.value = "Du musst entweder eine Text-Idee oder eine Bild-Idee einfügen, bevor du die Idee speicherst";
        console.log("No image or text input, showing error message");
    }
};

// Der folgende Codeabschnitt wurde mit Unterstützung von Claude 3.5 Sonnet erstellt
const compressImage = async (file, maxSizeInMB = 1) => {  // maxSizeInMB auf 1 geändert

    if (file.size <= maxSizeInMB * 1024 * 1024) {
        
        return file;
    }
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
                let iteration = 0;

                do {
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    const format = file.type.split('/')[1];
                    const mimeType = `image/${format === 'svg+xml' ? 'png' : format}`;

                    dataUrl = canvas.toDataURL(mimeType, quality);

                    if (dataUrl.length > maxSizeInMB * 1024 * 1024) {
                        width *= 0.9;  // Bild verkleinern
                        height *= 0.9;
                    }
                    quality *= 0.9;  // Qualität schrittweise reduzieren
                    iteration++;
                } while (dataUrl.length > maxSizeInMB * 1024 * 1024 && quality > 0.1);

                fetch(dataUrl)
                    .then(res => res.blob())
                    .then(blob => {
                        const compressedFile = new File([blob], file.name, { type: file.type });
                        resolve(compressedFile);
                    });
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
};

// Der folgende Codeabschnitt wurde mit Unterstützung von Claude 3.5 Sonnet erstellt
const handleFileChange = (event) => {
    const file = event.target.files[0];
    const allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/svg+xml', 'image/webp', 'image/tiff', 'image/heic', 'image/heif'];

    if (file && allowedImageTypes.includes(file.type)) {
        compressImage(file).then(resultFile => {
            imageFile.value = resultFile;
            imageFileUrl.value = URL.createObjectURL(resultFile);
            errorMsg.value = "";
        });
    } else {
        // Zurücksetzen des Datei-Inputs und der zugehörigen Werte
        event.target.value = null;
        imageFile.value = null;
        imageFileUrl.value = '';
        errorMsg.value = "Nicht unterstütztes Dateiformat. Bitte wählen Sie ein gültiges Bildformat. (PNG, JPEG, GIF, SVG, HEIF, HEIC, BMP, WEBP, TIFF)";
    }
};

const openFileInput = () => {
    fileInput.value.click();
};

onMounted(() => {
    const intervalId = setInterval(() => {
        inActiveSince.value++;
    }, 1000);
});
</script>