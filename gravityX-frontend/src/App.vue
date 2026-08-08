<script setup>
import { computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PwaStatus from './components/PwaStatus.vue';
import { userStore } from './store.js';
import {
  api,
  clearToken,
  getFallbackAvatarUrl,
  getProfileAvatarUrl,
  getToken,
  setUnauthorizedResponseHandler,
} from './services/api.js';

const route = useRoute();
const router = useRouter();

const isGuestPage = computed(() => Boolean(route.meta.guestOnly));
const showApplicationChrome = computed(() => (
  Boolean(route.meta.requiresAuth) && !route.meta.hideApplicationChrome
));

const navAvatarUrl = computed(() => getProfileAvatarUrl(userStore.currentUser, 50));

let activeSessionRequest = 0;

const redirectToLogin = () => {
  clearToken();
  userStore.clearUser();

  if (!isGuestPage.value) {
    router.replace('/login');
  }
};

const handleAvatarError = (event) => {
  event.currentTarget.src = getFallbackAvatarUrl(userStore.currentUser, 50);
};

setUnauthorizedResponseHandler(redirectToLogin);

const loadCurrentUser = async () => {
  const requestId = ++activeSessionRequest;

  if (!getToken()) {
    redirectToLogin();
    return;
  }

  try {
    const responsePayload = await api.get('/me');

    if (requestId === activeSessionRequest) {
      userStore.setCurrentUser(responsePayload.data);
    }
  } catch (errorResponse) {
    if (requestId !== activeSessionRequest) return;

    if (errorResponse.status === 401) {
      redirectToLogin();
      return;
    }

    console.error('Failed to load the current session:', errorResponse);
  }
};

watch(() => route.path, () => {
  if (!route.meta.requiresAuth) {
    activeSessionRequest++;

    if (isGuestPage.value) {
      userStore.clearUser();
    }

    return;
  }

  void loadCurrentUser();
}, { immediate: true });

const handleLogout = () => {
  const invalidateSession = api.post('/logout');

  // Do not leave someone trapped in the authenticated UI when their
  // connection is slow or unavailable. `api.post` captures the token before
  // this clears it, so the server can still revoke the current session.
  redirectToLogin();

  void invalidateSession.catch((errorResponse) => {
    if (errorResponse.status !== 401) {
      console.error('Failed to invalidate the current session:', errorResponse);
    }
  });
};
</script>

<template>
    <header v-if="showApplicationChrome">
      <div class="logo-items">
        <img alt="GravityX logo" class="logo" src="./assets/gravityly-logo-light.svg" width="125" height="125" />
        <h1 class="logo-title">GravityX</h1>
      </div>
      <section class="top-area">

      <div class="glass-effect buttons">
        <button type="button" @click="handleLogout" class="logout-button" aria-label="Log out">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </button>
      </div>
      </section>
      
    </header>


    <main>
      <router-view />
    </main>

    <PwaStatus />

     <div class="bottom-nav-wrapper" v-if="showApplicationChrome">
        <nav class="bottom-nav" aria-label="Primary navigation">
          <router-link to="/" class="nav-link" aria-label="Home">
            <i class="fa-solid fa-house nav-icon"></i>
          </router-link>

          <router-link to="/search" class="nav-link" aria-label="Search users">
            <i class="fa-solid fa-magnifying-glass nav-icon"></i>
          </router-link>        
          
          <router-link to="/posts/create" class="nav-link" aria-label="Create post">
            <i class="fa-solid fa-square-plus nav-icon"></i>
          </router-link>              
          
          <router-link to="/profile" class="nav-link" aria-label="Your profile">
            <img
              class="nav-profile-pic"
              loading="lazy"
              :src="navAvatarUrl"
              alt="Profile photo"
              @error="handleAvatarError"
            >
          </router-link>    
        </nav>   
      </div>
</template>

<style scoped>
header {
  line-height: 1.5;
  display: flex;
  justify-content: space-between;
  align-items: center;

  width: 100%;
  
  padding: 1rem 1rem;
  box-sizing: border-box;
}

.top-area {
  display: flex;
}

.logo {
  display: block;
  border-radius: 2rem;
  width: 4rem;
  height: 4rem;
}

.logo-items {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-title {
  color: #FFC857;
}

.buttons {
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 3rem;
  width: 4rem;
  height: 4rem;
  margin: 5px;
  
  transition: all 0.3s ease; 
  cursor: pointer;
}

.logout-button {
  display: flex;
  width: 100%;
  height: 100%;
  align-items: center;
  justify-content: center;
  color: #ff5d5d;
  background: transparent;
  border: 0;
  cursor: pointer;
  font-size: 1.25rem;
}

.bottom-nav-wrapper {
  position: fixed;
  bottom: max(1rem, env(safe-area-inset-bottom));
  left: 0;
  right: 0;
  z-index: 50;
  display: flex;
  justify-content: center;
  padding: 0 1rem;
  box-sizing: border-box;
}

.bottom-nav {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  padding: 0.625rem 1.5rem;
  border-radius: 9999px;
  
  background-color: rgba(33, 25, 52, 0.95); 
  border: 1px solid rgba(111, 92, 255, 0.3);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.nav-icon {
  font-size: 2.5rem; 
  color: #6F5CFF; 
  cursor: pointer;
  transition: all 0.3s ease;
}

.nav-link.router-link-active .nav-icon,
.nav-icon:hover {
  color: #C9C2E8; 
  transform: translateY(-2px); 
}

.nav-link {
  display: flex;
  width: 2.75rem;
  height: 2.75rem;
  align-items: center;
  justify-content: center;
}

.nav-profile-pic {
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #FFC857;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.nav-profile-pic:hover {
  transform: scale(1.1);
}

@media (max-width: 420px) {
  header {
    padding: 0.75rem;
  }

  .logo {
    width: 3.25rem;
    height: 3.25rem;
  }

  .logo-items {
    gap: 0.65rem;
  }

  .logo-title {
    font-size: 1.45rem;
  }

  .buttons {
    width: 3.25rem;
    height: 3.25rem;
    margin: 0;
  }

  .bottom-nav {
    gap: clamp(0.35rem, 3vw, 0.85rem);
    padding: 0.5rem clamp(0.65rem, 4vw, 1rem);
  }

  .nav-icon {
    font-size: 2rem;
  }
}
</style>
