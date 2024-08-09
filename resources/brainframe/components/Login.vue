<template>
  <main>
    <h1>Login</h1>
    <form class="login-form" @submit.prevent="login">
      <label for="email">Email:</label>
      <input id="email" type="email" v-model="email" required><br>
      
      <label for="password">Passwort:</label>
      <input id="password" :type="passwordType" v-model="password" required minlength="8">
      <input type="checkbox" id="showPassword" @change="togglePasswordVisibility">
      <label for="showPassword">Passwort anzeigen</label><br>
      
      <div>
        <button class="primary">Anmelden</button>
        <button class="secondary" @click="register">Registrieren</button>
      </div>
      
      <p v-if="errorMsg">{{ errorMsg }}</p>
    </form>
  </main>
   </template>
   
   <script setup>
   import axios from 'axios';
   import { ref } from 'vue';
   import { useRouter } from 'vue-router';
   
   const router = useRouter();
   const email = ref('');
   const password = ref('');
   const errorMsg = ref('');
   const user = ref(null);
   const passwordType = ref('password');
   const isLoading = ref(false);
   
   const login = async () => {
    isLoading.value = true;
    try {
      const response = await axios.post('/api/login', {
        email: email.value,
        password: password.value
      });
      user.value = response.data.user;
      router.push('/brainframe');
    } catch (error) {
      errorMsg.value = error.response?.data?.message || 'Anmeldung fehlgeschlagen!';
    }
   };
const register = () => {
  axios.post('/api/register', {
    email: email.value,
    password: password.value
  })
  .then(response => {
    login();
  })
  .catch(error => {
    console.log(error);
  });
};

const togglePasswordVisibility = () => {
  passwordType.value = passwordType.value === 'password' ? 'text' : 'password';
};
</script>