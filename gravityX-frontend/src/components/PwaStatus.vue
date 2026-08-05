<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { applyServiceWorkerUpdate } from '../services/pwa';

const isOffline = ref(false);
const isInstalled = ref(false);
const isOfflineReadyDismissed = ref(false);
const deferredInstallPrompt = ref(null);
const isOfflineReady = ref(false);
const hasUpdate = ref(false);

const canInstall = computed(() => (
  Boolean(deferredInstallPrompt.value) && !isInstalled.value
));
const showOfflineReady = computed(() => (
  isOfflineReady.value && !isOfflineReadyDismissed.value
));

const isStandalone = () => (
  window.matchMedia('(display-mode: standalone)').matches
  || window.navigator.standalone === true
);

const updateConnectionStatus = () => {
  isOffline.value = !window.navigator.onLine;
};

const handleBeforeInstallPrompt = (event) => {
  event.preventDefault();
  deferredInstallPrompt.value = event;
};

const handleAppInstalled = () => {
  isInstalled.value = true;
  deferredInstallPrompt.value = null;
};

const handleOfflineReady = () => {
  isOfflineReady.value = true;
};

const handlePwaUpdate = () => {
  hasUpdate.value = true;
};

const installApp = async () => {
  const installPrompt = deferredInstallPrompt.value;

  if (!installPrompt) return;

  await installPrompt.prompt();
  deferredInstallPrompt.value = null;
};

const refreshApp = async () => {
  await applyServiceWorkerUpdate();
};

onMounted(() => {
  updateConnectionStatus();
  isInstalled.value = isStandalone();

  window.addEventListener('online', updateConnectionStatus);
  window.addEventListener('offline', updateConnectionStatus);
  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
  window.addEventListener('appinstalled', handleAppInstalled);
  window.addEventListener('gravityx:pwa-offline-ready', handleOfflineReady);
  window.addEventListener('gravityx:pwa-update', handlePwaUpdate);
});

onUnmounted(() => {
  window.removeEventListener('online', updateConnectionStatus);
  window.removeEventListener('offline', updateConnectionStatus);
  window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
  window.removeEventListener('appinstalled', handleAppInstalled);
  window.removeEventListener('gravityx:pwa-offline-ready', handleOfflineReady);
  window.removeEventListener('gravityx:pwa-update', handlePwaUpdate);
});
</script>

<template>
  <aside class="pwa-notices" aria-live="polite">
    <section v-if="isOffline" class="pwa-notice pwa-notice--warning" role="status">
      <span>Você está offline. O conteúdo novo precisa de conexão.</span>
    </section>

    <section v-if="showOfflineReady" class="pwa-notice" role="status">
      <span>O GravityX está pronto para abrir offline.</span>
      <button type="button" class="pwa-notice__button" @click="isOfflineReadyDismissed = true">
        Entendi
      </button>
    </section>

    <section v-if="hasUpdate" class="pwa-notice" role="status">
      <span>Uma nova versão do GravityX está disponível.</span>
      <button type="button" class="pwa-notice__button" @click="refreshApp">
        Atualizar
      </button>
    </section>

    <section v-if="canInstall" class="pwa-notice" role="status">
      <span>Instale o GravityX para abrir como aplicativo.</span>
      <button type="button" class="pwa-notice__button" @click="installApp">
        Instalar
      </button>
    </section>
  </aside>
</template>

<style scoped>
.pwa-notices {
  position: fixed;
  z-index: 100;
  right: 1rem;
  bottom: calc(6.25rem + env(safe-area-inset-bottom));
  display: grid;
  gap: 0.75rem;
  width: min(22rem, calc(100vw - 2rem));
}

.pwa-notice {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  color: #ffffff;
  background: rgba(33, 25, 52, 0.97);
  border: 1px solid rgba(111, 92, 255, 0.6);
  border-radius: 0.875rem;
  box-shadow: 0 0.75rem 2rem rgba(0, 0, 0, 0.28);
}

.pwa-notice--warning {
  border-color: rgba(255, 200, 87, 0.7);
}

.pwa-notice__button {
  flex: 0 0 auto;
  padding: 0.45rem 0.75rem;
  color: #211934;
  font-weight: 700;
  background: #ffc857;
  border: 0;
  border-radius: 999px;
  cursor: pointer;
}

.pwa-notice__button:hover,
.pwa-notice__button:focus-visible {
  background: #ffffff;
}

@media (min-width: 1024px) {
  .pwa-notices {
    bottom: 1.5rem;
  }
}
</style>
