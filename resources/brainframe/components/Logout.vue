<template>
  <main class="login__main">
    <form class="login-form" @submit.prevent="login">
      <div class="login-form__buttons">
        <button class="primary" @click="logout" >Abmelden</button>
      </div>

      <p v-if="errorMsg">{{ errorMsg }}</p>
    </form>
  </main>
</template>
<script setup>
import axios from 'axios';
import { ref, toRef } from 'vue';
import { useRouter } from 'vue-router';
const router = useRouter();
const props = defineProps({
  userId: {
    type: Number,
    required: true
  }
});
const errorMsg = (null);
const userId = ref(props.userId);
const emit = defineEmits(['logout']);
const logout = () => {
    axios.post('/api/logout', {
    user_id: userId.value
  })
  .then(() => {
    localStorage.removeItem('authToken');
    localStorage.removeItem('user_id');
    emit('logout');
    
  })
  .catch(error => {
    console.error('Error logging out:', error);
  });
};
</script>