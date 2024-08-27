<template>
  <main class="login__main">
    <div class="headline__login">
      <h1>Login</h1>
    </div>
    <form class="login-form" @submit.prevent="login">
      <div class="login-form__email">
        <label for ="password">Email:</label>
        <input placeholder="max.mustermann@mail.de" id="email" type="email" v-model="email" required>
      </div>
      <div class="login-form__password">
        <label for ="password">Passwort:</label>
        <input placeholder="****************" id="password" :type="passwordType" v-model="password" required minlength="8">
        <input type="checkbox" id="showPassword" @change="togglePasswordVisibility">
      </div>
      <div class="login-form__buttons">
        <button class="primary">Anmelden</button>
        <button class="accent" @click="register">Registrieren</button>
      </div>

      <p v-if="errorMsg">{{ errorMsg }}</p>
    </form>
  </main>
</template>

<script setup>
import axios from 'axios';
import { toRef, ref } from 'vue';
import { useRouter } from 'vue-router';
const props = defineProps({
  userId: {
    type: Number,
    required: true
  }
});
const router = useRouter();
const email = ref(null);
const password = ref(null);
const errorMsg = ref(null);
const passwordType = ref('password');
const userId = toRef(props.userId);
const emit = defineEmits(['updateUserId']);
const login = () => {
  console.log(email.value, password.value);
  axios.post('/api/login',
    {
      email: email.value,
      password: password.value
    })
    .then(response => {
      console.log(response.data);
      errorMsg.value = "eingeloggt!";
      console.log(response.data.userId)
      emit('updateUserId', response.data.userId);
    })
    .catch(error => {
      console.error('Error sending user ID to server:', error);
      errorMsg.value = "Fehler beim Login!";
    });
};

const register = () => {
  console.log(email.value, password.value, userId.value);
  axios.post('/api/register', {
    user_id: userId.value,
    email: email.value,
    password: password.value
  })
    .then(response => {
      login();
    })
    .catch(error => {
      console.log(error);
      errorMsg.value = "Fehler beim Registrieren!";
    });
};

const togglePasswordVisibility = () => {
  passwordType.value = passwordType.value === 'password' ? 'text' : 'password';
};
</script>