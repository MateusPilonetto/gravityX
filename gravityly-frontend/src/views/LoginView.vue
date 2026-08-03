<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, setToken } from '../services/api';

const router = useRouter();
const error = ref('');
const loading = ref(false);

const form = ref({
  email: '',
  password: ''
});

const handleLogin = async () => {
  error.value = '';
  loading.value = true;

  try {
    const { data } = await api.post('/login', form.value, { auth: false });
    setToken(data.token);
    router.push('/');
  } catch (err) {
    error.value = err.firstMessage ? err.firstMessage() : err.message;
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
        <h1 class="login-subtitle">Sign-in</h1>
      </div>
      
      <p class="error" v-if="error">{{ error }}</p>
      
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="input-group">
          <input type="email" v-model="form.email" placeholder="Your e-mail" required class="input-field" />
        </div>
        
        <div class="input-group">
          <input type="password" v-model="form.password" placeholder="Your password" required class="input-field" />
          <a href="#">Forgot Password?</a>
        </div>
        
        <button type="submit" class="btn" :disabled="loading">
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
        
        <router-link to="/register" class="btn register" style="text-decoration: none; display: block;">
          Create account
        </router-link>
      </form>
      
    </div>
  </div>
</template>

<style scoped>
.error {
  color: #ff5d5d;
  text-align: center;
  margin-bottom: 1rem;
}

.login-screen {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  width: 100vw;
  padding: 1rem;
  box-sizing: border-box;
}

.login-box {
  display: flex;
  flex-direction: column;
  padding: 3rem 2rem;
  border-radius: 2rem;
  width: 100%;
  max-width: 400px;
  box-sizing: border-box;
}

.login-header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.login-title {
  color: #FFC857;
  margin: 0;
  font-size: 2.5rem;
}

.login-subtitle {
  color: #C9C2E8;
  font-size: 1.5rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.input-field {
  width: 100%;
  padding: 1rem 1.5rem;
  border-radius: 1rem;
  border: 1px solid rgba(111, 92, 255, 0.3);
  background: rgba(33, 25, 52, 0.6);
  color: white;
  font-size: 1rem;
  box-sizing: border-box;
  outline: none;
  transition: border-color 0.3s ease;
}

.input-field:focus {
  border-color: #6F5CFF;
}

.input-field::placeholder {
  color: rgba(201, 194, 232, 0.6);
}

.btn {
  margin-top: 0.3rem;
  background-color: #6F5CFF;
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 1rem;
  font-weight: bold;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.register {
  text-align: center;
  background-color: transparent;
  border: 1px solid rgba(111, 92, 255, 0.3);
  padding: 0.8rem;
}

.btn:hover {
  background-color: #C9C2E8;
  color: #211934;
  transform: translateY(-2px);
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}
</style>