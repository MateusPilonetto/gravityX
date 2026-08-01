<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const isAuthPage = computed(() => ['/login', '/register'].includes(route.path));
</script>

<template>
    <header v-if="!isAuthPage">
      <div class="logo-items">
        <img alt="Gravityly logo" class="logo" src="./assets/gravityly-logo-light.svg" width="125" height="125" />
        <h1 class="logo-title">Gravityly</h1>
      </div>
      <div class="notifications-area glass-effect">
        <i class="fa-solid fa-bell fa-2xl notification-icon"></i>
      </div>
    </header>


    <main>
      <router-view />
    </main>

     <div class="bottom-nav-wrapper" v-if="!isAuthPage">     
        <nav class="bottom-nav">       
          <i class="fa-solid fa-house nav-icon active"></i>        
          <i class="fa-solid fa-magnifying-glass nav-icon"></i>        
          <i class="fa-solid fa-square-plus nav-icon"></i>        
          <i class="fa-solid fa-message nav-icon"></i>        
          
          <img class="nav-profile-pic" loading="lazy" src="https://images.pexels.com/photos/30372403/pexels-photo-30372403.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Profile photo">     
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

.notifications-area {
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
  width: 1.75rem;
  height: 1.75rem;
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
