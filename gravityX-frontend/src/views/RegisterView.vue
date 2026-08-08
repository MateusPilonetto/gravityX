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
    const token = responsePayload?.data?.token;

    if (typeof token !== 'string' || !token) {
      throw new Error('The server did not return a valid session token.');
    }

    setToken(token);
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
        <h1 class="login-title">GravityX</h1>
        <h1 class="login-subtitle">Sign-up</h1>
      </div>
      
      <p v-if="error" id="register-error" class="error" role="alert">{{ error }}</p>
      
      <form @submit.prevent="handleRegister" class="login-form">
        <div class="input-group">
          <label class="visually-hidden" for="register-name">Full name</label>
          <input id="register-name" v-model="form.name" type="text" autocomplete="name" placeholder="Full Name" required class="input-field" />
        </div>
        
        <div class="input-group">
          <label class="visually-hidden" for="register-username">Username</label>
          <input id="register-username" v-model="form.username" type="text" autocomplete="username" placeholder="Username" required pattern="[^/]+" title="A username cannot contain a slash." class="input-field" />
        </div>
        
        <div class="input-group">
          <label class="visually-hidden" for="register-email">E-mail</label>
          <input id="register-email" v-model="form.email" type="email" autocomplete="email" placeholder="E-mail" required class="input-field" />
        </div>
        
        <div class="input-group">
          <label class="visually-hidden" for="register-password">Password</label>
          <input id="register-password" v-model="form.password" type="password" autocomplete="new-password" placeholder="Password (min. 8 characters)" required class="input-field" minlength="8" />
        </div>

        <div class="input-group">
          <label class="visually-hidden" for="register-password-confirmation">Confirm password</label>
          <input id="register-password-confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" placeholder="Confirm Password" required class="input-field" minlength="8" />
        </div>
        
        <button type="submit" class="btn" :disabled="loading">
          {{ loading ? 'Creating account...' : 'Register' }}
        </button>
        
        <router-link to="/login" class="btn register">
          Already have an account?
        </router-link>
      </form>
      
    </div>
  </div>
</template>

<style scoped>
.error { color: #ff5d5d; text-align: center; margin-bottom: 1rem; }
.login-screen { display: flex; justify-content: center; align-items: center; width: 100%; min-height: 100vh; min-height: 100dvh; padding: max(1rem, env(safe-area-inset-top)) max(1rem, env(safe-area-inset-right)) max(1rem, env(safe-area-inset-bottom)) max(1rem, env(safe-area-inset-left)); }
.login-box { display: flex; flex-direction: column; padding: 2rem; border-radius: 2rem; width: 100%; max-width: 400px; box-sizing: border-box; margin-top: 2rem; }
.login-header { text-align: center; margin-bottom: 1.5rem; }
.login-title { color: #FFC857; margin: 0; font-size: 2.5rem; }
.login-subtitle { color: #C9C2E8; font-size: 1.5rem; margin-top: 0.5rem; }
.login-form { display: flex; flex-direction: column; gap: 0.8rem; }
.input-field { width: 100%; min-width: 0; padding: 0.8rem 1.2rem; border-radius: 1rem; border: 1px solid rgba(111, 92, 255, 0.3); background: rgba(33, 25, 52, 0.6); color: white; font-size: 1rem; box-sizing: border-box; outline: none; transition: border-color 0.3s ease; }
.input-field:focus { border-color: #6F5CFF; }
.input-field::placeholder { color: rgba(201, 194, 232, 0.6); }
.btn { background-color: #6F5CFF; color: white; border: none; padding: 1rem; border-radius: 1rem; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease; text-align: center; }
.btn:hover { background-color: #C9C2E8; color: #211934; transform: translateY(-2px); }
.btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
.register { display: block; background-color: transparent; border: 1px solid rgba(111, 92, 255, 0.3); font-size: 1rem; padding: 0.8rem; }

@media (max-width: 390px) {
  .login-box { padding: 1.5rem 1.25rem; border-radius: 1.5rem; }
  .login-title { font-size: 2.1rem; }
}

@media (max-height: 700px) {
  .login-screen { align-items: flex-start; }
  .login-box { margin-top: 0; }
}
</style>
