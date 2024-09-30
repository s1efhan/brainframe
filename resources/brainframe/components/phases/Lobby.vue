<template>
    <section v-if="session.vote_round < 1 && session.collecting_round < 1 && session.phase != 'closing'">
      <div v-if="sessionLink" @click="copyToClipboard(sessionLink)" class="session-link">
    <router-link :to="'/brainframe/' + session.id">{{ sessionLink }}</router-link>
    <p>
      <CopyIcon />
    </p>
  </div>
  <div v-if="sessionLink" class="qr-code-container">
    <canvas class="qr-code" ref="qrcodeCanvas"></canvas>
  </div>
  <div v-if="sessionLink && personalContributor.isHost" class="email-list">
    <div v-for="email in validatedEmails" :key="email" class="validated-email">
      {{ email }} <span @click="removeEmail(email)" class="remove-email">x</span>
    </div>
  </div>
  <div v-if="sessionLink && personalContributor.isHost" class="email-input__container" v-for="(email, index) in contributorEmailAddresses"
  :key="index">
  
  <input type="email" v-model="contributorEmailAddresses[index]" @keyup.enter="validateEmail(index, $event)"
    @blur="validateEmail(index, $event)" placeholder="E-Mail-Adresse eingeben">
  <button v-if="sessionLink" @click="sessionInvite" class="secondary">
    Teilnehmer einladen
  </button>
</div>
</section>
    <section v-else class="contributors_board__container">
        <div class="contributors_board">
            <div class="info__container">
                <button v-if="!session.isPaused" @click="emit('exit')" class="primary">X</button>
                <div @click="showInfo = !showInfo" class="join__info">
                    <p>i</p>
                </div>
            </div>
            <div v-if="showInfo" class="info__text__container">
                <div class="info__text">
                    <h3>Board</h3>
                    <ul>
                        <li>Hier siehst du die bisherigen <strong>Stats deiner BrainStorming Session</strong></li>
                        <br>
                        <li v-if="session.isPaused">Der Host hat die Session für alle Teilnehmer pausiert. Der Countdown
                            ist angehalten</li>
                        <li v-else>Die Session ist nicht pausiert. Der Countdown läuft weiter</li>
                    </ul>
                </div>
            </div>
            <table v-if="ideas">
                <thead>
                    <tr>
                        <th>Session PIN</th>
                        <th>Methode</th>
                        <th v-for="round in session.method.round_limit" :key="round">
                            Runde {{ round }}
                        </th>
                        <th>Teilnehmer</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ session.id }}</td>
                        <td>{{ session.method.name }}</td>
                        <td class="center" v-for="round in session.method.round_limit" :key="round">
                            {{ ideas.filter(idea => Number(idea.round) === round).length }}
                        </td>
                        <td class="center">{{ contributors.length }}</td>
                    </tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Name</th>
                        <th v-if="session.phase != 'voting'">Ideen</th>
                        <th v-if="session.phase != 'collecting'">Votes</th>
                        <th>Zuletzt Aktiv</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="contributor in contributors" :key="contributor.id">
                        <td>{{ contributor.icon }}</td>
                        <td>{{ contributor.name }}</td>
                        <td v-if="session.phase != 'voting'">
                            {{ ideasCount(contributor.id, session.collecting_round) }}
                            <template v-if="session.method.idea_limit > 0">
                                / {{ session.method.idea_limit }}
                            </template>
                        </td>
                        <td v-if="session.phase != 'collecting'">
                            {{
                            votes.filter(vote =>
                            vote.contributor_id === contributor.id &&
                            Number(vote.round) === session.vote_round
                            ).length
                            }}
                        </td>
                        <td>Vor {{
                            (() => {
                            const diff = new Date() - new Date(contributor.last_active);
                            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                            return days > 0 ? `${days} ${days === 1 ? 'Tag' : 'Tage'}` : `${hours}h ${minutes}min`;
                            })()
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
    <div class="lobby__start__container">
                <button class="primary" v-if="session.seconds_left == 0 && session.isPaused && personalContributor.isHost && session.phase === 'collecting'"
                    @click="emit('start')">Sammeln
                    starten</button>
                    <button class="secondary" v-if="session.seconds_left != 0 &&  session.isPaused && personalContributor.isHost && session.phase ==='collecting'"
                    @click="emit('stop')">Sammeln
                    beenden</button>
                <button class="secondary" v-if="session.seconds_left != 0 && session.isPaused && personalContributor.isHost && session.phase ==='voting'"
                    @click="emit('stop')">Voting
                    beenden</button>
                    <button class="primary" v-if="session.seconds_left == 0 && session.isPaused && personalContributor.isHost && session.phase ==='voting'"
                    @click="emit('start')">Voting
                    starten</button>
            </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import SwooshIcon from '../icons/SwooshIcon.vue';
import CopyIcon from '../icons/CopyIcon.vue';
import QRCode from 'qrcode';
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
const ideasCount = computed(() => {
    return (contributorId, round) => {
        return ideas.value.filter(idea =>
            idea.contributor_id === contributorId &&
            Number(idea.round) === round
        ).length;
    };
});
const showQRCode = ref(false);
const validatedEmails = ref([]);
const contributorEmailAddresses = ref(['']);
const sessionLink = ref(null);
const emit = defineEmits(['start', 'exit', 'stop']);
const showInfo = ref(true);
const contributors = ref(props.contributors);
const personalContributor = ref(props.personalContributor);
const session = ref(props.session);
const ideas = ref(props.ideas);
const removeEmail = (email) => {
    validatedEmails.value = validatedEmails.value.filter(e => e !== email);
  };
const copyToClipboard = (copyText) => {
  navigator.clipboard.writeText(copyText);
};

const validateEmail = (index, event) => {
  const email = contributorEmailAddresses.value[index];
  if (isValidEmail(email) && (event.key === 'Enter' || event.type === 'blur')) {
    if (!validatedEmails.value.includes(email)) {
      validatedEmails.value.push(email);
    } else {
      console.log('Diese E-Mail-Adresse wurde bereits hinzugefügt.');
    }
    // Leeren Sie das Eingabefeld in jedem Fall
    contributorEmailAddresses.value[index] = '';
  }
};

const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

const sessionInvite = () => {
  const oldValidatedEmails = [...validatedEmails.value];

  validatedEmails.value = [];
  contributorEmailAddresses.value = [''];
if(personalContributor.value.isHost){
  axios.post('/api/session/invite', {
    session_id: session.value.id,
    host_id: personalContributor.value.id,
    contributor_email_addresses: oldValidatedEmails
  })
  .then(response => {
      console.log('Einladungen erfolgreich gesendet');
  })
  .catch(error => {
    console.error('Error inviting to the session', error);
 validatedEmails.value = oldValidatedEmails;
  });
}
};
const qrcodeCanvas = ref(null);
const generateQRCode = () => {
  if (sessionLink.value && qrcodeCanvas.value && !showQRCode.value) {
    showQRCode.value = true;
    QRCode.toCanvas(qrcodeCanvas.value, sessionLink.value, (error) => {
      if (error) console.error(error);
    });
  }
};
onMounted(() => {
    sessionLink.value = `https://stefan-theissen.de/`+session.value.id;
    if (session.value.collecting_round > 1 || session.value.vote_round > 0) {
        showInfo.value = false;
    }
    else { showInfo.value = true; }
    nextTick(() => {generateQRCode()});
});
</script>