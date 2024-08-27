<template>
  <main class="login__main">
    <div class="headline__login">
      <h1>Login</h1>
    </div>
    <form class="login-form" @submit.prevent>
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
        <button class="primary" @click="login">Anmelden</button>
        <button class="accent" @click="register">Registrieren</button>
      </div>

      <p v-if="errorMsg">{{ errorMsg }}</p>
    </form>
  </main>
</template>

<script setup>
import axios from 'axios';
import { ref, toRef, onMounted} from 'vue';
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
const userId = ref(props.userId);

const emit = defineEmits(['login']);
const authToken = ref(null);
const login = () => {
  axios.post('/api/login', {
    email: email.value,
    password: password.value
  })
  .then(response => {
     authToken.value = response.data.authToken;
     console.log('response.data.authToken', response.data.authToken)
     userId.value = response.data.userId;
    localStorage.setItem('authToken', authToken.value);
    localStorage.setItem('user_id', userId.value);
    if(authToken)
    emit('login', authToken);
  })
  .catch(error => {
    console.error('Error logging in:', error);
    errorMsg.value = "Fehler beim Login!";
  });
};

const register = () => {
  console.log(userId.value);
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