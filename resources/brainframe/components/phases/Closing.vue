<template v-if="isDataReady">
    <div class="collecting-pdf ">
        <table class="session-data" id="first-table">
            <thead>
                <tr>
                    <th>
                        <TargetIcon />
                    </th>
                    <th>
                        <ProfileIcon />
                    </th>
                    <th>
                        <LightbulbIcon />
                    </th>
                    <th>
                        <CalendarIcon />
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ session.target }}</td>
                    <td class="center">{{ contributors.length }}</td>
                    <td class="center">{{ ideasWithoutTags.length }}</td>
                    <td class="center">{{ formatDate(session.created_at) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="session-data">
            <thead>
                <tr>
                    <th>Methode</th>
                    <th>
                        <PinIcon />
                    </th>
                    <th>
                        <AiStarsIcon />
                    </th>
                    <th>
                        <SandclockIcon />
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="center">{{ session.method.name }}</td>
                    <td class="center"><a :href="'https://stefan-theissen.de/brainframe/' + session.id">
                            {{ session.id }}
                        </a>
                    </td>
                    <td class="token">
                        {{ session.prompt_tokens }} (prompt) <br>{{ session.completion_tokens }} (completion) <br> =>
                        {{ calculateCost }} ct
                    </td>
                    <td class="center">
                        {{ Math.floor(sessionDuration / 60) }}h
                        {{ Math.round(sessionDuration % 60) }}min
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="survey__email__input" v-if="!props.personalContributor.survey_activated">
            <form @submit.prevent="validateSurveyEmail">
                <div v-if="surveyEmailIsValid" class="validated-email">
                    {{ surveyEmail }} <span v-if="!showCodeInput"
                        @click="surveyEmail = '', showCodeInput = false, validateSurveyEmail()"
                        class="remove-email">x</span>
                </div>
                <input v-if="!surveyEmailIsValid" v-model="surveyEmail" type="email" @blur="validateSurveyEmail"
                    @keyup.enter="validateSurveyEmail">
                <div class="permission__container">
                    <label v-if="!showCodeInput" for="permission_for_survey">Ich bin einverstanden damit, dass meine
                        E-Mail Adresse für den
                        einmaligen Versand einer wissenschaftlichen Umfrage zum Thema Digitales Ideen-Sammeln verwendet
                        wird.</label>
                    <input v-if="!showCodeInput" id="permission_for_survey" type="checkbox" v-model="isChecked"
                        :disabled="showCodeInput">
                </div>
                <label v-if="showCodeInput">
                    Bitte gib den Verifizierungscode ein, den wir dir per E-Mail zugesandt haben.
                </label>
                <button v-if="!showCodeInput && surveyEmailIsValid" class="primary" @click="storeSurveyEmail">Email
                    verifizieren</button>
                <input v-if="showCodeInput" v-model="surveyVerificationKey" type="text" inputmode="numeric"
                    pattern="[0-9]*" maxlength="6" placeholder="z.B 123456">
                <button v-if="showCodeInput" class="primary" @click="verifyEmail">Senden</button>
            </form>
        </div>
        <div class="top-ideas">
            <h2>Top Ideen</h2>
            <table>
                <thead>
                    <tr>
                        <th>
                            <PodiumIcon />
                        </th>
                        <th>
                            <LightbulbIcon />
                        </th>
                        <th>Beschreibung</th>
                        <th>
                            <ProfileIcon />
                        </th>
                        <th>
                            <StarIcon />
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(idea, index) in ideasWithTags.slice(0, visibleIdeas)" :key="idea.id">
                        <template v-if="index === 0 || index === 1 || props.personalContributor.survey_activated">
                            <tr :class="{ 'blurred': index === 1 && !props.personalContributor.survey_activated }">
                                <td class="center">{{ index + 1 }}</td>
                                <td>{{ idea.title }}</td>
                                <td v-html="idea.description"></td>
                                <td class="center" :class="{
    [contributors.find(c => c.id === idea.contributor_id)?.icon]: session.method.name === '6 Thinking Hats'
  }">
                                    <component
                                        :is="getIconComponent(contributors.find(c => c.id === idea.contributor_id))" />
                                </td>
                                <td class="center">{{ idea.avgRating.toFixed(1) }}/{{ idea.maxVoteValue.toFixed(1) }}
                                    ({{ idea.maxRound }})</td>
                            </tr>
                            <tr v-if="index === 0 || props.personalContributor.survey_activated" class="chevron"
                                @click="toggleDetails(idea.id)">
                                <td colspan="6">
                                    <div class="chevron-container"
                                        :class="{ 'rotated': expandedIds.includes(idea.id) }">▼</div>
                                </td>
                            </tr>
                            <tr v-if="(index === 0 || props.personalContributor.survey_activated) && expandedIds.includes(idea.id)"
                                class="details-row">
                                <td colspan="5">
                                    <div v-html="idea.description"></div>
                                </td>
                            </tr>
                        </template>
                    </template>
                    <tr v-if="visibleIdeas < ideasWithTags.length">
                        <td colspan="5" class="center">
                            <button class="secondary" @click="showMoreIdeas">Mehr Anzeigen</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="collecting-process">
            <h2>{{ session.method.name }}</h2>
            <div class="timeline">
                <div :class="{ 'blurred': !personalContributor.survey_activated }"
                    v-for="(groupedIdeas, round) in groupedIdeasByRound" :key="round" class="tag">
                    <div class="round">{{ round }}</div>
                    <ul>
                        <li v-for="idea in groupedIdeas" :key="idea.id" :class="{
    [contributors.find(c => c.id === idea.contributor_id)?.icon]: session.method.name === '6 Thinking Hats'
  }">
                            <component :is="getIconComponent(contributors.find(c => c.id === idea.contributor_id))" />
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="word-cluster" v-if="wordCloud">
            <h2>Wort-Cluster</h2>
            <ul>
                <li :class="['count-' + item.count, { 'blurred': !props.personalContributor.survey_activated }]"
                    v-for="item in wordCloud" :key="item.word">
                    {{ item.word }}
                </li>
            </ul>
        </div>


        <div class="tags-list">
            <h2>#Tags</h2>
            <ul>
                <li :class="['count-' + tag.count, { 'blurred': !props.personalContributor.survey_activated }]"
                    v-for="tag in tagList" :key="tag.tag">
                    #{{ tag.tag }}
                </li>
            </ul>
        </div>
        <div class="summary__buttons">
            <button class="accent" @click="toggleshowSendContainer">Zusammenfassung senden</button>

            <section class="download_buttons"><button class="secondary" @click="downloadPDF">PDF herunterladen</button>
                <button class="accent" @click="downloadCSV">CSV herunterladen</button>
            </section>
        </div>
        <div v-if="showSendContainer" class="send__container">
            <div class="email-list">
                <div v-for="email in validatedEmails" :key="email" class="validated-email">
                    {{ email }} <span @click="removeEmail(email)" class="remove-email">x</span>
                </div>
            </div>
            <div class="email-input__container">
                <input type="email" v-model="newEmail" @keyup.enter="addEmail" @blur="addEmail"
                    placeholder="E-Mail-Adresse eingeben">
                <button class="secondary" @click="sendSummary">PDF Senden</button>
            </div>
        </div>
        <div v-if="errorMsg" class="error">{{ errorMsg }}</div>
        <div class="next-steps">
            <h2>Nächste Schritte und Empfehlungen</h2>
            <p :class="{ 'blurred': !props.personalContributor.survey_activated }" v-html="nextSteps"></p>
        </div>
        <div class="newSession__buttons">
            <button class="accent" @click="router.push('/brainframe/create')">Neue Session Starten</button>
            <button class="primary" @click="router.push('/brainframe/profile')">Account anlegen & Session
                speichern!</button>
        </div>
    </div>

</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import IconComponents from '../IconComponents.vue';
import CalendarIcon from '../icons/CalendarIcon.vue';
import PinIcon from '../icons/PinIcon.vue';
import PodiumIcon from '../icons/PodiumIcon.vue';
import SandclockIcon from '../icons/SandclockIcon.vue';
import TargetIcon from '../icons/TargetIcon.vue';
import ProfileIcon from '../icons/ProfileIcon.vue';
import StarIcon from '../icons/StarIcon.vue';
import AiStarsIcon from '../icons/AiStarsIcon.vue';
import LightbulbIcon from '../icons/LightbulbIcon.vue';
import { useRouter } from 'vue-router';
const showCodeInput = ref(false);
const router = useRouter();
const showSendContainer = ref(false);
const props = defineProps({
    personalContributor: {
        type: Object,
        required: true
    },
    session: {
        type: Object,
        required: true
    },
    contributors: {
        type: Object,
        required: true
    },
    ideas: {
        type: Object,
        required: true
    },
    votes: {
        type: Object,
        required: true
    }
});

const isDataReady = computed(() => {
    return props.ideas &&
        props.ideas.length > 0 &&
        props.votes &&
        props.votes.length > 0 &&
        props.session &&
        props.contributors;
});
const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
const surveyEmailIsValid = ref(false);
const validateSurveyEmail = () => {
    console.log("validateSurveyEmail")
    if (isValidEmail(surveyEmail.value)) {
        surveyEmailIsValid.value = true;
    }
    else {
        surveyEmailIsValid.value = false;

    }
}
const isChecked = ref(false);
const toggleDetails = (id) => {
    if (expandedIds.value.includes(id)) {
        expandedIds.value = expandedIds.value.filter(expandedId => expandedId !== id);
    } else {
        expandedIds.value.push(id);
    }
};
const surveyEmail = ref(props.personalContributor.email);
const expandedIds = ref([]);
const ideas = ref(props.ideas || []);
const session = ref(props.session || {});
watch(() => props.session, (newSession) => {
    if (newSession) {
        session.value = newSession;
        console.log("Updated session:", session.value);
    }
}, { immediate: true, deep: true });
const votes = ref(props.votes || []);
const contributors = ref(props.contributors || []);
const visibleIdeas = ref(3);
const showMoreIdeas = () => {
    if (props.personalContributor.survey_activated) {
        visibleIdeas.value += 5;
        return true;
    }
    errorMsg.value = "Melde dich für die Umfrage (5-10 min), um die Mehr zu den Session Ergebnissen zu erfahren"
    alert(errorMsg.value);
    return false;
};
const getMaxVoteValue = (vote_type) => {
    switch (vote_type) {
        case 'ranking': return 5;
        case 'star': return 3;
        case 'swipe': return 1;
        case 'leftRightVote': return 1;
        default: return 1;
    }
};

const ideasWithTags = computed(() => {
    if (!ideas.value || !votes.value) return [];

    return ideas.value
        .filter(idea => idea.tag !== null && idea.tag !== '')
        .map(idea => {
            const ideaVotes = votes.value.filter(v => v.idea_id === idea.id);
            const maxRound = Math.max(...ideaVotes.map(v => v.round));
            const relevantVotes = ideaVotes.filter(v => v.round === maxRound);
            const avgRating = relevantVotes.length ?
                relevantVotes.reduce((sum, v) => sum + v.value, 0) / relevantVotes.length :
                0;

            const voteType = relevantVotes.length ? relevantVotes[0].vote_type : 'default';

            const maxVoteValue = getMaxVoteValue(voteType);
            return { ...idea, avgRating, maxRound, maxVoteValue, voteType };
        })
        .sort((a, b) => {
            if (a.maxRound !== b.maxRound) return b.maxRound - a.maxRound;
            return b.avgRating - a.avgRating;
        });
});

const ideasWithoutTags = computed(() => {
    return ideas.value ? ideas.value.filter(idea => !idea.tag) : [];
});
const sessionDuration = computed(() => {
    if (!isDataReady.value) return 0;
    const firstIdea = ideas.value[0];
    const lastVote = votes.value[votes.value.length - 1];
    return (new Date(lastVote.created_at) - new Date(firstIdea.created_at)) / 60000;
});
const tagList = ref(null);
const wordCloud = ref(null);
const nextSteps = ref(null);
const getClosingDetails = () => {
    console.log("getClosingDetails");
    axios.get(`/api/session/${props.session.id}/closing`)
        .then(response => {
            wordCloud.value = response.data.wordCloud;
            tagList.value = response.data.tagList;
            session.value.prompt_tokens = response.data.prompt_tokens;
            session.value.completion_tokens = response.data.completion_tokens;
            nextSteps.value = response.data.nextSteps
            console.log("response getClosing", response.data);
        })
        .catch(error => {
            console.error('Error fetching closingDetails', error);
        })
}

const errorMsg = ref(null);
const downloadCSV = () => {
    if (props.personalContributor.survey_activated) {
        const url = `/api/session/${props.session.id}/summary/download-csv`;

        axios.get(url, { responseType: 'blob' })
            .then(response => {
                const blob = new Blob([response.data], { type: 'text/csv' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = `${session.value.target}_summary.csv`;
                link.click();
            })
            .catch(error => {
                console.error('Error Downloading CSV', error);
            });
    }
    else {
        errorMsg.value = "Melde dich für die Umfrage (5-10 min), um die ausführlichen Session Ergebnisse zu downloaden";
        alert(errorMsg.value)
    }
};
const groupedIdeasByRound = computed(() => {
    return ideas.value.reduce((acc, idea) => {
        if (idea.tag) {  // Nur Ideen mit einem Tag berücksichtigen
            const round = idea.round || 'Unbekannt';
            if (!acc[round]) {
                acc[round] = [];
            }
            acc[round].push(idea);
        }
        return acc;
    }, {});
});

const calculateCost = computed(() => {
    if (!session.value) return 0;
    return ((session.value.prompt_tokens * 0.00000015 + session.value.completion_tokens * 0.00000060) * 100).toFixed(2);
});

const downloadPDF = () => {
    if (props.personalContributor.survey_activated) {
        const url = `/api/session/${props.session.id}/summary/download`;
        // Behalten Sie die bestehende PDF-Download-Logik bei
        axios.get(url, { responseType: 'blob' })
            .then(response => {
                const blob = new Blob([response.data]);
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = `${session.value.target}.pdf`;
                link.click();
            })
            .catch(error => {
                console.error('Error Downloading PDF', error);
            });
    }
    else {
        errorMsg.value = "Melde dich für die Umfrage (5-10 min), um die ausführlichen Session Ergebnisse zu downloaden";
        alert(errorMsg.value)
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'Kein Datum verfügbar';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) {
        console.error('Ungültiges Datum:', dateString);
        return 'Fehler beim Datumformat';
    }
    const options = { year: 'numeric', month: 'numeric', day: 'numeric' };
    return date.toLocaleDateString('de-DE', options);
};

onMounted(() => {
    getClosingDetails();
    validateSurveyEmail();
    console.log("Session created_at:", props.session.created_at);
    console.log("Formatted date:", formatDate(props.session.created_at));
});

const newEmail = ref('');
const validatedEmails = ref([]);
const contributorEmailAddresses = ref(['']);
const addEmail = () => {
    const email = newEmail.value.trim();
    if (isValidEmail(email) && !validatedEmails.value.includes(email)) {
        validatedEmails.value.push(email);
        newEmail.value = '';
    }
};
const sendSummary = () => {
    axios.post(`/api/session/summary/send`, {
        contributor_emails: validatedEmails.value,
        session_id: props.session.id
    })
        .then(response => {
            showSendContainer.value = !showSendContainer.value;
            errorMsg.value = "Zusammenfassung Erfolgreich versendet"
        }).catch(error => {
            console.error('Error sending the summary', error);
        });
};
const validateEmail = (index, event) => {
    const email = contributorEmailAddresses.value[index];
    if (isValidEmail(email) && (event.key === 'Enter' || event.type === 'blur')) {
        if (!validatedEmails.value.includes(email)) {
            validatedEmails.value.push(email);
        }
        contributorEmailAddresses.value[index] = '';
        if (index === contributorEmailAddresses.value.length - 1) {
            contributorEmailAddresses.value.push('');
        }
    }
};

const getIconComponent = (contributor) => {
    return contributor ? IconComponents[contributor.icon] || null : null;
};
const surveyVerificationKey = ref(null);

const verifyEmail = () => {
    console.log("verifyEmail");
    axios.post(`/api/survey/email/verify`, {
        survey_email: surveyEmail.value,
        session_id: props.session.id,
        survey_verification_key: surveyVerificationKey.value,
        user_id: Number(localStorage.getItem('user_id'))
    })
        .then(response => {
            showSendContainer.value = !showSendContainer.value;
            props.personalContributor.survey_activated = true;
        })
        .catch(error => {
            console.error('Error in verification process', error);
        });
}
const storeSurveyEmail = () => {
    if (isChecked.value) {
        showCodeInput.value = true;
        console.log("storeSurveyEmail")
        axios.post(`/api/survey/email/store`, {
            survey_email: surveyEmail.value,
            session_id: props.session.id,
            is_checked: isChecked.value,
            user_id: Number(localStorage.getItem('user_id'))
        })
            .then(response => {
                showSendContainer.value = !showSendContainer.value;
            }).catch(error => {
                console.error('Error storing the email', error);
            });
    }
    else {
        alert("Du musst der Verwendung deiner Email Adresse zustimmen, um an der Umfrage teilzunehmen.")
    }
}

const toggleshowSendContainer = () => {
    if (props.personalContributor.survey_activated) {
        showSendContainer.value = !showSendContainer.value;
        errorMsg.value = null;
        if (showSendContainer.value && session.value && session.value.contributor_emails) {
            session.value.contributor_emails.forEach(email => { // gibts nicht
                if (isValidEmail(email) && !validatedEmails.value.includes(email)) {
                    validatedEmails.value.push(email);
                }
            });
        }
    }
    else {
        errorMsg.value = "Melde dich für die Umfrage (5-10 min), um die ausführlichen Session Ergebnisse zu teilen"
        alert(errorMsg.value)
    }
};

const removeEmail = (email) => {
    validatedEmails.value = validatedEmails.value.filter(e => e !== email);
};

</script>
<style scoped>
.chevron {
    cursor: pointer;
    text-align: center;
}

.chevron div {
    transition: transform 0.3s ease;
}

.chevron .rotated {
    transform: rotate(180deg);
}

.blurred {
    filter: blur(4px);
    pointer-events: none;
}
</style>