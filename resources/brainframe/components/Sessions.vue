<template>
  <div class="headline__sessions">
    <h1>User Sessions</h1>
    <p class="response" v-if="responseMsg">{{responseMsg}}</p>
  </div>
  <main>
    <div class="sessions__table__container">
    <table class="sessions__table" v-if="userSessions && userSessions.length > 0">
      <thead>
        <tr>
          <th><PinIcon/></th>
          <th><TargetIcon/></th>
          <th><ProfileIcon/></th>
          <th><CalendarIcon/></th>
          <th>Method</th>
          <th>Phase</th>
          <th>
            <SettingsIcon />
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="session in userSessions" :key="session.session_id">
          <td>
            <router-link :to="'/brainframe/'+session.session_id">{{ session.session_id }}</router-link>
          </td>
          <td>
            <template v-if="session.isEditing">
              <input v-model="session.editedTarget" :placeholder="session.target" />
            </template>
            <template v-else>
              {{ session.target }}
            </template>
          </td>
          <td class="center"> <component :is="getIconComponent(session.role)" /></td>
          <td>
            {{
            new Date(session.updated_at).toLocaleTimeString('de-DE', {
            hour: '2-digit',
            minute: '2-digit'
            }) + ' Uhr' + ' ' +
            new Date(session.updated_at).toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: '2-digit'
            })
            }}
          </td>
          <td>
            <template
              v-if="session.isEditing && (session.active_phase === null || session.active_phase ==='collectingPhase') && (session.active_round > 1 || session.active_round === null)">
              <select v-model="session.editedMethodId">
                <option v-for="method in methods" :key="method.id" :value="method.id">
                  {{ method.name }}
                </option>
              </select>
            </template>
            <template v-else>
              {{ session.method_name }}
            </template>
          </td>
          <td class="center" v-if="session.active_phase == 'closingPhase'"><SwooshIcon/></td>
          <td class="center" v-if="session.active_phase == 'collectingPhase' || !session.active_phase"><BrainIcon/></td>
          <td class="center" v-if ="session.active_phase == 'lobby'"><PauseIcon/></td>
          <td class="center" v-if ="session.active_phase == 'votingPhase'"><FunnelIcon/></td>
          <td v-if="session.host_id === userId" class="settings">
            <template v-if="session.isEditing">
              <button @click="sendAlterSession(session)">Speichern</button>
            </template>
            <template v-else>
              <div v-if="session.active_phase === null || session.active_phase === 'collectingPhase'">
                <BrushIcon @click="alterSession(session)" />
              </div>
              <div v-if="!session.confirmDelete" @click="session.confirmDelete = true" class="x">X</div>
              <div v-else>
                <button @click="deleteSession(session)">Bestätigen</button>
                <button @click="session.confirmDelete = false">Abbrechen</button>
              </div>
            </template>
          </td>
          <td v-else></td>
        </tr>
      </tbody>
    </table>
    <p v-else>Keine Sitzungen gefunden.</p>
  </div>
  
  </main>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import SettingsIcon from '../components/icons/SettingsIcon.vue';
import BrushIcon from '../components/icons/BrushIcon.vue';
import TargetIcon from '../components/icons/TargetIcon.vue';
import SwooshIcon from '../components/icons/SwooshIcon.vue';
import PinIcon from '../components/icons/PinIcon.vue';
import CalendarIcon from '../components/icons/CalendarIcon.vue';
import ProfileIcon from '../components/icons/ProfileIcon.vue';
import BrainIcon from '../components/icons/BrainIcon.vue';
import PauseIcon from '../components/icons/PauseIcon.vue';
import FunnelIcon from '../components/icons/FunnelIcon.vue';
import IconComponents from '../components/IconComponents.vue';
const getIconComponent = (iconName) => {
  return IconComponents[iconName] || null;
};
const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
});
const responseMsg = ref(null);
const userSessions = ref([]);
const userId = ref(props.userId);
const methods = ref(null);
const alterSession = (session) => {
  session.isEditing = true;
  session.editedTarget = session.target;
  session.editedMethodId = session.method_id;
};
const getMethods = () => {
  axios.get('/api/methods')
    .then(response => {
      methods.value = response.data;
      const defaultMethod = methods.value.find(m => m.id === 1);
      if (defaultMethod) methods.value[0] = { ...defaultMethod };
    }).catch(error => {
      console.error('Error fetching methods', error);
    });
}; 
const sendAlterSession = (session) => {
  axios.post('/api/session/put', {
    session_id: session.session_id,
    user_id: userId.value,
    methodId: session.editedMethodId,
    target: session.editedTarget
  })
    .then(response => {
      console.log('Server response:', response.data);
      responseMsg.value = response.data.message;
      session.target = session.editedTarget;
      session.method_id = session.editedMethodId;
      session.method_name = methods.value.find(m => m.id === session.editedMethodId).name;
      session.isEditing = false;
    })
    .catch(error => {
      console.error('Fehler bei update der Session', error);
      responseMsg.value = response.data.error;
    });
};

const deleteSession = (session) => {
  axios.post('/api/session/delete', {
    session_id: session.session_id,
    user_id: userId.value
  })
    .then(response => {
      console.log('Server response:', response.data);
      responseMsg.value = response.data.message;
      userSessions.value = userSessions.value.filter(s => s.session_id !== session.session_id);
    })
    .catch(error => {
      console.error('Fehler beim Löschen der Session', error);
      responseMsg.value = response.data.error;
    });
};
const getUserSessions = () => {
  axios.get(`/api/user/${userId.value}/sessions`)
    .then(response => {
      userSessions.value = response.data;
      console.log('userSessions: ', userSessions.value);
      console.log('meine Id: ', userId.value);
    })
    .catch(error => {
      console.error('Error fetching sessions', error);
    });
};

onMounted(() => {
  getUserSessions();
  getMethods();
});
</script>