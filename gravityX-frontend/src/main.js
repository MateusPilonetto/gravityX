import './assets/main.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { registerServiceWorker } from './services/pwa';

const application = createApp(App);

application.use(router);
application.mount('#app');

registerServiceWorker();
