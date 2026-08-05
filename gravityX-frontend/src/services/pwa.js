const OFFLINE_READY_EVENT = 'gravityx:pwa-offline-ready';
const UPDATE_EVENT = 'gravityx:pwa-update';

let registrationPromise = null;
let reloadAfterServiceWorkerUpdate = false;

const dispatchPwaEvent = (eventName) => {
  window.dispatchEvent(new Event(eventName));
};

const watchInstallingWorker = (worker) => {
  worker.addEventListener('statechange', () => {
    if (worker.state !== 'installed') return;

    if (navigator.serviceWorker.controller) {
      dispatchPwaEvent(UPDATE_EVENT);
      return;
    }

    dispatchPwaEvent(OFFLINE_READY_EVENT);
  });
};

const register = async () => {
  try {
    const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

    if (registration.installing) {
      watchInstallingWorker(registration.installing);
    }

    registration.addEventListener('updatefound', () => {
      if (registration.installing) {
        watchInstallingWorker(registration.installing);
      }
    });

    if (registration.waiting) {
      dispatchPwaEvent(UPDATE_EVENT);
    }

    await registration.update();
    return registration;
  } catch (error) {
    console.error('Unable to register the service worker:', error);
    return null;
  }
};

export const registerServiceWorker = () => {
  if (!import.meta.env.PROD || !('serviceWorker' in navigator) || !window.isSecureContext) {
    return;
  }

  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (!reloadAfterServiceWorkerUpdate) return;

    reloadAfterServiceWorkerUpdate = false;
    window.location.reload();
  });

  const startRegistration = () => {
    registrationPromise ??= register();
  };

  if (document.readyState === 'complete') {
    startRegistration();
    return;
  }

  window.addEventListener('load', startRegistration, { once: true });
};

export const applyServiceWorkerUpdate = async () => {
  if (!('serviceWorker' in navigator)) return;

  const registration = await (registrationPromise ?? navigator.serviceWorker.getRegistration('/'));

  if (!registration) return;

  if (!registration.waiting) {
    await registration.update();
  }

  if (!registration.waiting) return;

  reloadAfterServiceWorkerUpdate = true;
  registration.waiting.postMessage({ type: 'SKIP_WAITING' });
};
