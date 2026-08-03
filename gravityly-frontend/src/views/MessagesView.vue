<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, setToken } from '../services/api';

const router = useRouter();
const error = ref('');
const loading = ref(false);


</script>

<template>
  <div class="home-container">
    <div class="feed-header">
      <h2><Search></Search></h2>
    </div>
    
    <div class="welcome-card glass-effect">
      <i class="fa-solid fa-message fa-2xl" style="color: #FFC857; margin-bottom: 20px;"></i>
      <h3><Search></Search></h3>
      <p>Soon will be avaliable</p>
    </div>
  </div>
</template>

<style scoped>
.home-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px 20px 100px 20px;
  color: #fff;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.feed-header h2 {
  color: #C9C2E8;
  font-size: 1.5rem;
  margin: 0;
}

.welcome-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 50px 20px;
  border-radius: 16px;
  background: rgba(33, 25, 52, 0.6);
  border: 1px solid rgba(111, 92, 255, 0.3);
}

.welcome-card h3 {
  color: #fff;
  margin-bottom: 10px;
}

.welcome-card p {
  color: #a8a8a8;
  font-size: 0.95rem;
  max-width: 80%;
}
</style>