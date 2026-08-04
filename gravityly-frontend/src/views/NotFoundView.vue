<script setup>
import { computed } from 'vue';
import { isAuthenticated } from '../services/api';

const destination = computed(() => (isAuthenticated() ? '/' : '/login'));
const actionLabel = computed(() => (isAuthenticated() ? 'Go to Feed' : 'Go to Login'));
</script>

<template>
  <section class="not-found-page">
    <div class="not-found-card glass-effect">
      <p class="error-code">404</p>
      <h1>Page not found</h1>
      <p>The page you requested does not exist or is no longer available.</p>
      <router-link :to="destination" class="action-button">
        {{ actionLabel }}
      </router-link>
    </div>
  </section>
</template>

<style scoped>
.not-found-page {
  display: grid;
  min-height: 100vh;
  place-items: center;
  padding: 2rem;
  box-sizing: border-box;
}

.not-found-card {
  width: min(100%, 32rem);
  padding: 3rem 2rem;
  border: 1px solid rgba(111, 92, 255, 0.35);
  border-radius: 1.25rem;
  text-align: center;
}

.error-code {
  margin: 0;
  color: #ffc857;
  font-size: clamp(4rem, 18vw, 7rem);
  font-weight: 700;
  line-height: 1;
}

h1 {
  margin: 1rem 0 0.75rem;
  color: #fff;
}

p {
  color: #c9c2e8;
}

.action-button {
  display: inline-block;
  margin-top: 1.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 0.75rem;
  background: #6f5cff;
  color: #fff;
  font-weight: 700;
  text-decoration: none;
}
</style>
