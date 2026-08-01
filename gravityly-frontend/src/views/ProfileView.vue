<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const user = ref(null);
const router = useRouter();

onMounted(async () => {
  const token = localStorage.getItem('auth_token');
  
  try {
    const response = await fetch('http://localhost:8000/api/me', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      const data = await response.json();
      user.value = data.data;
    }
  } catch (error) {
    console.error("Error fetching profile", error);
  }
});

const handleLogout = async () => {
  const token = localStorage.getItem('auth_token');
  
  // Avisa a API para destruir o token
  await fetch('http://localhost:8000/api/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  // Limpa o navegador e manda pro login
  localStorage.removeItem('auth_token');
  window.location.href = '/login'; 
};
</script>

<template>
  <div class="profile-page" v-if="user">
    
    <div class="profile-header">
      <h2 class="username">{{ user.username }}</h2>
    </div>

    <div class="profile-stats">
      <img class="avatar" src="https://images.pexels.com/photos/30372403/pexels-photo-30372403.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Avatar">
      <div class="stats-box">
        <div class="stat-item"><strong>0</strong><span>Posts</span></div>
        <div class="stat-item"><strong>0</strong><span>Followers</span></div>
        <div class="stat-item"><strong>0</strong><span>Following</span></div>
      </div>
    </div>

    <div class="bio-section">
      <p class="name">{{ user.name }}</p>
      <p class="bio">{{ user.bio || 'No bio yet.' }}</p>
    </div>

    <div class="actions">
      <router-link to="/profile/edit" class="btn btn-edit">Edit Profile</router-link>
      <button @click="handleLogout" class="btn btn-logout">Logout</button>
    </div>

    <div class="posts-grid glass-effect">
      <i class="fa-solid fa-camera fa-2xl" style="color: #6F5CFF;"></i>
      <p>No posts yet</p>
    </div>

  </div>
</template>

<style scoped>
.profile-page { padding: 1rem 1.5rem; color: white; padding-bottom: 6rem; }
.profile-header { margin-bottom: 1.5rem; }
.username { font-size: 1.5rem; font-weight: bold; margin: 0; }
.profile-stats { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.avatar { width: 85px; height: 85px; border-radius: 50%; border: 3px solid #FFC857; object-fit: cover; }
.stats-box { display: flex; gap: 1.5rem; }
.stat-item { display: flex; flex-direction: column; align-items: center; font-size: 0.9rem; }
.stat-item strong { font-size: 1.2rem; }
.bio-section { margin-bottom: 1.5rem; }
.name { font-weight: bold; font-size: 1.1rem; margin-bottom: 0.2rem; }
.bio { font-size: 0.95rem; line-height: 1.4; color: #C9C2E8; }
.actions { display: flex; gap: 1rem; margin-bottom: 2rem; }
.btn { flex: 1; padding: 0.6rem; border-radius: 0.8rem; border: none; font-weight: bold; cursor: pointer; text-align: center; text-decoration: none; font-size: 0.95rem; transition: all 0.2s; }
.btn-edit { background-color: rgba(111, 92, 255, 0.2); color: white; border: 1px solid rgba(111, 92, 255, 0.4); }
.btn-edit:hover { background-color: #6F5CFF; }
.btn-logout { background-color: rgba(255, 93, 93, 0.1); color: #ff5d5d; border: 1px solid rgba(255, 93, 93, 0.3); }
.btn-logout:hover { background-color: #ff5d5d; color: white; }
.posts-grid { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; border-radius: 1rem; gap: 1rem; color: #C9C2E8; }
</style>