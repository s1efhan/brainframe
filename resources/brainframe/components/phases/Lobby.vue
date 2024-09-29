<template>
    <section class="contributors_board__container">
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
                        <li v-if="session.isPaused">Der Host hat die Session für alle Teilnehmer pausiert. Der Countdown ist angehalten</li>
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

                        <th>Zuletzt Aktiv</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="contributor in contributors" :key="contributor.id">
                        <td>{{ contributor.icon }}</td>
                        <td>{{ contributor.name }}</td>
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
            <div class="lobby__start__container">
                <button class="primary" v-if="session.isPaused && personalContributor.isHost" @click="emit('start')">Runde starten</button>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import SwooshIcon from '../icons/SwooshIcon.vue';

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
});
const emit = defineEmits(['start', 'exit']);
const showInfo = ref(true);
const contributors = ref(props.contributors);
const personalContributor = ref(props.personalContributor);
const session = ref(props.session);
const ideas = ref(props.ideas);
</script>