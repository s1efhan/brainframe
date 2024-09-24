<template>
  <main>
    <h1 class="headline__session-target">
      {{ sessionDetails.target }}
    </h1>
    <div class="session_headline__details"
      v-if="sessionDetails && personalContributor && sessionPhase != 'closingPhase'">
      <div @click="toggleShowContributorsBoard">
        <ProfileIcon />
        <p v-if="personalContributor.role_name != 'Default'">
          {{ contributorsCount }} | {{ contributorsAmount }}
        </p>
      </div>
      <div v-if="sessionPhase != 'lobby' &&  personalContributor.id === sessionHostId && currentRound"
        @click="switchPhase('lobby')">⏸</div>
      <div v-if="sessionPhase === 'lobby' &&  personalContributor.id === sessionHostId && currentRound"
        @click="switchPhase(previousPhase)">▶</div>
      <div>
        <p>{{ methodName }} Methode</p>
      </div>
      <div>
        <p v-if="personalContributor.role_name != 'Default'" class="contributor-icon">
          <component :is="getIconComponent(personalContributor.icon)" />
        </p>
        <p>{{ personalContributor.role_name }}</p>
      </div>
    </div>

    <Rollenwahl v-if="!personalContributor || personalContributor.role_name === 'Default' && methodName"
      :userId="userId" @contributorAdded="handleContributorAdded" :methodName="methodName" />
    <Lobby
      v-if="method && !showContributorsBoard && personalContributor && sessionDetails && personalContributor.role_name != 'Default' && sessionPhase === 'lobby' || isWaiting === true "
      :method="method" :previousPhase="previousPhase" @switchPhase="switchPhase" :currentRound="currentRound"
      :sessionPhase="sessionPhase" :sessionHostId="sessionHostId" :contributors="contributors" :sessionId="sessionId"
      :personalContributor="personalContributor" :totalIdeasToVoteCount="totalIdeasToVoteCount"
      :ideasCount="ideasCount" />
    <CollectingPhase @getContributors="getContributors" @switchPhase="switchPhase"
      v-if="method && !showContributorsBoard && personalContributor && sessionPhase === 'collectingPhase' && personalContributor.role_name != 'Default' "
      :method="method" :currentRound="currentRound" :sessionHostId="sessionHostId" :contributors="contributors"
      :sessionId="sessionId" :personalContributor="personalContributor" :ideasCount="ideasCount" />
    <VotingPhase @switchPhase="switchPhase" @wait="wait"
      v-if=" method && !showContributorsBoard && personalContributor && sessionPhase === 'votingPhase' && personalContributor.role_name != 'Default' "
      :sessionId="sessionId" :votingPhase="votingPhase" :sessionHostId="sessionHostId"
      :personalContributor="personalContributor" :contributorsCount="contributorsCount" />
    <ClosingPhase @switchPhase="switchPhase"
      v-if="method && !showContributorsBoard && personalContributor && sessionPhase === 'closingPhase' && personalContributor.role_name != 'Default' "
      :sessionId="sessionId" :sessionHostId="sessionHostId" :personalContributor="personalContributor" />
    <ContributorsBoard :sessionPhase="sessionPhase" :previousPhase="previousPhase"
      v-if="showContributorsBoard && sessionDetails && method && personalContributor" @exit="handleExit"
      :method="method" :currentRound="currentRound" :sessionHostId="sessionHostId" :contributors="contributors"
      :sessionId="sessionId" :totalIdeasToVoteCount="totalIdeasToVoteCount" :personalContributor="personalContributor"
      :ideasCount="ideasCount" />
  </main>

</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { sessionId } from '../js/eventBus.js'
import axios from 'axios';
import Rollenwahl from './Rollenwahl.vue';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
const route = useRoute();
const router = useRouter();
import ProfileIcon from '../components/icons/ProfileIcon.vue';
import VotingPhase from '../components/phases/VotingPhase.vue';
import CollectingPhase from '../components/phases/CollectingPhase.vue';
import Lobby from '../components/phases/Lobby.vue';
import ClosingPhase from '../components/phases/ClosingPhase.vue';
import ContributorsBoard from '../components/ContributorsBoard.vue';
import IconComponents from '../components/IconComponents.vue';
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const isWaiting = ref(false);
const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});
const totalIdeasToVoteCount = ref(null);
const votingPhase = ref(1);
const updateSessionId = () => {
  sessionId.value = route.params.id;
  emit('updateSessionId', sessionId.value);
}
const handleExit = () => {
  showContributorsBoard.value = false;
};
const headlineHeight = ref('auto');
const baseHeight = 1.5; // Basishöhe in em

const adjustHeadline = () => {
  nextTick(() => {
    const headline = document.querySelector('.headline__session-target');
    if (!headline) return;

    if (headline.textContent.length > 60) {
      headline.textContent = headline.textContent.slice(0, 60);
    }

    const computedStyle = window.getComputedStyle(headline);
    const lineHeight = parseFloat(computedStyle.lineHeight);
    const height = headline.offsetHeight;

    if (lineHeight && height) {
      const lines = Math.ceil(height / lineHeight);
      headlineHeight.value = `${lines * baseHeight}em`;
    }
  });
};
const showContributorsBoard = ref(false);
const contributors = ref([]);
const personalContributor = ref(null);
const handleContributorAdded = () => {
  getContributors();
};
const sessionPhase = ref('lobby');
const getContributors = () => {
  axios.get(`/api/contributors/${sessionId.value}/${userId.value}`)
    .then(response => {
      contributors.value = response.data.contributors;
      console.log("contributors", response.data.contributors)
      personalContributor.value = response.data.personal_contributor;
      console.log("sessionHostId.value, personalContributor.value.id", sessionHostId.value, personalContributor.value.id)
    })
    .catch(error => {
      console.error('Error fetching contributors', error);
    });
}
const methodId = ref(null);
const methodName = ref(null);
const sessionHostId = ref(null);
const currentRound = ref(null);
const sessionDetails = ref([]);
const contributorsCount = ref(null);
const contributorsAmount = ref(null);
const method = ref(null);
const userId = ref(props.userId);
const ideasCount = ref(null);
const previousPhase = ref('collectingPhase');

const toggleShowContributorsBoard = () => {
  if (sessionPhase.value !== 'lobby') {
    showContributorsBoard.value = !showContributorsBoard.value;

    console.log('Contributors Board Toggle:', {
      showContributorsBoard: showContributorsBoard.value,
      sessionPhase: sessionPhase.value,
      sessionDetailsAvailable: !!sessionDetails.value,
      method: method.value,
      personalContributorAvailable: !!personalContributor.value,
      currentRound: currentRound.value
    });
  } else {
    console.log('Cannot toggle Contributors Board in lobby phase');
  }
}

const getSessionDetails = () => {
  axios.get(`/api/session/${sessionId.value}`)
    .then(
      response => {
        sessionDetails.value = response.data;
        methodId.value = sessionDetails.value.method_id;
        methodName.value = sessionDetails.value.method_name;
        sessionPhase.value = sessionDetails.value.session_phase || 'lobby';
        previousPhase.value = sessionDetails.value.previous_phase || 'collectingPhase';
        contributorsAmount.value = sessionDetails.value.contributors_amount;
        sessionHostId.value = sessionDetails.value.session_host;
        currentRound.value = sessionDetails.value.current_round || 0;
        ideasCount.value = sessionDetails.value.ideas_count;
        totalIdeasToVoteCount.value = sessionDetails.value.total_ideas_to_vote_count;
        votingPhase.value = sessionDetails.value.voting_phase || 1;
        getMethodDetails();
      })
    .catch(error => {
      console.error('Error fetching Session Details', error);
      sessionId.value = null;
      router.push('/brainframe/join')
    });
};
const wait = () => {
  isWaiting.value = true;
}
const emit = defineEmits(['updateSessionId']);
const switchPhase = (switchedPhase) => {
  currentRound.value = 1;
  isWaiting.value = false;
  if (personalContributor.value.id == sessionHostId.value) {
    axios.post('/api/phase', {
      switched_phase: switchedPhase,
      session_id: sessionId.value,
      voting_phase: votingPhase.value
    })
      .then(response => {
        console.log('Server response:', response.data);
      })
      .catch(error => {
        console.error('Error switching Phase', error);
      })
      .finally(() => {
        getSessionDetails();
      });
  }
};
const getMethodDetails = () => {
  axios.get(`/api/method/${methodId.value}`)
    .then(response => {
      method.value = response.data;
    })
    .catch(error => {
      console.error('Error fetching Method Details', error);
    });
};
const joinSession = () => {
  if (sessionId.value > 0 && userId) {
    axios.post('/api/session/join', {
      session_id: sessionId.value,
      user_id: userId.value
    })
      .then(response => {
        contributorsCount.value = response.data.contributors_count;
      })
      .catch(error => {
        console.error('Error adding Contributor', error);
      })
  }
};
const leaveSession = () => {
  if (sessionId.value > 0 && userId)
    // Option 1: Using Fetch API
    fetch('/api/session/leave', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        session_id: sessionId.value,
        user_id: userId.value
      }),
      keepalive: true
    }).catch(error => console.error('Error leaving session:', error));
};


const handleVisibilityChange = () => {
  if (sessionId.value > 0 && userId)
    if (document.hidden) {
      leaveSession();
    } else {
      joinSession();
    }
};
const ping = () => {
  if (sessionId.value > 0 && userId.value) {
    axios.post('/api/session/ping', {
      session_id: sessionId.value,
      user_id: userId.value,
    })
      .then(response => {
      })
      .catch(error => {
        console.error('Error pinging', error);
      });
  }
};

let pingInterval;

onMounted(() => {
  updateSessionId();
  console.log('Listening on channel:', 'session.' + sessionId.value);
  getSessionDetails();
  Echo.channel('session.' + sessionId.value)
    .listen('SwitchPhase', (e) => {
      getSessionDetails();
      isWaiting.value = false;
      console.log("switchedPhase");
    })
    .listen('LastVote', (e) => {
      isWaiting.value = false;
      if (personalContributor.value.id == sessionHostId.value) {
        votingPhase.value = e.votingPhase;
        axios.post('/api/session/vote/update', {
          session_id: sessionId.value,
          voting_phase: votingPhase.value
        })
          .then(response => {
            console.error('Success switching VotingPhase', response.data);
          })
          .catch(error => {
            console.error('Error switching VotingPhase', error);
          })
      }
      console.log("Last Vote Event received");
      if (e.switchToClosing) {
        switchPhase("closingPhase");
      }
    })
    .listen('UserJoinedSession', (e) => {

      if (e.newContributorsAmount !== null) {
        contributorsAmount.value = e.newContributorsAmount;
      }
      if (e.newContributorsCount !== null || e.newContributorsAmount !== null) {
        getContributors();
      }
    })
    .listen('UserSentVote', (e) => {
      console.log(`Contributor ${e.contributorId} hat ${e.voteCount} Ideen in Runde ${e.votingPhase}`);
      const contributorIndex = contributors.value.findIndex(c => c.id == e.contributorId);
      if (contributorIndex !== -1) {
        contributors.value[contributorIndex].voted_ideas_count = e.voteCount;
      }
    })
    .listen('UserLeftSession', (e) => {
      contributorsCount.value = e.newContributorsCount;
    });
  document.addEventListener('visibilitychange', handleVisibilityChange);
  window.addEventListener('beforeunload', leaveSession);
  pingInterval = setInterval(ping, 30000);
  nextTick(() => {
    joinSession();
    adjustHeadline();
  });
});

onUnmounted(() => {
  leaveSession();
  document.removeEventListener('visibilitychange', handleVisibilityChange);
  window.removeEventListener('beforeunload', leaveSession);
  clearInterval(pingInterval);
});
</script>