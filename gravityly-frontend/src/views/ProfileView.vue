<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { api, clearToken, getProfileAvatarUrl, getToken } from '../services/api';
import { userStore } from '../store';

const router = useRouter();
const route = useRoute();

const profileUser = ref(null);
const loading = ref(true);
const errorMessage = ref('');
const isOwnProfile = ref(true);

const avatarUrl = computed(() => getProfileAvatarUrl(profileUser.value));

const fetchProfile = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const username = route.params.username;

    let response;

    if (username) {
      isOwnProfile.value = false;
      response = await api.get(`/users/${encodeURIComponent(username)}`);
      profileUser.value = response.user;
    } else {
      isOwnProfile.value = true;
      response = await api.get('/me');
      profileUser.value = response.data;
    }
  } catch (error) {
    if (error.status === 401) {
      clearToken();
      userStore.clearUser();
      router.replace('/login');
      return;
    }

    console.error(error);
    errorMessage.value = 'Could not load profile.';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  if (!getToken()) {
    router.push('/login');
    return;
  }

  await fetchProfile();
});

watch(
  () => route.params.username,
  () => {
    fetchProfile();
  }
);

const handleLogout = () => {
  clearToken();
  userStore.clearUser();
  router.replace('/login');
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
      <button @click="router.push('/')" class="btn-back">Go to Feed</button>
    </div>
    
    <div v-else-if="profileUser" class="gravityly-layout">
      
      <header class="profile-header">
        <div class="profile-avatar-container">
          <img class="profile-avatar" :src="avatarUrl" alt="User avatar">
        </div>

        <section class="profile-info">
          <div class="info-top">
            <h2 class="username">{{ profileUser.username }}</h2>
            
            <router-link v-if="isOwnProfile" to="/profile/edit" class="btn-edit">Edit profile</router-link>
            <a href="#" @click.prevent="handleLogout" class="settings-icon" title="Log Out">
              <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
          </div>

          <ul class="info-stats">
            <li><span class="stat-count">{{ profileUser.posts_count || 0 }}</span> posts</li>
            <li><span class="stat-count">{{ profileUser.followers_count || 0 }}</span> followers</li>
            <li><span class="stat-count">{{ profileUser.following_count || 0 }}</span> following</li>
          </ul>

          <div class="info-bio">
            <h1 class="fullname">{{ profileUser.name || profileUser.username }}</h1>
            <div class="bio-text">
              {{ profileUser.bio || 'Add a bio in Edit Profile.' }}
            </div>
          </div>
        </section>
      </header>

      <div class="profile-tabs">
        <a href="#" class="tab active-tab"><i class="fa-solid fa-table-cells"></i> POSTS</a>
        <a href="#" class="tab"><i class="fa-regular fa-bookmark"></i> SAVED</a>
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
.profile-container { max-width: 935px; margin: 0 auto; padding: 30px 20px 80px 20px; color: #fff; }
.center-message { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 60vh; text-align: center; }
.error-box { background: rgba(255, 93, 93, 0.1); border: 1px solid rgba(255, 93, 93, 0.3); border-radius: 12px; padding: 30px; max-width: 400px; margin: 0 auto; }
.btn-back { background-color: transparent; color: #fff; border: 1px solid #6F5CFF; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 15px; }
.profile-header { display: flex; margin-bottom: 44px; }
.profile-avatar-container { flex: 1; display: flex; justify-content: center; margin-right: 30px; }
.profile-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(111, 92, 255, 0.5); }
.profile-info { flex: 2; display: flex; flex-direction: column; }
.info-top { display: flex; align-items: center; margin-bottom: 20px; gap: 15px; }
.username { font-size: 1.25rem; font-weight: 500; margin: 0; color: #C9C2E8; }
.btn-edit { background-color: rgba(255, 255, 255, 0.1); color: #fff; border-radius: 8px; padding: 6px 16px; font-size: 14px; font-weight: bold; text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.1); cursor: pointer; }
.settings-icon { color: #ff5d5d; font-size: 1.2rem; text-decoration: none; }
.info-stats { display: flex; list-style: none; padding: 0; margin: 0 0 20px 0; gap: 40px; }
.stat-count { font-weight: bold; color: #FFC857; }
.info-bio { font-size: 0.95rem; line-height: 1.5; }
.fullname { font-weight: bold; font-size: 1.05rem; margin: 0 0 5px 0; }
.bio-text { white-space: pre-wrap; color: #C9C2E8; }
.profile-tabs { display: flex; justify-content: center; border-top: 1px solid rgba(255, 255, 255, 0.1); gap: 60px; }
.tab { display: flex; align-items: center; gap: 6px; padding: 15px 0; color: #a8a8a8; font-size: 12px; font-weight: bold; text-decoration: none; }
.active-tab { color: #fff; border-top: 1px solid #FFC857; margin-top: -1px; }
.posts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; }
.empty-posts { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px; color: #C9C2E8; }
</style>
