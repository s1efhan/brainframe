<template>
    <nav>
        <ul>
            <li> <RouterLink to="/brainframe/profile"><div><ProfileIcon/></div><div>Profil </div> </RouterLink></li>
            <li> <RouterLink to="/brainframe/sessions"><div></div><div><SessionsIcon/></div><div>Sessions </div> </RouterLink></li>
            <li> 
                <RouterLink v-if="sessionId" :to="`/brainframe/${sessionId}`">
                    <div><BrainFrameIcon/></div><div>{{ sessionId }} </div>
                </RouterLink>
            <RouterLink v-if="!sessionId" @click="resetSessionId" to="/brainframe"><div><BrainFrameIcon/></div><div>Beitreten </div></RouterLink>
            </li>
            <li> <RouterLink to="/brainframe/create"><div><CreateIcon/></div><div>Erstellen </div> </RouterLink></li>
            <li> <RouterLink to="/brainframe/knowledge"><div><KnowledgeIcon/></div><div>Wissen </div></RouterLink></li>
        </ul>
    </nav>
</template>
<script setup>
import {toRef} from 'vue';
import KnowledgeIcon from '../components/icons/KnowledgeIcon.vue';
import SessionsIcon from '../components/icons/SessionsIcon.vue';
import BrainFrameIcon from '../components/icons/BrainFrameIcon.vue';
import CreateIcon from '../components/icons/CreateIcon.vue';
import ProfileIcon from '../components/icons/ProfileIcon.vue';
const props = defineProps({
  sessionId: {
    type: [String, Number],
    required: true
  }
});
const emit = defineEmits(['resetSessionId']);
const sessionId =  toRef(props, 'sessionId');
const resetSessionId = () =>
{
    sessionId.value = null;
    emit('resetSessionId', sessionId.value);
}
</script>