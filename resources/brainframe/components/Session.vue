<template>
  <main v-if="session && ideas && votes && !isLoading">

    <h1 class="headline__session-target">
      {{ session.target }}
    </h1>
    <Rollenwahl v-if="!personalContributor" :session="session" :userId="userId" />
    <div v-if="personalContributor && session.phase != 'closing'" class="session_headline__details">
      <div @click="session.phase !== 'closing' && !session.isPaused ? showStats = !showStats : null">
        <ProfileIcon />
        <p>
          {{ contributors.filter(c => c.is_active).length }} | {{ contributors.length }}
        </p>
      </div>
      <div class="session_headline__details__pause"
        @click="session.isPaused && personalContributor.isHost ? resumeSession() : pauseSession()">
        <p :class="{ 'pause-animation':  contributors.filter(c => c.is_active).length > contributors.length && personalContributor.isHost}"
          v-if="!session.isPaused">
          <PauseIcon />
        </p>
        <p :class="{ 'pause-animation': session.seconds_left && personalContributor.isHost}" v-else>
          <PlayIcon />
        </p>
      </div>
      <div>
        <p>{{ session.method.name }} Methode</p>
      </div>
      <div class="contributor_icon">
        <p v-if="personalContributor.name != 'Default'">
          <component :is="getIconComponent(personalContributor.icon)" :class="{
    'animation': session.method.name === '6 Thinking Hats' && 
                 !session.isPaused && 
                 session.phase === 'collecting'
  }" />
        </p>
        <p class="desktop">{{ personalContributor.name }}</p>
      </div>
    </div>
    <div v-if="errorMsg" class="error">
      <p>{{ errorMsg }}</p>
    </div>

    <Lobby
      v-if="(session.isPaused  && session.phase != 'closing' || showStats && session.phase != 'closing') && personalContributor"
      :session="session" :isStarting="isStarting" :isStopping="isStopping":contributors="contributors" :personalContributor="personalContributor" :votes="votes"
      @exit="showStats = false" @start="startSession" @stop="stopSession" :ideas="ideasWithoutTags" />
    <Collecting @stop="stopSession" :contributors="contributors" :session="session"
      :personalContributor="personalContributor" :ideas="ideas"
      v-if="session.phase === 'collecting' && !session.isPaused && !showStats && personalContributor"
      @wait="showStats = true" />
    <Voting v-if=" session.phase === 'voting' && !session.isPaused && !showStats && personalContributor"
      :session="session" @wait="showStats = true" :contributors="contributors"
      :personalContributor="personalContributor" :ideas="ideasWithTags" :votes="votes" />
    <Closing v-if=" session.phase === 'closing' && personalContributor" :session="session" :contributors="contributors"
      :personalContributor="personalContributor" :ideas="ideas" :votes="votes" />
    <div v-if="session.phase != 'closing' " class="timer__container">
      <SandclockIcon v-if="session.seconds_left" />
      <div v-if="session.seconds_left" class="timer"
        :style="{ '--progress': `${(1 - session.seconds_left / session.method.time_limit) * 360}deg` }">
        {{ session.seconds_left }}
      </div>
    </div>
  </main>
  <main class="isLoading" v-if="isLoading">
    <div> <l-dot-pulse size="43" speed="1.3" color="#91b4b2"></l-dot-pulse></div>
  </main>

</template>

<script setup>
import SandclockIcon from './icons/SandclockIcon.vue';
import { ref, onMounted, onUnmounted, nextTick, computed, watch } from 'vue';
import { sessionId } from '../js/eventBus.js'
import axios from 'axios';
import Rollenwahl from './Rollenwahl.vue';
import { useRoute, useRouter } from 'vue-router';
const router = useRouter();
const route = useRoute();
import PauseIcon from '../components/icons/PauseIcon.vue';
import ProfileIcon from '../components/icons/ProfileIcon.vue';
import Voting from '../components/phases/Voting.vue';
import Collecting from '../components/phases/Collecting.vue';
import Closing from '../components/phases/Closing.vue';
import Lobby from '../components/phases/Lobby.vue';
import PlayIcon from '../components/icons/PlayIcon.vue';
import IconComponents from '../components/IconComponents.vue';
import { dotPulse } from 'ldrs'

const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});

const personalContributor = ref(null);
const errorMsg = ref(null);
const session = ref(null);
const userId = ref(props.userId);
const contributors = ref(null);
const ideas = ref([])
let pingInterval;

const headlineHeight = ref('auto');
const baseHeight = 1.5;
const showStats = ref(false);

const votes = ref(null);
const isStopping = ref(false);
const isStarting = ref(false);
const timer = ref(null);
const isLoading = ref(true);


const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};

dotPulse.register();

const emit = defineEmits(['updateSessionId']);

const updateSessionId = () => {
  sessionId.value = route.params.id;
  emit('updateSessionId', sessionId.value);
}

// Der folgende Codeabschnitt wurde mit Unterstützung von Claude 3.5 Sonnet erstellt
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
//

const ideasWithTags = computed(() => {
  return ideas.value ? ideas.value.filter(idea => idea.tag) : [];
});

const ideasWithoutTags = computed(() => {
  return ideas.value ? ideas.value.filter(idea => !idea.tag) : [];
});

const getSession = () => {
  axios.get(`/api/session/${sessionId.value}`)
    .then(response => {
      session.value = response.data.session;
    })
    .catch(error => {
      console.error('Error fetching session', error);
      router.push('/brainframe/join');
    })
};

const getContributors = () => {
  axios.get(`/api/contributors/${sessionId.value}/${userId.value}`)
    .then(response => {
      contributors.value = response.data.contributors;
      personalContributor.value = contributors.value.find(c => c.isMe);
    })
    .catch(error => {
      console.error('Error fetching contributors', error);
    })
};

const getIdeas = () => {
  axios.get(`/api/ideas/${sessionId.value}`)
    .then(response => {
      ideas.value = response.data.ideas;
    })
    .catch(error => {
      console.error('Error fetching ideas', error);
    })
}

const getVotes = () => {
  axios.get(`/api/votes/${sessionId.value}`)
    .then(response => {
      votes.value = response.data.votes;
    })
    .catch(error => {
      console.error('Error fetching votes', error);
    })
}

const joinSession = () => {
  if (sessionId.value && userId) {
    axios.post('/api/contributor/join', {
      session_id: sessionId.value,
      user_id: userId.value
    })
      .then(response => {

      })
      .catch(error => { console.error('Error joining session:', error) });
  }
};

const leaveSession = () => {
  if (sessionId.value && userId) {
    axios.post('/api/contributor/leave', {
      session_id: sessionId.value,
      user_id: userId.value
    })
      .then(response => {

      })
      .catch(error => { console.error('Error leaving session:', error) });
  }
};

const ping = () => {
  if (sessionId.value && userId.value) {
    axios.post('/api/contributor/ping', {
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

const startSession = () => {
  if (personalContributor.value.isHost) {
    isStarting.value  = true;
    if (session.value.phase === "voting") {
      session.value.vote_round++;
    }
    else if (session.value.phase === "collecting") {
      session.value.collecting_round++;
    }
    axios.post('/api/session/start', {
      session_id: sessionId.value,
      vote_round: session.value.vote_round,
      collecting_round: session.value.collecting_round
    })
      .catch(error => {
        console.error('Error starting Session', error);
      })
      .finally(() => {
        isStarting.value  = false;
        isLoading.value = false;
      });
  }
}

const stopSession = () => {
  isLoading.value = true;
  if (personalContributor.value.isHost) {
    isStopping.value = true;
    axios.post('/api/session/stop', {
      session_id: sessionId.value,
      vote_round: session.value.vote_round,
      collecting_round: session.value.collecting_round
    })
      .catch(error => {
        errorMsg.value = error.response?.data?.error || 'Ein unerwarteter Fehler ist aufgetreten';
        console.error('Error stopping Session', error);
      })
      .finally(() => {
        isLoading.value = false;
        isStopping.value = false;
      });
  }
}

const resumeSession = () => {
  if (personalContributor.value.isHost && session.value.seconds_left > 0) {
    axios.post('/api/session/resume', {
      session_id: sessionId.value,
    })
      .catch(error => {
        console.error('Error resuming Session', error);
      })
  }
}

const pauseSession = () => {
  if (personalContributor.value.isHost) {
    axios.post('/api/session/pause', {
      session_id: sessionId.value,
    })
      .catch(error => {
        console.error('Error pausing Session', error);
      })
  }
}

const handleVisibilityChange = () => {
  if (sessionId.value > 0 && userId)
    if (document.hidden) {
      leaveSession();
    } else {
      joinSession();
    }
};

const startTimer = () => {
  clearInterval(timer.value);
  timer.value = setInterval(() => {
    if (session.value.seconds_left > 0 && !session.value.isPaused) {
      session.value.seconds_left--;
    } else if (session.value.seconds_left === 0 && !session.value.isPaused) {
      stopTimer();
      stopSession();
    } else {
      stopTimer();
    }
  }, 1000);
};

const currentRoundIdeas = computed(() =>
  ideas.value.filter(idea => idea.round == session.value.collecting_round)
);

watch(() => currentRoundIdeas.value, (newValue) => {
  const currentRoundContributorIdeas = newValue.filter(idea => idea.contributor_id === personalContributor.value.id);
  if (session.value.method.idea_limit &&
    session.value.method.idea_limit <= currentRoundContributorIdeas.length) {
    showStats.value = true;
  }
}, { deep: true });

const stopTimer = () => {
  clearInterval(timer.value);
};

onMounted(async () => {
  isLoading.value = true;
  try {
    await Promise.all([
      updateSessionId(),
      getContributors(),
      getSession(),
      getIdeas(),
      getVotes()
    ]);
    startTimer();
    setupEventListeners();
    setupWindowListeners();
    await nextTick();
    await joinSession();
    adjustHeadline();
  } catch (error) {
    console.error("Fehler beim Laden:", error);
  } finally {
    isLoading.value = false;
  }
});
const setupEventListeners = () => {
  Echo.channel('session.' + sessionId.value)
    .listen('UserPickedRole', (e) => {
      contributors.value.push(e.formattedContributor);
      if (e.formattedContributor.user_id === userId.value) { //wenn man selbst rolle gewählt hat
        personalContributor.value = e.formattedContributor;
        personalContributor.value.isMe = true;
      }
      session.value.method.round_limit = Math.max(contributors.value.length, 2);
    })
    .listen('SessionStarted', (e) => {
      errorMsg.value = null;
      session.value = e.formattedSession;
      stopTimer();
      const delay = Math.random() * 3000;
      setTimeout(() => {
        getIdeas();
      }, delay);
      startTimer();
      showStats.value = false;
    })
    .listen('SessionPaused', (e) => {
      errorMsg.value = null;
      session.value = e.formattedSession;
      stopTimer();
      showStats.value = false;
    })
    .listen('RotateContributorRoles', (e) => {
      contributors.value = contributors.value.map(contributor => {
        const updatedRole = e.contributorRoles.find(role => role.id === contributor.id);
        if (updatedRole) {
  
          return {
            ...contributor,
            name: updatedRole.name,
            icon: updatedRole.icon
          };
        }
        return contributor;
      });
      personalContributor.value = contributors.value.find(c => c.id === personalContributor.value.id);
    })
    .listen('SessionResumed', (e) => {
      errorMsg.value = null;
      session.value = e.formattedSession;
      stopTimer();
      startTimer();
      showStats.value = false;
    })
    .listen('SessionStopped', (e) => {
      errorMsg.value = null;
      stopTimer();
      session.value = e.formattedSession;
      showStats.value = false;
    })
    .listen('UserJoinedSession', (e) => {
      contributors.value.find(c => c.id === e.contributorId).is_active = true;
    })
    .listen('UserLeftSession', (e) => {
      contributors.value.find(c => c.id === e.contributorId).is_active = false;
    })
    .listen('UserSentVote', (e) => {
      votes.value.push({ ...e.vote });
    })
    .listen('UserSentIdea', (e) => {
      ideas.value.push({ ...e.idea });
    })
    .listen('IdeasFormatted', (e) => {
      e.ideas.forEach(idea => {
        ideas.value.push(idea);
      });
    });
}
const setupWindowListeners = () => {
  document.addEventListener('visibilitychange', handleVisibilityChange);
  window.addEventListener('beforeunload', leaveSession);
  pingInterval = setInterval(ping, 30000);
}

watch(() => session.value, (newSession) => {
  if (newSession?.method?.name === '6-3-5') {
    session.value.method.round_limit = Math.max(contributors.value.length, 2);
  }
}, { immediate: true, deep: true });
onUnmounted(() => {
  document.removeEventListener('visibilitychange', handleVisibilityChange);
  window.removeEventListener('beforeunload', leaveSession);
});
</script>