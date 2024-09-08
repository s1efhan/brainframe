<template>
    <div class="lobby__headline__container">
    <h2 class="lobby__headline">Lobby</h2></div>
    <section class="contributors_board__container">
        
        <div class="contributors_board">
            <div class="info__container">
                <button @click="emit('exit')" class="primary">X</button>
                <div @click="showInfo = !showInfo" class="join__info">
                    <p>i</p>
                </div>
            </div>
            <div v-if="showInfo" class="info__text__container">
                <div class="info__text">
                    <h3>Board</h3>
                    <ul>
                        <li>Hier siehst du die bisherigen <strong>Stats deiner BrainStorming Session</strong></li>
                    </ul>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>SessionId</th>
                        <th>Methode</th>
                        <th v-for="round in Object.keys(ideasCount)" :key="round"
                            :class="{ 'active_round': round == currentRound }">
                            Runde {{ round }}
                        </th>
                        <th>Gesamt</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ sessionId }}</td>
                        <td>{{ method.name }}</td>
                        <td class="center" v-for="(roundData, round) in ideasCount" :key="round"
                            :class="{ 'active_round': round == currentRound }">
                            {{ roundData.sum }}
                        </td>
                        <td class="center">{{ getTotalIdeas() }}</td>
                    </tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Name</th>
                        <th>Ideenzahl</th>
                        <th>Zuletzt Aktiv</th>
                        <th v-if="maxIdeaInput">Fertig?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(contributor, index) in contributors" :key="index" :class="{
      'host-stats': contributor.id === sessionHostId,
      'my-stats': contributor.id === personalContributor.id
    }">
                        <td class="center">
                            <component :is="getIconComponent(contributor.icon)" />
                        </td>
                        <td :class="{ host: contributor.id === sessionHostId }">
                            {{ contributor.id === sessionHostId ? `Host: ${contributor.role_name}` : contributor.role_name }}
                        </td>
                        <td class="center">{{ (ideasCount[currentRound]?.contributors?.[contributor.id] || 0) + (maxIdeaInput ? ' / ' + maxIdeaInput : '') }}</td>
                        <td>{{
                            (() => {
                            const diff = (new Date() - new Date(contributor.last_active)) / 60000;
                            return diff < 1 ? 'Jetzt' : diff < 60 ? `Vor ${Math.round(diff)} min` : `Vor
                                ${Math.floor(diff / 60)}h ${Math.round(diff % 60)} min` })() }}</td>
<td v-if="maxIdeaInput" class="center">{{ (ideasCount[currentRound]?.contributors?.[contributor.id] || 0) >= maxIdeaInput ? '✅' : '❌' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import IconComponents from './IconComponents.vue';
const props = defineProps({
    personalContributor: {
        type: Object,
        required: true
    },
    sessionHostId: {
        type: [String, Number],
        required: true
    },
    sessionId: {
        type: [String, Number],
        required: true
    },
    method: {
        type: Object,
        required: true
    },
    contributors: {
        type: [Object, null],
        required: true
    },
    currentRound: {
        type: [Number, null],
        required: true
    },
    ideasCount: {
        type: [Object, null],
        required: true
    }
});
const maxIdeaInput = ref(null);
const showInfo = ref(false);
const emit = defineEmits(['exit']);

const getTotalIdeas = () => {
    return Object.values(props.ideasCount).reduce((total, round) => total + round.sum, 0);
};
const getIconComponent = (iconName) => {
    return IconComponents[iconName] || null;
};
onMounted(() => {
    if(props.method.name === "6-3-5"){
        maxIdeaInput.value= 5;
    } 
    else if (props.method.name === "Crazy 8"){
        maxIdeaInput.value= 1;
    }
    maxIdeaInput.value= 1;
});

</script>