<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  api,
  getApiAssetUrl,
  getFallbackAvatarUrl,
  getProfileAvatarUrl,
} from '../services/api';
import { deleteStory } from '../services/stories';
import { userStore } from '../store';

const route = useRoute();
const router = useRouter();

const stories = ref([]);
const currentIndex = ref(-1);
const loading = ref(true);
const errorMessage = ref('');
const mediaFailed = ref(false);
const deletingStory = ref(false);
const deleteError = ref('');

const currentStory = computed(() => stories.value[currentIndex.value] || null);
const currentAuthor = computed(() => currentStory.value?.user || null);
const currentMediaUrl = computed(() => getApiAssetUrl(currentStory.value?.media_url, ''));
const isVideo = computed(() => currentStory.value?.media_type === 'video');
const canGoPrevious = computed(() => currentIndex.value > 0);
const canGoNext = computed(() => currentIndex.value >= 0 && currentIndex.value < stories.value.length - 1);
const canDeleteCurrentStory = computed(() => (
  currentAuthor.value?.id != null
  && String(currentAuthor.value.id) === String(userStore.currentUser?.id)
));

function getArrayPayload(responsePayload, key) {
  const value = responsePayload?.[key] ?? responsePayload?.data?.[key];

  return Array.isArray(value) ? value : [];
}

function flattenStories(storyGroups) {
  return storyGroups.flatMap((storyGroup) => {
    const author = storyGroup?.user;
    const authorStories = Array.isArray(storyGroup?.stories) ? storyGroup.stories : [];

    if (!author) {
      return [];
    }

    return authorStories
      .filter((story) => story?.id && typeof story.media_url === 'string')
      .map((story) => ({ ...story, user: author }));
  });
}

function findStoryIndex(storyId) {
  return stories.value.findIndex((story) => String(story.id) === String(storyId));
}

function resetMediaState() {
  mediaFailed.value = false;
  deleteError.value = '';
}

function selectStory(index, updateRoute = true) {
  if (index < 0 || index >= stories.value.length) {
    return;
  }

  currentIndex.value = index;
  resetMediaState();

  if (updateRoute) {
    void router.replace({
      name: 'story-viewer',
      params: { storyId: stories.value[index].id },
    });
  }
}

function closeViewer() {
  void router.push({ name: 'home' });
}

function goPrevious() {
  if (!deletingStory.value && canGoPrevious.value) {
    selectStory(currentIndex.value - 1);
  }
}

function goNext() {
  if (deletingStory.value) {
    return;
  }

  if (canGoNext.value) {
    selectStory(currentIndex.value + 1);
    return;
  }

  closeViewer();
}

function handleStageClick(event) {
  if (!currentStory.value || event.defaultPrevented) {
    return;
  }

  const bounds = event.currentTarget.getBoundingClientRect();
  const pointerPosition = event.clientX - bounds.left;

  if (pointerPosition < bounds.width * 0.35) {
    goPrevious();
  } else if (pointerPosition > bounds.width * 0.65) {
    goNext();
  }
}

function handleKeydown(event) {
  if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey) {
    return;
  }

  if (event.key === 'Escape') {
    event.preventDefault();
    closeViewer();
  } else if (event.key === 'ArrowLeft') {
    event.preventDefault();
    goPrevious();
  } else if (event.key === 'ArrowRight') {
    event.preventDefault();
    goNext();
  }
}

function handleMediaError() {
  mediaFailed.value = true;
}

function handleAvatarError(event) {
  event.currentTarget.src = getFallbackAvatarUrl(currentAuthor.value, 72);
}

async function deleteCurrentStory() {
  if (!currentStory.value || !canDeleteCurrentStory.value || deletingStory.value) {
    return;
  }

  const deletedStoryId = currentStory.value.id;

  if (!window.confirm('Delete this story? This action cannot be undone.')) {
    return;
  }

  deletingStory.value = true;
  deleteError.value = '';

  try {
    await deleteStory(deletedStoryId);

    const deletedStoryIndex = stories.value.findIndex((story) => (
      String(story.id) === String(deletedStoryId)
    ));

    if (deletedStoryIndex === -1) {
      return;
    }

    stories.value.splice(deletedStoryIndex, 1);

    if (stories.value.length === 0) {
      closeViewer();
      return;
    }

    selectStory(Math.min(deletedStoryIndex, stories.value.length - 1));
  } catch (errorResponse) {
    if (errorResponse.status !== 401) {
      console.error('Failed to delete story:', errorResponse);
      deleteError.value = errorResponse.firstMessage?.()
        || errorResponse.message
        || 'Could not delete this story.';
    }
  } finally {
    deletingStory.value = false;
  }
}

async function loadStories() {
  loading.value = true;
  errorMessage.value = '';
  mediaFailed.value = false;

  try {
    const responsePayload = await api.get('/posts');
    stories.value = flattenStories(getArrayPayload(responsePayload, 'stories'));

    const requestedStoryIndex = findStoryIndex(route.params.storyId);

    if (requestedStoryIndex === -1) {
      currentIndex.value = -1;
      errorMessage.value = 'This story is no longer available.';
      return;
    }

    selectStory(requestedStoryIndex, false);
  } catch (errorResponse) {
    if (errorResponse.status !== 401) {
      console.error('Failed to load stories:', errorResponse);
      errorMessage.value = errorResponse.firstMessage?.()
        || errorResponse.message
        || 'Could not load this story.';
    }
  } finally {
    loading.value = false;
  }
}

watch(() => route.params.storyId, (storyId) => {
  const storyIndex = findStoryIndex(storyId);

  if (storyIndex >= 0) {
    selectStory(storyIndex, false);
    return;
  }

  void loadStories();
});

onMounted(() => {
  document.body.classList.add('story-viewer-open');
  window.addEventListener('keydown', handleKeydown);
  void loadStories();
});

onBeforeUnmount(() => {
  document.body.classList.remove('story-viewer-open');
  window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <main class="story-viewer" aria-label="Story viewer">
    <section v-if="loading" class="viewer-status" aria-live="polite">
      <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
      <p>Loading story…</p>
    </section>

    <section v-else-if="errorMessage || !currentStory" class="viewer-status viewer-status-error" role="alert">
      <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
      <h1>Story unavailable</h1>
      <p>{{ errorMessage || 'This story is no longer available.' }}</p>
      <button type="button" class="viewer-return-button" @click="closeViewer">Return to feed</button>
    </section>

    <article
      v-else
      class="story-stage"
      aria-roledescription="story"
      :aria-label="`${currentAuthor?.name || currentAuthor?.username || 'User'}'s story`"
      @click="handleStageClick"
    >
      <img
        v-if="!isVideo && !mediaFailed"
        :key="currentStory.id"
        class="story-media"
        :src="currentMediaUrl"
        :alt="`${currentAuthor?.name || currentAuthor?.username || 'User'}'s story`"
        @error="handleMediaError"
      >
      <video
        v-else-if="isVideo && !mediaFailed"
        :key="currentStory.id"
        class="story-media"
        :src="currentMediaUrl"
        autoplay
        muted
        playsinline
        @ended="goNext"
        @error="handleMediaError"
      ></video>
      <div v-else class="media-unavailable" role="status">
        <i class="fa-solid fa-image" aria-hidden="true"></i>
        <p>This media could not be displayed.</p>
      </div>

      <div class="story-scrim" aria-hidden="true"></div>

      <div class="story-progress" aria-label="Story progress">
        <span
          v-for="(story, index) in stories"
          :key="story.id"
          class="story-progress-segment"
          :class="{
            'is-complete': index < currentIndex,
            'is-current': index === currentIndex,
          }"
        ></span>
      </div>

      <div class="story-header" @click.stop>
        <div class="story-author">
          <img
            :src="getProfileAvatarUrl(currentAuthor, 72)"
            class="story-author-avatar"
            :alt="`${currentAuthor?.name || currentAuthor?.username || 'User'}'s profile photo`"
            @error="handleAvatarError"
          >
        </div>
        <div class="story-header-actions">
          <button
            v-if="canDeleteCurrentStory"
            type="button"
            class="delete-story-button"
            :disabled="deletingStory"
            :aria-busy="deletingStory"
            aria-label="Delete this story"
            @click="deleteCurrentStory"
          >
            <i :class="deletingStory ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-trash-can'" aria-hidden="true"></i>
          </button>
          <button type="button" class="close-story-button" aria-label="Close stories" @click="closeViewer">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <p v-if="deleteError" class="story-delete-error" role="alert" @click.stop>{{ deleteError }}</p>

      <div class="story-navigation" aria-label="Story navigation" @click.stop>
        <button
          type="button"
          class="story-direction story-direction-previous"
          :disabled="!canGoPrevious || deletingStory"
          aria-label="Previous story"
          @click="goPrevious"
        >
          <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button
          type="button"
          class="story-direction story-direction-next"
          :disabled="deletingStory"
          aria-label="Next story"
          @click="goNext"
        >
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
      </div>

      <p class="story-counter" @click.stop>{{ currentIndex + 1 }} of {{ stories.length }}</p>
    </article>
  </main>
</template>

<style scoped>
:global(body.story-viewer-open) {
  overflow: hidden;
}

.story-viewer {
  position: fixed;
  z-index: 100;
  inset: 0;
  display: grid;
  min-height: 100dvh;
  place-items: center;
  padding: 1.5rem;
  box-sizing: border-box;
  overflow: hidden;
  background:
    radial-gradient(circle at 20% 20%, rgba(111, 92, 255, 0.3), transparent 34%),
    radial-gradient(circle at 80% 80%, rgba(255, 200, 87, 0.14), transparent 30%),
    #080510;
  color: #fff;
}

.story-stage,
.viewer-status {
  width: min(100%, 27rem);
  aspect-ratio: 9 / 16;
  max-height: calc(100dvh - 3rem);
  overflow: hidden;
  border: 1px solid rgba(201, 194, 232, 0.25);
  border-radius: 1.5rem;
  box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, 0.5);
  background: #151020;
}

.story-stage {
  position: relative;
  isolation: isolate;
  cursor: pointer;
}

.story-media,
.media-unavailable {
  position: absolute;
  z-index: -1;
  inset: 0;
  width: 100%;
  height: 100%;
}

.story-media {
  display: block;
  object-fit: contain;
  background: #0e0a16;
}

.media-unavailable {
  display: grid;
  place-items: center;
  align-content: center;
  gap: 0.7rem;
  color: #c9c2e8;
  text-align: center;
}

.media-unavailable i {
  color: #ffc857;
  font-size: 2rem;
}

.media-unavailable p {
  margin: 0;
}

.story-scrim {
  position: absolute;
  z-index: 0;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(180deg, rgba(4, 3, 9, 0.58), transparent 28%, transparent 64%, rgba(4, 3, 9, 0.36));
}

.story-progress {
  position: absolute;
  z-index: 2;
  top: max(0.75rem, env(safe-area-inset-top));
  right: 0.85rem;
  left: 0.85rem;
  display: flex;
  gap: 0.25rem;
}

.story-progress-segment {
  height: 0.2rem;
  flex: 1;
  overflow: hidden;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.38);
}

.story-progress-segment::after {
  display: block;
  width: 0;
  height: 100%;
  background: #fff;
  content: '';
}

.story-progress-segment.is-complete::after,
.story-progress-segment.is-current::after {
  width: 100%;
}

.story-header {
  position: absolute;
  z-index: 2;
  top: 1.5rem;
  right: 0.85rem;
  left: 0.85rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.story-author {
  display: flex;
  align-items: center;
}

.story-author-avatar {
  width: 2.25rem;
  height: 2.25rem;
  flex: 0 0 auto;
  border: 2px solid #ffc857;
  border-radius: 50%;
  object-fit: cover;
}

.story-counter {
  color: rgba(255, 255, 255, 0.74);
  font-size: 0.72rem;
}

.close-story-button,
.delete-story-button,
.story-direction {
  display: grid;
  width: 2.35rem;
  height: 2.35rem;
  flex: 0 0 auto;
  place-items: center;
  border: 1px solid rgba(255, 255, 255, 0.36);
  border-radius: 50%;
  background: rgba(14, 10, 22, 0.52);
  color: #fff;
  cursor: pointer;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  transition: transform 160ms ease, background-color 160ms ease;
}

.close-story-button:hover,
.delete-story-button:hover:not(:disabled),
.story-direction:hover:not(:disabled) {
  background: rgba(111, 92, 255, 0.68);
  transform: scale(1.06);
}

.close-story-button:focus-visible,
.delete-story-button:focus-visible,
.story-direction:focus-visible,
.viewer-return-button:focus-visible {
  outline: 2px solid #ffc857;
  outline-offset: 3px;
}

.delete-story-button {
  color: #ffb0b0;
}

.delete-story-button:hover:not(:disabled) {
  background: rgba(185, 55, 72, 0.72);
  color: #fff;
}

.delete-story-button:disabled {
  cursor: wait;
  opacity: 0.72;
}

.story-header-actions {
  display: flex;
  gap: 0.45rem;
}

.story-delete-error {
  position: absolute;
  z-index: 2;
  top: 4.25rem;
  right: 0.85rem;
  left: 0.85rem;
  margin: 0;
  border-radius: 0.6rem;
  padding: 0.45rem 0.6rem;
  background: rgba(114, 24, 36, 0.8);
  color: #fff;
  font-size: 0.75rem;
  text-align: center;
}

.story-navigation {
  position: absolute;
  z-index: 2;
  top: 50%;
  right: 0.75rem;
  left: 0.75rem;
  display: flex;
  justify-content: space-between;
  transform: translateY(-50%);
}

.story-direction:disabled {
  visibility: hidden;
}

.story-counter {
  position: absolute;
  z-index: 2;
  right: 0;
  bottom: 0.9rem;
  left: 0;
  margin: 0;
  text-align: center;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
}

.viewer-status {
  display: grid;
  align-content: center;
  justify-items: center;
  gap: 0.8rem;
  padding: 2rem;
  box-sizing: border-box;
  color: #c9c2e8;
  text-align: center;
}

.viewer-status > i {
  color: #ffc857;
  font-size: 2rem;
}

.viewer-status h1,
.viewer-status p {
  margin: 0;
}

.viewer-status h1 {
  color: #fff;
  font-size: 1.6rem;
}

.viewer-return-button {
  min-height: 2.75rem;
  margin-top: 0.35rem;
  border: 0;
  border-radius: 0.75rem;
  padding: 0 1rem;
  background: #ffc857;
  color: #211934;
  cursor: pointer;
  font-weight: 700;
}

@media (max-width: 600px) {
  .story-viewer {
    padding: 0;
  }

  .story-stage,
  .viewer-status {
    width: 100vw;
    height: 100dvh;
    max-height: none;
    aspect-ratio: auto;
    border: 0;
    border-radius: 0;
  }

  .story-progress {
    top: max(0.75rem, env(safe-area-inset-top));
    right: 0.75rem;
    left: 0.75rem;
  }

  .story-header {
    top: max(1.5rem, calc(env(safe-area-inset-top) + 0.75rem));
    right: 0.75rem;
    left: 0.75rem;
  }

  .story-delete-error {
    top: max(4.15rem, calc(env(safe-area-inset-top) + 2.7rem));
    right: 0.75rem;
    left: 0.75rem;
  }

  .story-navigation {
    right: 0.3rem;
    left: 0.3rem;
  }

  .story-direction {
    width: 2.7rem;
    height: 3.5rem;
    border-color: transparent;
    background: transparent;
  }

  .story-counter {
    bottom: max(0.85rem, env(safe-area-inset-bottom));
  }
}

@media (prefers-reduced-motion: reduce) {
  .close-story-button,
  .delete-story-button,
  .story-direction {
    transition: none;
  }
}
</style>
