<template>
  <main v-if="session && ideas && votes">

    <h1 class="headline__session-target">
      {{ session.target }} {{ session.phase }}
    </h1>
    <Rollenwahl v-if="!personalContributor" :session="session" :userId="userId" />
    <div v-if="personalContributor && session.phase != 'closing'" class="session_headline__details">
      <div @click="session.phase !== 'closing' && !session.isPaused ? showStats = !showStats : null">
        <ProfileIcon />
        <p>
          {{ contributors.filter(c => c.is_active).length }} | {{ contributors.length }}
        </p>
      </div>
      <div v-if="session.phase ==='collecting'">
        <p>
          <BrainIcon />
        </p>
      </div>
      <div v-if="session.phase ==='voting'">
        <p>
          <FunnelIcon />
        </p>
      </div>
      <div v-if="session.phase ==='closing'">
        <p>
          <SwooshIcon />
        </p>
      </div>
      <div @click="session.isPaused && personalContributor.isHost ? resumeSession() : pauseSession()">
        <p>{{ session.isPaused ? 'pausiert' : 'gestartet' }}</p>
      </div>
      <div>
        <p>{{ session.method.name }} Methode</p>
      </div>
      <div>
        <p v-if="personalContributor.name != 'Default'">
          <component :is="getIconComponent(personalContributor.icon)" />
        </p>
        <p>{{ personalContributor.name }}</p>
      </div>
    </div>
    <Lobby v-if="(session.isPaused  && session.phase != 'closing' || showStats && session.phase != 'closing') && personalContributor" :session="session" :contributors="contributors"
      :personalContributor="personalContributor" :votes="votes" @exit="showStats = false" @start="startSession" @stop="stopSession" :ideas="ideasWithoutTags" />
    <Collecting @stop="stopSession" :session="session" :personalContributor="personalContributor" :ideas="ideas"
      v-if="session.phase === 'collecting' && !session.isPaused &&  !showStats && personalContributor"
      @wait="showStats = true" />
    <Voting v-if=" session.phase === 'voting' && !session.isPaused && !showStats && personalContributor"
      :session="session" @wait="showStats = true" :contributors="contributors"
      :personalContributor="personalContributor" :ideas="ideasWithTags"  :votes="votes" />
    <Closing
      v-if=" session.phase === 'closing'" :session="session" :contributors="contributors" :personalContributor="personalContributor" :ideas="ideas" :votes="votes"/>
    <div v-if="session.phase != 'closing' " class="timer__container">
      <SandclockIcon />
      <div class="timer"
        :style="{ '--progress': `${(1 - session.seconds_left / session.method.time_limit) * 360}deg` }">
        {{ session.seconds_left }}
      </div>
    </div>
  </main>

</template>

<script setup>
import SandclockIcon from './icons/SandclockIcon.vue';
import { ref, onMounted, onUnmounted, nextTick, computed} from 'vue';
import { sessionId } from '../js/eventBus.js'
import axios from 'axios';
import Rollenwahl from './Rollenwahl.vue';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
const route = useRoute();
import SwooshIcon from '../components/icons/SwooshIcon.vue';
import BrainIcon from '../components/icons/BrainIcon.vue';
import PauseIcon from '../components/icons/PauseIcon.vue';
import FunnelIcon from '../components/icons/FunnelIcon.vue';
import ProfileIcon from '../components/icons/ProfileIcon.vue';
import Voting from '../components/phases/Voting.vue';
import Collecting from '../components/phases/Collecting.vue';
import Closing from '../components/phases/Closing.vue';
import Lobby from '../components/phases/Lobby.vue';
import IconComponents from '../components/IconComponents.vue';
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const personalContributor = ref(null);
const wait = () => {
  console.log('wait');
  showStats.value = true;
}
const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});
const emit = defineEmits(['updateSessionId']);

const updateSessionId = () => {
  sessionId.value = route.params.id;
  emit('updateSessionId', sessionId.value);
}

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
const ideasWithTags = computed(() => {
  return ideas.value ? ideas.value.filter(idea => idea.tag) : [];
});
const ideasWithoutTags = computed(() => {
  return ideas.value ? ideas.value.filter(idea => !idea.tag) : [];
});
//session
const session = ref(null);
const userId = ref(props.userId);
const contributors = ref(null);
const ideas = ref(null);
let pingInterval;

// UI und Zustand
const headlineHeight = ref('auto');
const baseHeight = 1.5;
const showStats = ref(false);

const getSession = () => {
  console.log("getSession");
  axios.get(`/api/session/${sessionId.value}`)
    .then(response => {
      session.value = response.data.session;
      console.log("session.value", session.value);
    })
    .catch(error => {
      console.error('Error fetching session', error);
    })
};

const getContributors = () => {
  console.log("getContributors");
  axios.get(`/api/contributors/${sessionId.value}/${userId.value}`)
    .then(response => {
      contributors.value = response.data.contributors;
      console.log("contributors.value", contributors.value);
      personalContributor.value = contributors.value.find(c => c.isMe);
      console.log("personalContributor.value", personalContributor.value);
    })
    .catch(error => {
      console.error('Error fetching contributors', error);
    })
};
const getIdeas = () => {
  console.log("getIdeas");
  axios.get(`/api/ideas/${sessionId.value}`)
    .then(response => {
      ideas.value = response.data.ideas;
      console.log("ideas got", ideas.value)
    })
    .catch(error => {
      console.error('Error fetching ideas', error);
    })
}
const votes = ref(null);
const getVotes = () => {
  console.log("getVotes");
  axios.get(`/api/votes/${sessionId.value}`)
    .then(response => {
      votes.value = response.data.votes;
      console.log("votes got", votes.value)
    })
    .catch(error => {
      console.error('Error fetching votes', error);
    })
}

const joinSession = () => {
  console.log('join', sessionId.value, userId.value)
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
  console.log('leave', sessionId.value, userId.value)
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
  console.log("startSession", personalContributor.value.isHost)

  if (personalContributor.value.isHost) {
    if (session.value.phase === "voting") {
      session.value.vote_round++;
    }
    else if (session.value.phase === "collecting") {
      session.value.collecting_round++;
    }
    console.log(session.value.vote_round, "session.value.vote_round", session.value.collecting_round, "session.value.collecting_round")
    axios.post('/api/session/start', {
      session_id: sessionId.value,
      vote_round: session.value.vote_round,
      collecting_round: session.value.collecting_round
    })
      .then(response => {
        console.log('Success starting Session', response);
      })
      .catch(error => {
        console.error('Error starting Session', error);
      })
  }
}
const stopSession = () => {
  console.log("stopSession", personalContributor.value.isHost)

  if (personalContributor.value.isHost) {
    axios.post('/api/session/stop', {
      session_id: sessionId.value,
      vote_round: session.value.vote_round,
      collecting_round: session.value.collecting_round
    })
      .then(response => {
        console.log('Success stopping Session', response);
      })
      .catch(error => {
        console.error('Error stopping Session', error);
      })
  }
}

const resumeSession = () => {
  console.log("resumeSession", personalContributor.value.isHost)

  if (personalContributor.value.isHost && session.value.seconds_left > 0) {
    axios.post('/api/session/resume', {
      session_id: sessionId.value,
    })
      .then(response => {
        console.log('Success resuming Session', response);
      })
      .catch(error => {
        console.error('Error resuming Session', error);
      })
  }
}

const pauseSession = () => {
  console.log("pauseSession")
  if (personalContributor.value.isHost) {
    axios.post('/api/session/pause', {
      session_id: sessionId.value,
    })
      .then(response => {
        console.log('Success pausing Session', response);
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

const timer = ref(null);
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

const stopTimer = () => {
  clearInterval(timer.value);
};

onMounted(() => {
  updateSessionId();
  getContributors();
  getSession();
  getIdeas();
  startTimer();
  getVotes();
  Echo.channel('session.' + sessionId.value)
    .listen('UserPickedRole', (e) => {
      console.log("e.formattedContributor", e.formattedContributor)
      contributors.value.push(e.formattedContributor);

      if (e.formattedContributor.user_id === userId.value) { //wenn man selbst rolle gewählt hat
        personalContributor.value = e.formattedContributor;
        personalContributor.value.isMe = true;
        console.log("personalContributor Picked", personalContributor.value);
      }
      console.log("UserPickedRole", contributors.value);
    })
    .listen('SessionStarted', (e) => {
      console.log("Event: SessionStarted", e);
      session.value = e.formattedSession;
      startTimer();
    })
    .listen('SessionPaused', (e) => {
      console.log("Event: SessionPaused", e);
      session.value = e.formattedSession;
      stopTimer();
    })
    .listen('SessionResumed', (e) => {
      console.log("Event: SessionResumed", e);
      session.value = e.formattedSession;
      startTimer();
    })
    .listen('SessionStopped', (e) => {
      console.log("Event: SessionStopped", e);
      stopTimer();
      session.value = e.formattedSession;
    })
    .listen('UserJoinedSession', (e) => {
      console.log("User Joined", e.contributorId);
      contributors.value.find(c => c.id === e.contributorId).is_active = true;
    })
    .listen('UserLeftSession', (e) => {
      console.log("User Left", e.contributorId);
      contributors.value.find(c => c.id === e.contributorId).is_active = false;
    })
    .listen('UserSentVote', (e) => {
      console.log("sent vote:", e);
      votes.value.push({ ...e.vote });
    })
    .listen('UserSentIdea', (e) => {
      console.log("sent idea:", e);
      ideas.value.push({ ...e.idea });
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
  document.removeEventListener('visibilitychange', handleVisibilityChange);
  window.removeEventListener('beforeunload', leaveSession);
});
</script>