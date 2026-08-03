<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, setToken } from '../services/api';

const router = useRouter();
const error = ref('');
const loading = ref(false);

const form = ref({
  name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const handleRegister = async () => {
  error.value = '';
  loading.value = true;

  try {
    const responsePayload = await api.post('/register', form.value, { auth: false });
    setToken(responsePayload.data.token);
    router.push('/');
  } catch (errorResponse) {
    error.value = errorResponse.firstMessage
      ? errorResponse.firstMessage()
      : errorResponse.message;
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="login-screen">
    <div class="login-box glass-effect">
      
      <div class="login-header">
        <h1 class="login-title">Gravityly</h1>
        <h1 class="login-subtitle">Sign-up</h1>
      </div>
      
      <p class="error" v-if="error">{{ error }}</p>
      
      <form @submit.prevent="handleRegister" class="login-form">
        <div class="input-group">
          <input type="text" v-model="form.name" placeholder="Full Name" required class="input-field" />
        </div>
        
        <div class="input-group">
          <input type="text" v-model="form.username" placeholder="Username" required class="input-field" />
        </div>
        
        <div class="input-group">
          <input type="email" v-model="form.email" placeholder="E-mail" required class="input-field" />
        </div>
        
        <div class="input-group">
          <input type="password" v-model="form.password" placeholder="Password (min. 8 characters)" required class="input-field" minlength="8" />
        </div>

        <div class="input-group">
          <input type="password" v-model="form.password_confirmation" placeholder="Confirm Password" required class="input-field" minlength="8" />
        </div>
        
        <button type="submit" class="btn" :disabled="loading">
          {{ loading ? 'Creating account...' : 'Register' }}
        </button>
        
        <!-- Link back to Login -->
        <router-link to="/login" class="btn register" style="text-decoration: none;">
          Already have an account?
        </router-link>
      </form>
      
    </div>
  </div>
</template>

<style scoped>
.error { color: #ff5d5d; text-align: center; margin-bottom: 1rem; }
.login-screen { display: flex; justify-content: center; align-items: center; height: 100vh; width: 100vw; padding: 1rem; box-sizing: border-box; }
.login-box { display: flex; flex-direction: column; padding: 2rem; border-radius: 2rem; width: 100%; max-width: 400px; box-sizing: border-box; margin-top: 2rem; }
.login-header { text-align: center; margin-bottom: 1.5rem; }
.login-title { color: #FFC857; margin: 0; font-size: 2.5rem; }
.login-subtitle { color: #C9C2E8; font-size: 1.5rem; margin-top: 0.5rem; }
.login-form { display: flex; flex-direction: column; gap: 0.8rem; }
.input-field { width: 100%; padding: 0.8rem 1.2rem; border-radius: 1rem; border: 1px solid rgba(111, 92, 255, 0.3); background: rgba(33, 25, 52, 0.6); color: white; font-size: 1rem; box-sizing: border-box; outline: none; transition: border-color 0.3s ease; }
.input-field:focus { border-color: #6F5CFF; }
.input-field::placeholder { color: rgba(201, 194, 232, 0.6); }
.btn { background-color: #6F5CFF; color: white; border: none; padding: 1rem; border-radius: 1rem; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease; text-align: center; }
.btn:hover { background-color: #C9C2E8; color: #211934; transform: translateY(-2px); }
.btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
.register { background-color: transparent; border: 1px solid rgba(111, 92, 255, 0.3); font-size: 1rem; padding: 0.8rem; }
</style>
