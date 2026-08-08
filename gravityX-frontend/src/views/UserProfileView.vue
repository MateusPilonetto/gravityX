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
const loadingMorePosts = ref(false);
const postsError = ref('');
const loadMorePostsError = ref('');
const postsPagination = ref(null);
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
const hasMorePosts = computed(() => Boolean(postsPagination.value?.has_more_pages));

const loadUserPosts = async (username = profileUser.value?.username, { append = false } = {}) => {
  const requestId = ++activePostsRequestId;
  const page = append ? (Number(postsPagination.value?.current_page) || 1) + 1 : 1;

  if (append) {
    loadingMorePosts.value = true;
    loadMorePostsError.value = '';
  } else {
    postsLoading.value = true;
    postsError.value = '';
    loadMorePostsError.value = '';
    posts.value = [];
    postsPagination.value = null;
  }

  try {
    const response = await fetchPostsByUsername(username, { page });

    if (requestId !== activePostsRequestId) {
      return;
    }

    if (append) {
      const displayedPostIds = new Set(posts.value.map((post) => String(post.id)));
      posts.value = [
        ...posts.value,
        ...response.posts.filter((post) => !displayedPostIds.has(String(post.id))),
      ];
    } else {
      posts.value = response.posts;
    }

    postsPagination.value = response.pagination;
  } catch (errorResponse) {
    if (requestId !== activePostsRequestId || errorResponse.status === 401) {
      return;
    }

    console.error('Failed to load user posts:', errorResponse);
    const message = errorResponse.firstMessage?.()
      || errorResponse.message
      || 'Could not load posts.';

    if (append) {
      loadMorePostsError.value = message;
    } else {
      postsError.value = message;
    }
  } finally {
    if (requestId === activePostsRequestId) {
      if (append) {
        loadingMorePosts.value = false;
      } else {
        postsLoading.value = false;
      }
    }
  }
};

const loadMoreUserPosts = () => {
  if (!hasMorePosts.value || postsLoading.value || loadingMorePosts.value) {
    return;
  }

  void loadUserPosts(profileUser.value?.username, { append: true });
};

const loadUserProfile = async () => {
  const requestId = ++activeRequestId;
  activePostsRequestId += 1;
  loading.value = true;
  errorMessage.value = '';
  posts.value = [];
  postsError.value = '';
  loadMorePostsError.value = '';
  postsPagination.value = null;
  postsLoading.value = false;
  loadingMorePosts.value = false;
  
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

const updatePostsPaginationAfterDelete = () => {
  if (!postsPagination.value) {
    return;
  }

  const total = Math.max(0, (Number(postsPagination.value.total) || 0) - 1);
  const perPage = Math.max(1, Number(postsPagination.value.per_page) || 20);
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const currentPage = Math.max(1, Number(postsPagination.value.current_page) || 1);

  postsPagination.value = {
    ...postsPagination.value,
    total,
    last_page: lastPage,
    has_more_pages: currentPage < lastPage,
  };
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
    updatePostsPaginationAfterDelete();
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
      <button type="button" @click="router.push('/')" class="btn-back">Go to Feed</button>
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
          <div v-if="hasMorePosts || loadMorePostsError" class="load-more-area">
            <p v-if="loadMorePostsError" class="load-more-error" role="alert">{{ loadMorePostsError }}</p>
            <button
              v-if="hasMorePosts"
              type="button"
              class="posts-retry-button"
              :disabled="loadingMorePosts"
              @click="loadMoreUserPosts"
            >
              {{ loadingMorePosts ? 'Loading…' : 'Load more posts' }}
            </button>
          </div>
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
.profile-container {
  max-width: 935px;
  margin: 0 auto;
  padding: 30px 20px calc(7.5rem + env(safe-area-inset-bottom));
  color: #fff;
}

.center-message {
  display: flex;
  min-height: 60vh;
  min-height: 60dvh;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.error-box {
  width: min(100%, 400px);
  margin: 0 auto;
  border: 1px solid rgba(255, 93, 93, 0.3);
  border-radius: 12px;
  padding: 30px;
  background: rgba(255, 93, 93, 0.1);
}

.btn-back,
.posts-retry-button {
  border: 1px solid #6F5CFF;
  border-radius: 8px;
  padding: 8px 14px;
  background: transparent;
  color: #fff;
  cursor: pointer;
  font-weight: bold;
}

.btn-back {
  margin-top: 15px;
  padding: 10px 20px;
}

.profile-header {
  display: grid;
  grid-template-columns: minmax(9rem, 1fr) minmax(0, 2fr);
  align-items: center;
  gap: clamp(1rem, 4vw, 1.875rem);
  margin-bottom: 44px;
}

.profile-avatar-container {
  display: flex;
  justify-content: center;
}

.profile-avatar {
  width: 150px;
  height: 150px;
  border: 2px solid rgba(111, 92, 255, 0.5);
  border-radius: 50%;
  object-fit: cover;
}

.profile-info {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.info-top {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 20px;
}

.username {
  margin: 0;
  color: #C9C2E8;
  font-size: 1.25rem;
  font-weight: 500;
  overflow-wrap: anywhere;
}

.btn-edit {
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 6px 16px;
  background-color: rgba(255, 255, 255, 0.1);
  color: #fff;
  cursor: pointer;
  font-size: 14px;
  font-weight: bold;
}

.btn-following {
  border-color: #6F5CFF;
  background-color: transparent;
}

.action-error {
  margin: -12px 0 12px;
  color: #ff8b8b;
  font-size: 0.85rem;
  overflow-wrap: anywhere;
}

.info-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem clamp(1rem, 5vw, 2.5rem);
  margin: 0 0 20px;
  padding: 0;
  list-style: none;
}

.stat-count {
  color: #FFC857;
  font-weight: bold;
}

.info-bio {
  font-size: 0.95rem;
  line-height: 1.5;
}

.fullname {
  margin: 0 0 5px;
  font-size: 1.05rem;
  font-weight: bold;
  overflow-wrap: anywhere;
}

.bio-text {
  color: #C9C2E8;
  overflow-wrap: anywhere;
  white-space: pre-wrap;
}

.profile-tabs {
  display: flex;
  justify-content: center;
  gap: 60px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
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
}

.active-tab {
  margin-top: -1px;
  border-top: 1px solid #FFC857;
  color: #fff;
}

.posts-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 16px;
}

.posts-list {
  display: grid;
  gap: 16px;
}

.empty-posts,
.posts-state {
  display: flex;
  min-height: 300px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #C9C2E8;
  text-align: center;
}

.posts-state {
  gap: 12px;
}

.posts-state p {
  margin: 0;
}

.posts-error {
  color: #ff9e9e;
}

.load-more-area {
  display: grid;
  justify-items: center;
  gap: 0.65rem;
  margin-top: 0.25rem;
}

.load-more-error {
  margin: 0;
  color: #ff9e9e;
  text-align: center;
}

@media (max-width: 600px) {
  .profile-container {
    padding: 1.25rem 1rem calc(7.5rem + env(safe-area-inset-bottom));
  }

  .profile-header {
    grid-template-columns: minmax(0, 1fr);
    gap: 1.25rem;
    margin-bottom: 2rem;
  }

  .profile-avatar {
    width: 7rem;
    height: 7rem;
  }

  .info-top {
    justify-content: space-between;
    margin-bottom: 1rem;
  }

  .info-stats {
    justify-content: center;
    margin-bottom: 1rem;
  }

  .info-bio {
    text-align: center;
  }
}

@media (max-width: 360px) {
  .info-top {
    align-items: stretch;
    flex-direction: column;
  }

  .btn-edit {
    text-align: center;
  }
}
</style>
