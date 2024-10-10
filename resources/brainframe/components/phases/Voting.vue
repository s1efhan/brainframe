<template>
    <h2>Voting</h2>
    <component :is="votingMethods[votingMethod]" :personalContributor="personalContributor" :ideas="ideas"
        :votes="votes" :session="session"  @sendVote="sendVote" @wait="emit('wait')"/>
</template>
<script setup>
import { ref, onMounted } from 'vue';
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

const votingMethods = {
    StarVote,
    RankingVote,
    LeftRightVote,
    SwipeVote
};
const emit = defineEmits (['wait']);
const session = ref(props.session);
const votes = ref(props.votes);
const ideas = ref(props.ideas);
const votingMethod = ref(null);
const voteRoundLimit = ref(null);
const voteCountPrevRound = ref(null);
const personalContributor = ref(props.personalContributor);
const pickVotingMethod = () => {
    console.log("pickvotingMethod - Ideas: ", ideas.value.length);
    console.log("vote_round: ", session.value.vote_round);
    if (ideas.value.length <= 5) {
        votingMethod.value = 'RankingVote'
        voteRoundLimit.value = 1;
    }
    else if (ideas.value.length > 5 && ideas.value.length <= 15) {
        votingMethod.value = 'StarVote'
        voteRoundLimit.value = 2;
        console.log("star")
    }
    else if (ideas.value.length > 15 && ideas.value.length <= 30) {
        votingMethod.value = 'SwipeVote'
        voteRoundLimit.value = 3;
    }
    else if (ideas.value.length > 30) {
        votingMethod.value = 'LeftRightVote'
        voteRoundLimit.value = 4;
    }
    else { console.log("error") }

    if (session.value.vote_round > 1) {
       
        voteCountPrevRound.value = new Set(votes.value.filter(vote => vote.round === session.value.vote_round - 1).map(vote => vote.idea_id)).size;
        console.log("voteCountPrevRound", voteCountPrevRound.value)
        if (voteCountPrevRound.value <= 15) {
            votingMethod.value = 'RankingVote'
        } else if (voteCountPrevRound.value <= 30) {
            votingMethod.value = 'StarVote'
        }
        else if (voteCountPrevRound.value > 30) {
            votingMethod.value = 'SwipeVote'
        }
    }
    console.log("votingMethod picked: ", votingMethod.value)
}
const sendVote = ({ ideaId, voteType, voteValue }) => {
  console.log('Sending vote data:', {
    session_id: session.value.id,
    idea_id: ideaId,
    contributor_id: personalContributor.value.id,
    vote_type: voteType,
    vote_value: voteValue,
    vote_round: session.value.vote_round
  });
  axios.post('/api/vote/store', {
        session_id: session.value.id,
        idea_id: ideaId,
        contributor_id: personalContributor.value.id,
        vote_type: voteType,
        vote_value: voteValue,
        vote_round: session.value.vote_round
      })
    .then(response => {
      console.log('Server response:', response.data);
    })
    .catch(error => {
      console.error('Fehler beim Speichern deines Votes', error);
    });
};

onMounted(() => {
    pickVotingMethod();
    console.log('votingMethod.value', votingMethod.value)
});
</script>