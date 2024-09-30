<template>
    <div v-if="sessionPhase === 'lobby'" class="lobby__headline__container">
        <h2 class="lobby__headline">Lobby</h2>
        <h3>. . . warte, bis der Host die Runde startet</h3>
    </div>


  <div v-if="props.currentRound < 1" class="lobby__before__container">
  <div v-if="sessionLink" @click="copyToClipboard(sessionLink)" class="session-link">
    <router-link :to="'/brainframe/' + sessionId">{{ sessionLink }}</router-link>
    <p>
      <CopyIcon />
    </p>
  </div>
  <div v-if="sessionLink" class="qr-code-container">
    <canvas class="qr-code" ref="qrcodeCanvas"></canvas>
  </div>
  <div v-if="sessionLink && props.sessionHostId == props.personalContributor.id" class="email-list">
    <div v-for="email in validatedEmails" :key="email" class="validated-email">
      {{ email }} <span @click="removeEmail(email)" class="remove-email">x</span>
    </div>
  </div>
  <div v-if="sessionLink && props.sessionHostId == props.personalContributor.id" class="email-input__container" v-for="(email, index) in contributorEmailAddresses"
  :key="index">
  
  <input type="email" v-model="contributorEmailAddresses[index]" @keyup.enter="validateEmail(index, $event)"
    @blur="validateEmail(index, $event)" placeholder="E-Mail-Adresse eingeben">
  <button v-if="sessionLink" @click="sessionInvite" class="secondary">
    Teilnehmer einladen
  </button>
</div>
  <ContributorsBoard v-if="props.currentRound >= 0 || props.sessionHostId != props.personalContributor.id" :method="props.method" @exit="handleExit"
    :currentRound="props.currentRound" :sessionHostId="props.sessionHostId" :contributors="props.contributors"
    :sessionId="props.sessionId" :personalContributor="props.personalContributor" :ideasCount="props.ideasCount"
    :previousPhase="props.previousPhase"  :sessionPhase="props.sessionPhase" :totalIdeasToVoteCount="props.totalIdeasToVoteCount"/>
</div>
<ContributorsBoard v-else-if="props.currentRound >= 0 || props.sessionHostId != props.personalContributor.id" :method="props.method" @exit="handleExit"
    :currentRound="props.currentRound" :sessionHostId="props.sessionHostId" :contributors="props.contributors"
    :sessionId="props.sessionId" :personalContributor="props.personalContributor" :ideasCount="props.ideasCount"
   :sessionPhase="props.sessionPhase" :totalIdeasToVoteCount="props.totalIdeasToVoteCount":previousPhase="props.previousPhase" />
</template>

<script setup>
import ContributorsBoard from '../ContributorsBoard.vue';
import { ref, onMounted, nextTick } from 'vue';
import CopyIcon from '../icons/CopyIcon.vue';
import QRCode from 'qrcode';
const props = defineProps({
  method: Object,
  currentRound: Number,
  sessionHostId: [String, Number],
  contributors: Array,
  sessionId: [String, Number],
  personalContributor: Object,
  ideasCount: Object,
  maxIdeaInput: Number,
  previousPhase: String,
  sessionPhase: String,
  totalIdeasToVoteCount: [null, Number]
});
 
const showQRCode = ref(false);
const validatedEmails = ref([]);
const contributorEmailAddresses = ref(['']);
const sessionLink = ref(null);
const emit = defineEmits(['switchPhase']);
import { useRouter } from 'vue-router';
const sessionId = ref(props.sessionId);
const router = useRouter();
const handleExit = () => {
  if(props.sessionPhase === "votingPhase"){
    emit('switchPhase', 'votingPhase');
  } else
  emit('switchPhase', 'previousPhase');
}
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

  axios.post('/api/session/invite', {
    session_id: sessionId.value,
    host_id: props.sessionHostId,
    contributor_email_addresses: oldValidatedEmails
  })
  .then(response => {
      console.log('Einladungen erfolgreich gesendet');
  })
  .catch(error => {
    console.error('Error inviting to the session', error);
 validatedEmails.value = oldValidatedEmails;
  });
};

const generateQRCode = () => {
  if (sessionLink.value && qrcodeCanvas.value && !showQRCode.value) {
    showQRCode.value = true;
    QRCode.toCanvas(qrcodeCanvas.value, sessionLink.value, (error) => {
      if (error) console.error(error);
    });
  }
};

onMounted(() => {
  console.log('props.previousPhase',props.previousPhase)
  sessionLink.value = `${window.location.origin}${router.resolve({ name: 'session', params: { id: sessionId.value } }).href}`;
});
nextTick(() => {generateQRCode()});
</script>