<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { userStore } from './store'; 
import { useRouter } from 'vue-router';

const loggedUsername = computed(() => {
  return userStore.currentUser?.username || '';
});

const route = useRoute();
const isAuthPage = computed(() => ['/login', '/register'].includes(route.path));

const user = computed(() => userStore.currentUser);

const navAvatarUrl = computed(() => {
  let url = user.value?.profile_photo_url;
  
  if (url) {
    const match = url.match(/avatars\/([^/]+)$/);
    if (match) {
      return `http://localhost:8000/storage/avatars/${match[1]}`;
    }
  }
  
  const name = user.value?.name ? encodeURIComponent(user.value.name) : 'U';
  return `https://ui-avatars.com/api/?name=${name}&background=6F5CFF&color=fff&size=50&bold=true`;
});

const router = useRouter();

const handleLogout = () => {
  localStorage.removeItem('auth_token');
  
  router.push('/login');
};
</script>

<template>
    <header v-if="!isAuthPage">
      <div class="logo-items">
        <img alt="Gravityly logo" class="logo" src="./assets/gravityly-logo-light.svg" width="125" height="125" />
        <h1 class="logo-title">Gravityly</h1>
      </div>
      <section class="top-area">
        <div class="buttons glass-effect">
        <i class="fa-solid fa-bell fa-2xl notification-icon"></i>
      </div>
      <div class="glass-effect buttons">
        <a href="#" @click.prevent="handleLogout" class="sua-classe-de-css-aqui">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
      </div>
      </section>
      
    </header>


    <main>
      <router-view />
    </main>

     <div class="bottom-nav-wrapper" v-if="!isAuthPage">     
        <nav class="bottom-nav">       
          <router-link to="/" custom v-slot="{ navigate, isActive }">
            <i class="fa-solid fa-house nav-icon" :class="{ active: isActive }" @click="navigate"></i>
          </router-link>

          <router-link to="/search" custom v-slot="{ navigate, isActive }">
            <i class="fa-solid fa-magnifying-glass nav-icon" :class="{ active: isActive }" @click="navigate"></i>
          </router-link>        
          
          <router-link to="/post/create" custom v-slot="{ navigate, isActive }">
            <i class="fa-solid fa-square-plus nav-icon" :class="{ active: isActive }" @click="navigate"></i>
          </router-link>        
          
          <router-link to="/messages" custom v-slot="{ navigate, isActive }">
            <i class="fa-solid fa-message nav-icon" :class="{ active: isActive }" @click="navigate"></i>
          </router-link>        
          
          <router-link :to="`/profile/${loggedUsername}`">
            <img class="nav-profile-pic" loading="lazy" :src="navAvatarUrl" alt="Profile photo">
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

  width: 100vw;
  
  padding: 1rem 2rem;
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
  width: 5rem;
  height: 5rem;
  
  transition: all 0.3s ease; 
  cursor: pointer;
}

.notifications-area:hover {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.notifications-area:hover .notification-icon {
  color: #C9C2E8;
}

.notification-icon {
  color: #6F5CFF;
  transition: color 0.3s ease;
  cursor: pointer;
}

.bottom-nav-wrapper {
  position: fixed;
  bottom: 1rem;
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

.nav-icon.active, 
.nav-icon:hover {
  color: #C9C2E8; 
  transform: translateY(-2px); 
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
</style>