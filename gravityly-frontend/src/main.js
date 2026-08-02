import './assets/main.css'
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { userStore } from './store'

const app = createApp(App)

const initializeApp = async () => {
  const token = localStorage.getItem('auth_token');
  
  if (token) {
    try {
      const response = await fetch('http://localhost:8000/api/me', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      });
      if (response.ok) {
        const data = await response.json();
        userStore.setCurrentUser(data.data || data.user || data);
      } else {
        localStorage.removeItem('auth_token');
      }
    } catch (e) {
      console.error("Falha ao inicializar sessão", e);
    }
  }
  
  app.use(router)
  app.mount('#app')
}

initializeApp();