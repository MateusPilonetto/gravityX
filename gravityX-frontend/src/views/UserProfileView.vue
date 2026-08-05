<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PostCard from '../components/PostCard.vue';
import { api, getFallbackAvatarUrl, getProfileAvatarUrl } from '../services/api.js';
import { fetchPostsByUsername } from '../services/posts.js';
import { userStore } from '../store.js';

const route = useRoute();
const router = useRouter();

const profileUser = ref(null);
const loading = ref(true);
const errorMessage = ref('');
const posts = ref([]);
const postsLoading = ref(false);
const postsError = ref('');
const isFollowing = ref(false);
const actionLoading = ref(false);
const actionError = ref('');
let activeRequestId = 0;
let activePostsRequestId = 0;

const isOwnProfile = computed(() => {
  const profileUserId = profileUser.value?.id;
  const currentUserId = userStore.currentUser?.id;

  return profileUserId != null
    && currentUserId != null
    && String(profileUserId) === String(currentUserId);
});

const avatarUrl = computed(() => getProfileAvatarUrl(profileUser.value));

const loadUserPosts = async (username = profileUser.value?.username) => {
  const requestId = ++activePostsRequestId;
  postsLoading.value = true;
  postsError.value = '';
  posts.value = [];

  try {
    posts.value = await fetchPostsByUsername(username);
  } catch (errorResponse) {
    if (requestId !== activePostsRequestId || errorResponse.status === 401) {
      return;
    }

    console.error('Failed to load user posts:', errorResponse);
    postsError.value = errorResponse.firstMessage?.()
      || errorResponse.message
      || 'Could not load posts.';
  } finally {
    if (requestId === activePostsRequestId) {
      postsLoading.value = false;
    }
  }
};

const loadUserProfile = async () => {
  const requestId = ++activeRequestId;
  activePostsRequestId += 1;
  loading.value = true;
  errorMessage.value = '';
  posts.value = [];
  postsError.value = '';
  postsLoading.value = false;
  
  try {
    const username = route.params.username;

    if (typeof username !== 'string' || !username) {
      throw new Error('Missing profile username.');
    }
    
    const responsePayload = await api.get(`/users/${encodeURIComponent(username)}`);

    if (requestId !== activeRequestId) return;

    if (!responsePayload.user || typeof responsePayload.user !== 'object' || !responsePayload.user.username) {
      throw new Error('Profile response did not include a user.');
    }

    profileUser.value = responsePayload.user;
    isFollowing.value = Boolean(responsePayload.is_following);
    void loadUserPosts(profileUser.value.username);
    
  } catch (errorResponse) {
    if (requestId !== activeRequestId) return;

    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to load user profile:', errorResponse);
    profileUser.value = null;
    errorMessage.value = errorResponse.status === 404
      ? 'User not found.'
      : 'Could not load profile.';
  } finally {
    if (requestId === activeRequestId) {
      loading.value = false;
    }
  }
};

const updatePost = (updatedPost) => {
  posts.value = posts.value.map((post) => (
    post.id === updatedPost.id ? { ...post, ...updatedPost } : post
  ));
};

const removePost = (deletedPostId) => {
  const deletedPostWasDisplayed = posts.value.some(
    (post) => String(post.id) === String(deletedPostId),
  );
  posts.value = posts.value.filter((post) => String(post.id) !== String(deletedPostId));

  if (
    deletedPostWasDisplayed
    && profileUser.value
    && Number.isFinite(Number(profileUser.value.posts_count))
  ) {
    profileUser.value.posts_count = Math.max(0, Number(profileUser.value.posts_count) - 1);
  }
};

watch(() => route.params.username, () => {
  void loadUserProfile();
});

onMounted(() => {
  void loadUserProfile();
});

const handleAvatarError = (event) => {
  event.currentTarget.src = getFallbackAvatarUrl(profileUser.value, 256);
};

const handleFollowToggle = async () => {
  if (isOwnProfile.value || actionLoading.value) return;
  actionLoading.value = true;
  actionError.value = '';
  
  try {
    const path = `/users/${encodeURIComponent(profileUser.value.username)}/follow`;
    const responsePayload = isFollowing.value
      ? await api.delete(path)
      : await api.post(path);

    isFollowing.value = Boolean(responsePayload.is_following);

    if (typeof responsePayload.followers_count === 'number') {
      profileUser.value.followers_count = responsePayload.followers_count;
    }

    if (typeof responsePayload.viewer_following_count === 'number' && userStore.currentUser) {
      userStore.currentUser.following_count = responsePayload.viewer_following_count;
    }
  } catch (errorResponse) {
    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to update follow status:', errorResponse);
    actionError.value = errorResponse.firstMessage
      ? errorResponse.firstMessage()
      : errorResponse.message;
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
    
    <div v-else-if="profileUser" class="gravityx-layout">
      
      <header class="profile-header">
        <div class="profile-avatar-container">
          <img class="profile-avatar" :src="avatarUrl" alt="User avatar" @error="handleAvatarError">
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
              {{ profileUser.bio || 'No bio yet.' }}
            </div>
          </div>
        </section>
      </header>

      <div class="profile-tabs">
        <span class="tab active-tab"><i class="fa-solid fa-table-cells"></i> POSTS</span>
      </div>

      <div class="posts-grid">
        <div v-if="postsLoading" class="posts-state" aria-live="polite">
          <i class="fa-solid fa-spinner fa-spin fa-xl" aria-hidden="true"></i>
          <p>Loading posts…</p>
        </div>

        <div v-else-if="postsError" class="posts-state posts-error" role="alert">
          <i class="fa-solid fa-triangle-exclamation fa-xl" aria-hidden="true"></i>
          <p>{{ postsError }}</p>
          <button type="button" class="posts-retry-button" @click="loadUserPosts()">Try again</button>
        </div>

        <div v-else-if="posts.length" class="posts-list">
          <PostCard
            v-for="post in posts"
            :key="post.id"
            :post="post"
            allow-delete
            @updated="updatePost"
            @deleted="removePost"
          />
        </div>

        <div v-else class="empty-posts">
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
.posts-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; }
.posts-list { display: grid; gap: 16px; }
.empty-posts, .posts-state { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; color: #C9C2E8; text-align: center; }
.posts-state { gap: 12px; }
.posts-state p { margin: 0; }
.posts-error { color: #ff9e9e; }
.posts-retry-button { border: 1px solid #6F5CFF; border-radius: 8px; padding: 8px 14px; background: transparent; color: #fff; cursor: pointer; font-weight: bold; }
</style>
