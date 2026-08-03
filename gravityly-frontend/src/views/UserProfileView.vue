<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api, clearToken, getProfileAvatarUrl, getToken } from '../services/api';
import { userStore } from '../store';

const route = useRoute();
const router = useRouter();

const profileUser = ref(null); 
const loading = ref(true);
const errorMessage = ref(''); 
const isFollowing = ref(false);
const actionLoading = ref(false);
const actionError = ref('');
let activeRequest = 0;

const isOwnProfile = computed(() => {
  return Boolean(
    profileUser.value &&
    userStore.currentUser &&
    profileUser.value.id === userStore.currentUser.id
  );
});

const avatarUrl = computed(() => getProfileAvatarUrl(profileUser.value));

const redirectToLogin = () => {
  clearToken();
  userStore.clearUser();
  router.replace('/login');
};

const fetchUserProfile = async () => {
  const requestId = ++activeRequest;
  loading.value = true;
  errorMessage.value = '';
  
  try {
    const username = route.params.username;

    if (typeof username !== 'string' || !username) {
      throw new Error('Missing profile username.');
    }
    
    const response = await api.get(`/users/${encodeURIComponent(username)}`);

    if (requestId !== activeRequest) return;

    if (!response.user || typeof response.user !== 'object' || !response.user.username) {
      throw new Error('Profile response did not include a user.');
    }

    profileUser.value = response.user;
    isFollowing.value = Boolean(response.is_following);
    
  } catch (error) {
    if (requestId !== activeRequest) return;

    if (error.status === 401) {
      redirectToLogin();
      return;
    }

    console.error("Erro ao carregar o perfil", error);
    profileUser.value = null;
    errorMessage.value = error.status === 404
      ? 'User not found.'
      : 'Could not load profile.';
  } finally {
    if (requestId === activeRequest) {
      loading.value = false;
    }
  }
};

watch(() => route.params.username, () => {
  fetchUserProfile();
});

onMounted(async () => {
  if (!getToken()) {
    router.push('/login');
    return;
  }
  await fetchUserProfile();
});

const handleFollowToggle = async () => {
  if (isOwnProfile.value || actionLoading.value) return;
  actionLoading.value = true;
  actionError.value = '';
  
  try {
    const path = `/users/${encodeURIComponent(profileUser.value.username)}/follow`;
    const response = isFollowing.value
      ? await api.delete(path)
      : await api.post(path);

    isFollowing.value = Boolean(response.is_following);

    if (typeof response.followers_count === 'number') {
      profileUser.value.followers_count = response.followers_count;
    }

    if (typeof response.viewer_following_count === 'number' && userStore.currentUser) {
      userStore.currentUser.following_count = response.viewer_following_count;
    }
  } catch (error) {
    if (error.status === 401) {
      redirectToLogin();
      return;
    }

    console.error('Erro ao atualizar seguimento', error);
    actionError.value = error.firstMessage ? error.firstMessage() : error.message;
  } finally {
    actionLoading.value = false;
  }
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
            
            <router-link v-if="isOwnProfile" to="/profile/edit" class="btn-edit">
              Edit profile
            </router-link>
            <button 
              v-else
              @click="handleFollowToggle" 
              class="btn-edit" 
              :class="{ 'btn-following': isFollowing }"
              :disabled="actionLoading"
            >
              {{ actionLoading ? '...' : (isFollowing ? 'Following' : 'Follow') }}
            </button>
          </div>
          <p v-if="actionError" class="action-error" role="alert">{{ actionError }}</p>

          <ul class="info-stats">
            <li><span class="stat-count">{{ profileUser.posts_count || 0 }}</span> posts</li>
            <li><span class="stat-count">{{ profileUser.followers_count || 0 }}</span> followers</li>
            <li><span class="stat-count">{{ profileUser.following_count || 0 }}</span> following</li>
          </ul>

          <div class="info-bio">
            <h1 class="fullname">{{ profileUser.name || profileUser.username }}</h1>
            <div class="bio-text">
              {{ profileUser.bio }}
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
.btn-edit { background-color: rgba(255, 255, 255, 0.1); color: #fff; border-radius: 8px; padding: 6px 16px; font-size: 14px; font-weight: bold; cursor: pointer; border: 1px solid rgba(255, 255, 255, 0.1); }
.btn-following { background-color: transparent; border: 1px solid #6F5CFF; color: #fff; }
.action-error { color: #ff8b8b; font-size: 0.85rem; margin: -12px 0 12px; }
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
