<template>
    <component :is="votingMethods[votingMethod]" :personalContributor="personalContributor" :ideas="ideas"
        :votes="votes" :session="session"  @sendVote="sendVote" @wait="emit('wait')"/>
        <button class="accent stop" v-if="personalContributor.isHost" @click="confirmStop"> Runde beenden</button>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import StarVote from '../voting-methods/StarVote.vue';
import RankingVote from '../voting-methods/RankingVote.vue';
import LeftRightVote from '../voting-methods/LeftRightVote.vue';
import SwipeVote from '../voting-methods/SwipeVote.vue';
const props = defineProps({
    session: {
        type: Object,
        required: true
    },
    contributors: {
        type: Object,
        required: true
    },
    personalContributor: {
        type: Object,
        required: true
    },
    votes: {
        type: Object,
        required: true
    },
    ideas: {
        type: Object,
        required: true
    }
});

const emit = defineEmits (['wait', 'stop']);
const session = ref(props.session);
const votes = ref(props.votes);
const votingMethod = ref(null);
const voteRoundLimit = ref(null);
const voteCountPrevRound = ref(null);
const personalContributor = ref(props.personalContributor);

const votingMethods = {
    StarVote,
    RankingVote,
    LeftRightVote,
    SwipeVote
};
const ideas = computed(() => {
  return props.ideas.map(idea => ({
    ...idea,
    contributorIcon: props.contributors.find(c => c.id === idea.contributor_id)?.icon
  }));
});
const confirmStop = () => {
        if (confirm('Aktuelle Runde vorzeitig beenden?')) {
            emit('stop');
        }
};
const pickVotingMethod = () => {
    if (ideas.value.length <= 5) {
        votingMethod.value = 'RankingVote'
        voteRoundLimit.value = 1;
    }
    else if (ideas.value.length > 5 && ideas.value.length <= 15) {
        votingMethod.value = 'StarVote'
        voteRoundLimit.value = 2;
    }
    else if (ideas.value.length > 15 && ideas.value.length <= 30) {
        votingMethod.value = 'SwipeVote'
        voteRoundLimit.value = 3;
    }
    else if (ideas.value.length > 30) {
        votingMethod.value = 'LeftRightVote'
        voteRoundLimit.value = 4;
    }
    else { console.log("error while picking voteMethod") }

    if (session.value.vote_round > 1) {
       
        voteCountPrevRound.value = new Set(votes.value.filter(vote => vote.round === session.value.vote_round - 1).map(vote => vote.idea_id)).size;
        if (voteCountPrevRound.value <= 15) {
            votingMethod.value = 'RankingVote'
        } else if (voteCountPrevRound.value <= 30) {
            votingMethod.value = 'StarVote'
        }
        else if (voteCountPrevRound.value > 30) {
            votingMethod.value = 'SwipeVote'
        }
    }
}
const sendVote = ({ ideaId, voteType, voteValue }) => {
  axios.post('/api/vote/store', {
        session_id: session.value.id,
        idea_id: ideaId,
        contributor_id: personalContributor.value.id,
        vote_type: voteType,
        vote_value: voteValue,
        vote_round: session.value.vote_round
      })
    .then(response => {
    })
    .catch(error => {
      console.error('Fehler beim Speichern deines Votes', error);
    });
};

onMounted(() => {
    pickVotingMethod();
});
</script>