<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { api, getToken, clearToken } from '../services/api';

const user = ref(null);
const loading = ref(true);
const errorMessage = ref(''); 
const router = useRouter();

const avatarUrl = computed(() => {
  let url = user.value?.profile_photo_url;
  
  if (url) {
    const match = url.match(/avatars\/([^/]+)$/);
    if (match) {
      return `http://localhost:8000/storage/avatars/${match[1]}`;
    }
  }
  
  const name = user.value?.name ? encodeURIComponent(user.value.name) : 'User';
  return `https://ui-avatars.com/api/?name=${name}&background=6F5CFF&color=fff&size=150&bold=true`;
});

onMounted(async () => {
  if (!getToken()) {
    router.push('/login');
    return;
  }

  try {
    const { data } = await api.get('/me');
    user.value = data;
  } catch (error) {
    errorMessage.value = error.status
      ? `API Error: ${error.status} - ${error.message}`
      : error.message;
  } finally {
    loading.value = false;
  }

  const urlUsername = route.params.username;

  console.log('O username na URL é:', urlUsername);

});

const handleLogout = () => {
  clearToken();
  router.push('/login');
};
</script>

<template>
  <div class="profile-container">
    
    <div v-if="loading" class="center-message">
      <i class="fa-solid fa-spinner fa-spin fa-2xl" style="color: #6F5CFF;"></i>
    </div>
    
    <div v-else-if="errorMessage" class="center-message error-box">
      <i class="fa-solid fa-triangle-exclamation fa-2xl" style="color: #ff5d5d;"></i>
      <p>{{ errorMessage }}</p>
      <button @click="handleLogout" class="btn-logout">Log Out</button>
    </div>
    
    <div v-else-if="user" class="gravityly-layout">
      

      <header class="profile-header">
        <div class="profile-avatar-container">
          <img class="profile-avatar" :src="avatarUrl" alt="User avatar">
        </div>

        <section class="profile-info">
          <div class="info-top">
            <h2 class="username">{{ user.username || 'username' }}</h2>
            <router-link to="/profile/edit" class="btn-edit">Edit profile</router-link>
            <router-link to="/profile/edit" class="settings-icon"><i class="fa-solid fa-gear"></i></router-link>
          </div>

          <ul class="info-stats">
            <li><span class="stat-count">{{ user.posts_count || 0 }}</span> posts</li>
            <li><span class="stat-count">{{ user.followers_count || 0 }}</span> followers</li>
            <li><span class="stat-count">{{ user.following_count || 0 }}</span> following</li>
          </ul>

          <div class="info-bio">
            <h1 class="fullname">{{ user.name || 'User' }}</h1>
            <div class="bio-text">
              {{ user.bio || 'Add a bio in Edit Profile.' }}
            </div>
          </div>
        </section>
      </header>

      <div class="profile-tabs">
        <a href="#" class="tab active-tab"><i class="fa-solid fa-table-cells"></i> POSTS</a>
      </div>

      <div class="posts-grid">
        <div class="empty-posts">
          <i class="fa-solid fa-camera fa-2xl"></i>
          <h2>No posts yet</h2>
        </div>
      </div>
      
    </div>
  </div>
</template>

<style scoped>
/* Main Container */
.profile-container {
  max-width: 935px;
  margin: 0 auto;
  padding: 30px 20px 80px 20px;
  color: #fff;
}

.center-message {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 60vh;
  text-align: center;
}

.error-box {
  background: rgba(255, 93, 93, 0.1);
  border: 1px solid rgba(255, 93, 93, 0.3);
  border-radius: 12px;
  padding: 30px;
  max-width: 400px;
  margin: 0 auto;
}

.btn-logout {
  background-color: transparent;
  color: #ff5d5d;
  border: 1px solid #ff5d5d;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  margin-top: 15px;
}

/* Header: Avatar + Info */
.profile-header {
  display: flex;
  margin-bottom: 44px;
}

.profile-avatar-container {
  flex: 1;
  display: flex;
  justify-content: center;
  margin-right: 30px;
}

.profile-avatar {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(111, 92, 255, 0.5); 
  box-shadow: 0 4px 15px rgba(111, 92, 255, 0.2);
}

.profile-info {
  flex: 2;
  display: flex;
  flex-direction: column;
}

/* Line 1 */
.info-top {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
  gap: 15px;
}

.username {
  font-size: 1.25rem;
  font-weight: 500;
  margin: 0;
  color: #C9C2E8; 
}

.btn-edit {
  background-color: rgba(255, 255, 255, 0.1);
  color: #fff;
  border-radius: 8px;
  padding: 6px 16px;
  font-size: 14px;
  font-weight: bold;
  text-decoration: none;
  transition: all 0.2s;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.btn-edit:hover { 
  background-color: rgba(255, 255, 255, 0.2); 
}

.settings-icon {
  color: #fff;
  font-size: 1.2rem;
  text-decoration: none;
}

/* Line 2 */
.info-stats {
  display: flex;
  list-style: none;
  padding: 0;
  margin: 0 0 20px 0;
  gap: 40px;
  font-size: 1rem;
}

.stat-count {
  font-weight: bold;
  color: #FFC857; 
}

/* Line 3 */
.info-bio {
  font-size: 0.95rem;
  line-height: 1.5;
}

.fullname {
  font-weight: bold;
  font-size: 1.05rem;
  margin: 0 0 5px 0;
}

.bio-text {
  white-space: pre-wrap; 
  color: #C9C2E8;
}

/* Tabs */
.profile-tabs {
  display: flex;
  justify-content: center;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  gap: 60px;
}

.tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 15px 0;
  color: #a8a8a8;
  font-size: 12px;
  font-weight: bold;
  text-decoration: none;
  letter-spacing: 1px;
}

.active-tab {
  color: #fff;
  border-top: 1px solid #FFC857; 
  margin-top: -1px;
}

/* Grid */
.posts-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;
}

.empty-posts {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 300px;
  color: #C9C2E8;
}

.empty-posts h2 {
  font-size: 1.3rem;
  font-weight: 400;
  margin-top: 15px;
}

/* Responsiveness */
@media (max-width: 735px) {
  .profile-header {
    flex-direction: column;
    padding-left: 0;
  }
  .profile-avatar-container {
    margin-right: 0;
    margin-bottom: 20px;
  }
  .info-stats {
    justify-content: center;
    gap: 20px;
  }
  .info-top {
    justify-content: center;
  }
  .info-bio {
    text-align: center;
  }
  .profile-tabs {
    gap: 20px;
  }
}
</style>