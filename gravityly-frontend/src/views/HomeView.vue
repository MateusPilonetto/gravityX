<script setup>
import { computed, onMounted, ref } from 'vue';
import PostCard from '../components/PostCard.vue';
import { getFallbackAvatarUrl, getProfileAvatarUrl } from '../services/api';
import { fetchFeed } from '../services/posts';
import { uploadStory } from '../services/stories';
import { userStore } from '../store';

const MAX_STORY_MEDIA_SIZE_BYTES = 10 * 1024 * 1024;
const ACCEPTED_STORY_MEDIA_TYPES = new Set([
  'image/jpeg',
  'image/png',
  'image/webp',
  'video/mp4',
]);

const posts = ref([]);
const storyGroups = ref([]);
const loading = ref(true);
const errorMessage = ref('');
const loadingPlaceholders = [1, 2, 3];
const storyInput = ref(null);
const uploadingStory = ref(false);
const storyUploadStatus = ref('');
const storyUploadError = ref('');

const displayName = computed(() => {
  const name = userStore.currentUser?.name?.trim();

  return name ? name.split(/\s+/)[0] : 'creator';
});
const avatarUrl = computed(() => getProfileAvatarUrl(userStore.currentUser, 96));
const activeStoryGroups = computed(() => storyGroups.value.filter((storyGroup) => (
  storyGroup?.user?.username
  && Array.isArray(storyGroup.stories)
  && storyGroup.stories.some((story) => story?.id)
)));
const feedSummary = computed(() => {
  const count = posts.value.length;

  if (count === 0) {
    return 'Be the first voice in the feed.';
  }

  return `${count} ${count === 1 ? 'post' : 'posts'} waiting for you.`;
});

async function loadPosts() {
  loading.value = true;
  errorMessage.value = '';

  try {
    const feed = await fetchFeed();
    posts.value = feed.posts;
    storyGroups.value = feed.stories;
  } catch (errorResponse) {
    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to load posts:', errorResponse);
    errorMessage.value = errorResponse.firstMessage?.() || errorResponse.message || 'Could not load the feed.';
  } finally {
    loading.value = false;
  }
}

function updatePost(updatedPost) {
  posts.value = posts.value.map((post) => (post.id === updatedPost.id ? updatedPost : post));
}

function handleAvatarError(event) {
  event.currentTarget.src = getFallbackAvatarUrl(userStore.currentUser, 96);
}

function getStoryAvatarUrl(user) {
  return getProfileAvatarUrl(user, 96);
}

function handleStoryAvatarError(event, user) {
  event.currentTarget.src = getFallbackAvatarUrl(user, 96);
}

function resetStoryInput() {
  if (storyInput.value) {
    storyInput.value.value = '';
  }
}

function validateStoryMedia(file) {
  if (!ACCEPTED_STORY_MEDIA_TYPES.has(file.type)) {
    return 'Choose a JPEG, PNG, WebP image, or MP4 video.';
  }

  if (file.size === 0) {
    return 'The selected file is empty. Choose another file.';
  }

  if (file.size > MAX_STORY_MEDIA_SIZE_BYTES) {
    return 'Stories must be 10 MB or smaller.';
  }

  return '';
}

function openStoryMediaPicker() {
  if (uploadingStory.value) {
    return;
  }

  storyUploadStatus.value = '';
  storyUploadError.value = '';
  storyInput.value?.click();
}

async function handleStoryMediaSelection(event) {
  const [file] = event.target.files || [];

  if (!file) {
    return;
  }

  const validationMessage = validateStoryMedia(file);

  if (validationMessage) {
    storyUploadError.value = validationMessage;
    resetStoryInput();
    return;
  }

  uploadingStory.value = true;
  storyUploadStatus.value = '';
  storyUploadError.value = '';

  try {
    const story = await uploadStory(file);
    const currentUser = userStore.currentUser;
    const ownStoryGroup = storyGroups.value.find((storyGroup) => (
      storyGroup?.user?.id === currentUser?.id
    ));

    if (ownStoryGroup) {
      ownStoryGroup.stories = [...ownStoryGroup.stories, story];
    } else if (currentUser?.id) {
      storyGroups.value = [{ user: currentUser, stories: [story] }, ...storyGroups.value];
    }

    storyUploadStatus.value = 'Your story is live for the next 24 hours.';
  } catch (errorResponse) {
    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to upload story:', errorResponse);
    storyUploadError.value = errorResponse.firstMessage?.()
      || errorResponse.message
      || 'Could not upload the story.';
  } finally {
    uploadingStory.value = false;
    resetStoryInput();
  }
}

onMounted(() => {
  void loadPosts();
});
</script>

<template>
  <div class="feed-page">
    <span class="ambient-orb ambient-orb-primary" aria-hidden="true"></span>
    <span class="ambient-orb ambient-orb-secondary" aria-hidden="true"></span>

    <section class="stories-container" aria-label="Stories">
      <div class="stories-list">
        <button
          type="button"
          class="story-upload-button"
          :disabled="uploadingStory"
          :aria-busy="uploadingStory"
          aria-describedby="story-upload-hint"
          @click="openStoryMediaPicker"
        >
          <span class="story-avatar-frame">
            <img
              :src="avatarUrl"
              class="story-avatar"
              alt="Your profile photo"
              @error="handleAvatarError"
            >
            <span class="plus-icon" aria-hidden="true">
              <i :class="uploadingStory ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-plus'"></i>
            </span>
          </span>
          <span class="story-upload-copy">
            <strong>{{ uploadingStory ? 'Uploading story…' : 'Add a story' }}</strong>
            <small>Share an image or video</small>
          </span>
        </button>

        <router-link
          v-for="storyGroup in activeStoryGroups"
          :key="storyGroup.user.id"
          class="story-preview"
          :to="{ name: 'story-viewer', params: { storyId: storyGroup.stories[0].id } }"
          :aria-label="`View ${storyGroup.user.name || storyGroup.user.username}'s stories`"
        >
          <span class="story-preview-avatar-frame">
            <img
              :src="getStoryAvatarUrl(storyGroup.user)"
              class="story-preview-avatar"
              :alt="`${storyGroup.user.name || storyGroup.user.username}'s profile photo`"
              @error="handleStoryAvatarError($event, storyGroup.user)"
            >
          </span>
          <span class="story-preview-name">{{ storyGroup.user.name || storyGroup.user.username }}</span>
        </router-link>
      </div>
      <input
        ref="storyInput"
        class="story-file-input"
        type="file"
        accept="image/jpeg,image/png,image/webp,video/mp4"
        :disabled="uploadingStory"
        @change="handleStoryMediaSelection"
      >
      <p id="story-upload-hint" class="story-upload-hint">JPEG, PNG, WebP, or MP4 · 10 MB max</p>
      <p v-if="storyUploadStatus" class="story-upload-status" role="status">{{ storyUploadStatus }}</p>
      <p v-if="storyUploadError" class="story-upload-error" role="alert">{{ storyUploadError }}</p>
    </section>

    <section class="feed-hero" aria-labelledby="feed-title">
      <div class="hero-copy">
        <p class="eyebrow"><span class="eyebrow-dot"></span>GRAVITYLY FEED</p>
        <h1 id="feed-title">Your orbit, in one place.</h1>
        <p class="hero-description">
          Catch up with the people and ideas that keep your community moving.
        </p>
        <p class="feed-summary">
          <i class="fa-solid fa-inbox" aria-hidden="true"></i>
          {{ feedSummary }}
        </p>
      </div>

      <router-link to="/posts/create" class="hero-create-button">
        <span class="button-icon"><i class="fa-solid fa-plus" aria-hidden="true"></i></span>
        <span>
          <small>Share with the community</small>
          Create post
        </span>
      </router-link>
    </section>

    <router-link to="/posts/create" class="quick-composer" aria-label="Create a new post">
      <img
        :src="avatarUrl"
        class="composer-avatar"
        alt="Your profile photo"
        @error="handleAvatarError"
      >
      <span class="composer-copy">
        <strong>What is on your mind, {{ displayName }}?</strong>
        <span>Start a post and spark a conversation</span>
      </span>
      <span class="composer-arrow"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
    </router-link>

    <section class="feed-section" aria-labelledby="latest-posts-title">
      <div class="section-heading">
        <div>
          <p class="section-kicker">COMMUNITY PULSE</p>
          <h2 id="latest-posts-title">Latest posts</h2>
        </div>
        <button
          type="button"
          class="refresh-button"
          :disabled="loading"
          aria-label="Refresh feed"
          @click="loadPosts"
        >
          <i class="fa-solid fa-rotate-right" :class="{ spinning: loading }" aria-hidden="true"></i>
          <span>Refresh</span>
        </button>
      </div>

      <div v-if="loading" class="post-list skeleton-list" aria-busy="true" aria-live="polite">
        <span class="sr-only">Loading posts</span>
        <article v-for="placeholder in loadingPlaceholders" :key="placeholder" class="post-skeleton" aria-hidden="true">
          <div class="skeleton-user">
            <span class="skeleton-avatar"></span>
            <div><span class="skeleton-line skeleton-name"></span><span class="skeleton-line skeleton-handle"></span></div>
          </div>
          <span class="skeleton-line skeleton-title"></span>
          <span class="skeleton-line skeleton-body"></span>
          <span class="skeleton-line skeleton-body skeleton-body-short"></span>
          <div class="skeleton-actions"><span></span><span></span></div>
        </article>
      </div>

      <div v-else-if="errorMessage" class="status-surface error-surface" role="alert">
        <div class="status-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div>
        <div>
          <p class="status-label">THE FEED COULD NOT LOAD</p>
          <h3>Something interrupted the signal.</h3>
          <p>{{ errorMessage }}</p>
        </div>
        <button type="button" class="secondary-button" @click="loadPosts">
          Try again <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>
      </div>

      <div v-else-if="posts.length" class="post-list">
        <PostCard
          v-for="post in posts"
          :key="post.id"
          :post="post"
          @updated="updatePost"
        />
      </div>

      <div v-else class="status-surface empty-surface">
        <div class="empty-illustration" aria-hidden="true">
          <i class="fa-regular fa-pen-to-square"></i>
          <span></span>
        </div>
        <div>
          <p class="status-label">A QUIET CORNER OF SPACE</p>
          <h3>There are no posts yet.</h3>
          <p>Break the silence with an update, an idea, or a question for the community.</p>
        </div>
        <router-link to="/posts/create" class="secondary-button">
          Create the first post <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </router-link>
      </div>
    </section>
  </div>
</template>

<style scoped>
.stories-container {
  display: grid;
  gap: 0.45rem;
  margin-bottom: 1.25rem;
}

.stories-list {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  overflow-x: auto;
  padding: 0.15rem 0.2rem 0.4rem;
  scrollbar-width: thin;
  scrollbar-color: rgba(139, 124, 255, 0.65) transparent;
}

.stories-list::-webkit-scrollbar {
  height: 0.35rem;
}

.stories-list::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: rgba(139, 124, 255, 0.65);
}

.story-upload-button {
  display: inline-flex;
  flex: 0 0 auto;
  width: fit-content;
  align-items: center;
  gap: 0.7rem;
  border: 0;
  padding: 0;
  background: transparent;
  color: #fff;
  cursor: pointer;
  font: inherit;
  text-align: left;
}

.story-upload-button:focus-visible {
  outline: 2px solid var(--gold);
  outline-offset: 4px;
  border-radius: 12px;
}

.story-upload-button:disabled {
  cursor: wait;
  opacity: 0.72;
}

.story-avatar-frame {
  position: relative;
  display: block;
  width: 4rem;
  height: 4rem;
}

.story-avatar {
  display: block;
  width: 100%;
  height: 100%;
  border: 2px solid rgba(255, 200, 87, 0.7);
  border-radius: 50%;
  object-fit: cover;
  transition: border-color 180ms ease, transform 180ms ease;
}

.story-upload-button:hover:not(:disabled) .story-avatar {
  border-color: var(--gold);
  transform: translateY(-2px);
}

.story-preview {
  display: grid;
  width: 4.4rem;
  flex: 0 0 4.4rem;
  gap: 0.35rem;
  color: #fff;
  text-align: center;
}

.story-preview:focus-visible {
  outline: 2px solid var(--gold);
  outline-offset: 3px;
  border-radius: 12px;
}

.story-preview:hover {
  color: #fff;
}

.story-preview-avatar-frame {
  display: grid;
  width: 4rem;
  height: 4rem;
  margin: 0 auto;
  place-items: center;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--gold), var(--purple) 58%, #e982d4);
  transition: transform 180ms ease, box-shadow 180ms ease;
}

.story-preview:hover .story-preview-avatar-frame {
  box-shadow: 0 0 0 4px rgba(255, 200, 87, 0.12);
  transform: translateY(-2px);
}

.story-preview-avatar {
  width: calc(100% - 0.27rem);
  height: calc(100% - 0.27rem);
  border: 2px solid #211934;
  border-radius: 50%;
  object-fit: cover;
}

.story-preview-name {
  overflow: hidden;
  color: var(--purple-soft);
  font-size: 0.72rem;
  font-weight: 650;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.plus-icon {
  position: absolute;
  right: -0.2rem;
  bottom: -0.2rem;
  display: grid;
  width: 1.55rem;
  height: 1.55rem;
  place-items: center;
  border: 2px solid #211934;
  border-radius: 50%;
  background: var(--gold);
  color: #211934;
  font-size: 0.7rem;
}

.story-upload-copy {
  display: grid;
  gap: 0.08rem;
}

.story-upload-copy strong {
  font-size: 0.9rem;
}

.story-upload-copy small,
.story-upload-hint,
.story-upload-status,
.story-upload-error {
  margin: 0;
  font-size: 0.75rem;
}

.story-upload-copy small,
.story-upload-hint {
  color: var(--muted);
}

.story-upload-hint {
  margin-left: 4.7rem;
}

.story-upload-status {
  color: #a7e6bd;
}

.story-upload-error {
  color: #ffabab;
}

.story-file-input {
  display: none;
}

.feed-page {
  --surface: rgba(35, 27, 56, 0.82);
  --surface-raised: rgba(47, 37, 75, 0.9);
  --purple: #8b7cff;
  --purple-soft: #c9c2e8;
  --gold: #ffc857;
  --muted: #b8afcb;
  position: relative;
  isolation: isolate;
  max-width: 760px;
  min-height: calc(100vh - 7rem);
  margin: 0 auto;
  padding: 1.5rem 1.25rem 8rem;
  overflow: hidden;
  color: #fff;
}

.ambient-orb {
  position: absolute;
  z-index: -1;
  border-radius: 50%;
  filter: blur(2px);
  pointer-events: none;
}

.ambient-orb-primary {
  top: -7rem;
  right: -6rem;
  width: 17rem;
  height: 17rem;
  background: radial-gradient(circle, rgba(111, 92, 255, 0.34), rgba(111, 92, 255, 0));
}

.ambient-orb-secondary {
  top: 22rem;
  left: -10rem;
  width: 20rem;
  height: 20rem;
  background: radial-gradient(circle, rgba(255, 200, 87, 0.12), rgba(255, 200, 87, 0));
}

.feed-hero,
.quick-composer,
.status-surface,
.post-skeleton {
  border: 1px solid rgba(139, 124, 255, 0.24);
  box-shadow: 0 20px 45px rgba(8, 5, 17, 0.22);
}

.feed-hero {
  position: relative;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1.5rem;
  overflow: hidden;
  padding: clamp(1.5rem, 4vw, 2.4rem);
  border-radius: 24px;
  background:
    linear-gradient(125deg, rgba(58, 45, 91, 0.96), rgba(36, 27, 57, 0.94) 55%, rgba(39, 29, 62, 0.86)),
    var(--surface);
}

.feed-hero::after {
  position: absolute;
  top: 0;
  right: 0;
  width: 46%;
  height: 100%;
  background: linear-gradient(135deg, transparent 0 44%, rgba(255, 200, 87, 0.15) 44.25% 44.8%, transparent 45% 100%);
  content: '';
  pointer-events: none;
}

.hero-copy,
.hero-create-button {
  position: relative;
  z-index: 1;
}

.hero-copy {
  max-width: 31rem;
}

.eyebrow,
.section-kicker,
.status-label {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin: 0;
  color: var(--gold);
  font-family: var(--font-mono);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.14em;
}

.eyebrow-dot {
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 50%;
  background: var(--gold);
  box-shadow: 0 0 0 4px rgba(255, 200, 87, 0.12);
}

.feed-hero h1 {
  max-width: 26rem;
  margin: 0.5rem 0 0;
  color: #fff;
  font-size: clamp(2rem, 6vw, 3.35rem);
  line-height: 0.96;
  letter-spacing: -0.045em;
}

.hero-description {
  max-width: 29rem;
  margin: 1rem 0 0;
  color: var(--purple-soft);
  font-size: 1rem;
  line-height: 1.55;
}

.feed-summary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin: 1.25rem 0 0;
  color: #e9e5f5;
  font-size: 0.88rem;
}

.feed-summary i {
  color: var(--gold);
}

.hero-create-button,
.secondary-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.65rem;
  min-height: 3.1rem;
  border-radius: 13px;
  font-weight: 700;
  transition: transform 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
}

.hero-create-button {
  min-width: 11.5rem;
  padding: 0.55rem 0.75rem;
  background: var(--gold);
  color: #211934;
  box-shadow: 0 12px 25px rgba(255, 200, 87, 0.22);
}

.hero-create-button:hover,
.secondary-button:hover {
  color: #211934;
  transform: translateY(-2px);
}

.button-icon {
  display: grid;
  width: 2rem;
  height: 2rem;
  place-items: center;
  border-radius: 9px;
  background: rgba(33, 25, 52, 0.14);
}

.hero-create-button span:last-child {
  display: grid;
  line-height: 1.1;
}

.hero-create-button small {
  margin-bottom: 0.15rem;
  font-size: 0.62rem;
  font-weight: 600;
  letter-spacing: 0.03em;
  opacity: 0.7;
}

.quick-composer {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-top: 1rem;
  padding: 0.8rem;
  border-radius: 17px;
  background: linear-gradient(100deg, rgba(47, 37, 75, 0.86), rgba(39, 29, 62, 0.7));
  transition: border-color 180ms ease, transform 180ms ease, background-color 180ms ease;
}

.quick-composer:hover {
  border-color: rgba(255, 200, 87, 0.52);
  background: var(--surface-raised);
  color: #fff;
  transform: translateY(-2px);
}

.composer-avatar {
  width: 2.8rem;
  height: 2.8rem;
  border: 2px solid rgba(255, 200, 87, 0.7);
  border-radius: 50%;
  object-fit: cover;
}

.composer-copy {
  display: grid;
  min-width: 0;
  gap: 0.1rem;
}

.composer-copy strong {
  overflow: hidden;
  color: #fff;
  font-size: 0.96rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.composer-copy span {
  color: var(--muted);
  font-size: 0.8rem;
}

.composer-arrow {
  display: grid;
  width: 2.2rem;
  height: 2.2rem;
  margin-left: auto;
  place-items: center;
  border: 1px solid rgba(201, 194, 232, 0.24);
  border-radius: 50%;
  color: var(--gold);
}

.feed-section {
  margin-top: 2rem;
}

.section-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.9rem;
}

.section-heading h2 {
  margin: 0.2rem 0 0;
  color: #fff;
  font-size: 1.55rem;
  line-height: 1;
}

.refresh-button {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  min-height: 2.6rem;
  border: 1px solid rgba(201, 194, 232, 0.22);
  border-radius: 10px;
  padding: 0 0.75rem;
  background: rgba(47, 37, 75, 0.52);
  color: var(--purple-soft);
  cursor: pointer;
  font-size: 0.82rem;
  font-weight: 700;
  transition: color 180ms ease, border-color 180ms ease, background-color 180ms ease;
}

.refresh-button:hover:not(:disabled) {
  border-color: rgba(255, 200, 87, 0.62);
  background: rgba(255, 200, 87, 0.08);
  color: var(--gold);
}

.refresh-button:disabled {
  cursor: wait;
  opacity: 0.65;
}

.spinning {
  animation: spin 0.9s linear infinite;
}

.post-list {
  display: grid;
  gap: 0.9rem;
}

.post-skeleton {
  padding: 1rem;
  border-radius: 16px;
  background: var(--surface);
}

.skeleton-user {
  display: flex;
  align-items: center;
  gap: 0.7rem;
}

.skeleton-avatar,
.skeleton-line,
.skeleton-actions span {
  display: block;
  background: linear-gradient(90deg, rgba(201, 194, 232, 0.08), rgba(201, 194, 232, 0.2), rgba(201, 194, 232, 0.08));
  background-size: 220% 100%;
  animation: shimmer 1.5s ease infinite;
}

.skeleton-avatar {
  width: 2.65rem;
  height: 2.65rem;
  border-radius: 50%;
}

.skeleton-line {
  height: 0.65rem;
  margin-top: 0.6rem;
  border-radius: 999px;
}

.skeleton-name {
  width: 7.5rem;
  margin: 0;
}

.skeleton-handle {
  width: 4.5rem;
  height: 0.5rem;
  margin-top: 0.35rem;
}

.skeleton-title {
  width: 45%;
  margin-top: 1.4rem;
}

.skeleton-body {
  width: 94%;
}

.skeleton-body-short {
  width: 68%;
}

.skeleton-actions {
  display: flex;
  gap: 0.6rem;
  margin-top: 1.1rem;
}

.skeleton-actions span {
  width: 4.4rem;
  height: 1.8rem;
  border-radius: 8px;
}

.status-surface {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 1.1rem;
  padding: clamp(1.1rem, 3vw, 1.5rem);
  border-radius: 18px;
  background: var(--surface);
}

.status-icon,
.empty-illustration {
  display: grid;
  width: 3rem;
  height: 3rem;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 13px;
}

.error-surface .status-icon {
  background: rgba(255, 93, 93, 0.12);
  color: #ff9e9e;
}

.status-label {
  color: var(--muted);
  font-size: 0.62rem;
}

.error-surface .status-label {
  color: #ffabab;
}

.status-surface h3 {
  margin: 0.25rem 0 0;
  color: #fff;
  font-size: 1.2rem;
}

.status-surface p:not(.status-label) {
  margin: 0.35rem 0 0;
  color: var(--muted);
  line-height: 1.45;
}

.secondary-button {
  min-height: 2.75rem;
  padding: 0 0.9rem;
  background: var(--gold);
  color: #211934;
  font-size: 0.85rem;
  white-space: nowrap;
}

.empty-surface {
  grid-template-columns: auto 1fr auto;
}

.empty-illustration {
  position: relative;
  color: var(--gold);
  background: rgba(255, 200, 87, 0.09);
  font-size: 1.15rem;
}

.empty-illustration span {
  position: absolute;
  right: -0.2rem;
  bottom: -0.2rem;
  width: 0.62rem;
  height: 0.62rem;
  border: 2px solid #211934;
  border-radius: 50%;
  background: var(--purple);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

@keyframes shimmer {
  0% { background-position: 100% 0; }
  100% { background-position: -120% 0; }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 600px) {
  .feed-page {
    padding-top: 1rem;
  }

  .stories-list {
    gap: 0.75rem;
    margin-right: -1.25rem;
    padding-right: 1.25rem;
  }

  .story-upload-copy small,
  .story-upload-hint {
    display: none;
  }

  .story-upload-copy strong {
    font-size: 0.8rem;
  }

  .feed-hero,
  .status-surface,
  .empty-surface {
    grid-template-columns: 1fr;
  }

  .feed-hero {
    align-items: stretch;
    flex-direction: column;
  }

  .hero-create-button,
  .secondary-button {
    width: 100%;
  }

  .status-surface {
    display: flex;
    align-items: flex-start;
    flex-direction: column;
  }

  .section-heading {
    align-items: flex-start;
  }

  .refresh-button span {
    display: none;
  }

  .refresh-button {
    width: 2.6rem;
    justify-content: center;
    padding: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .quick-composer,
  .hero-create-button,
  .secondary-button,
  .skeleton-avatar,
  .skeleton-line,
  .skeleton-actions span,
  .spinning {
    animation: none;
    transition: none;
  }
}

@media (max-width: 600px) {
  .hero-create-button {
    min-width: 10rem;
    padding: 0.50rem 0.70rem;
    background: var(--gold);
    color: #211934;
    box-shadow: 0 12px 25px rgba(255, 200, 87, 0.22);
  }

  .feed-hero {
    display: flex;
    justify-content: center;
    align-items: center;
  }
}
</style>
