<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { userStore } from '../store';
import { getApiAssetUrl, getFallbackAvatarUrl, getProfileAvatarUrl } from '../services/api';
import { deletePost, updatePostLike } from '../services/posts';

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
  allowDelete: {
    type: Boolean,
    default: false,
  },
  imageVariant: {
    type: String,
    default: 'feed',
  },
});

const emit = defineEmits(['updated', 'deleted']);
const router = useRouter();
const actionError = ref('');
const likeLoading = ref(false);
const deleteLoading = ref(false);

const author = computed(() => props.post.user || {});
const authorName = computed(() => author.value.name || author.value.username || 'Unknown user');
const authorAvatarUrl = computed(() => getProfileAvatarUrl(author.value, 96));
const hasPostImage = computed(() => Boolean(props.post?.image_url));
const postImageUrl = computed(() => getApiAssetUrl(props.post?.image_url));
const postImageAlt = computed(() => {
  const caption = typeof props.post?.caption === 'string' ? props.post.caption.trim() : '';

  if (caption) {
    return `Image attached to: ${caption.slice(0, 120)}`;
  }

  return `Image attached to ${authorName.value}'s post`;
});
const postLocation = computed(() => ({
  name: 'post-detail',
  params: { postId: String(props.post.id) },
}));
const isAuthor = computed(() => {
  const authorId = author.value.id;
  const currentUserId = userStore.currentUser?.id;

  return authorId != null && currentUserId != null && String(authorId) === String(currentUserId);
});
const authorLocation = computed(() => {
  if (isAuthor.value) {
    return { name: 'profile' };
  }

  return {
    name: 'user-profile',
    params: { username: author.value.username },
  };
});
const likesCount = computed(() => Number(props.post.likes_count) || 0);
const commentsCount = computed(() => Number(props.post.comments_count) || 0);
const postImageFailed = ref(false);

watch(() => props.post?.image_url, () => {
  postImageFailed.value = false;
}, { immediate: true });

function formatDate(dateValue) {
  if (!dateValue) {
    return '';
  }

  const date = new Date(dateValue);

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date);
}

function handleAvatarError(event) {
  event.currentTarget.src = getFallbackAvatarUrl(author.value, 96);
}

function handlePostImageError() {
  postImageFailed.value = true;
}

async function handleLike() {
  if (likeLoading.value) {
    return;
  }

  likeLoading.value = true;
  actionError.value = '';

  try {
    const updatedPost = await updatePostLike(props.post);
    emit('updated', { ...props.post, ...updatedPost });
  } catch (errorResponse) {
    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to update post like:', errorResponse);
    actionError.value = errorResponse.firstMessage?.() || errorResponse.message || 'Could not update the like.';
  } finally {
    likeLoading.value = false;
  }
}

function openComments() {
  router.push(postLocation.value);
}

async function handleDelete() {
  if (!isAuthor.value || deleteLoading.value) {
    return;
  }

  const confirmed = window.confirm('Delete this post permanently?');

  if (!confirmed) {
    return;
  }

  deleteLoading.value = true;
  actionError.value = '';

  try {
    await deletePost(props.post.id);
    emit('deleted', props.post.id);
  } catch (errorResponse) {
    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to delete post:', errorResponse);
    actionError.value = errorResponse.firstMessage?.() || errorResponse.message || 'Could not delete the post.';
  } finally {
    deleteLoading.value = false;
  }
}
</script>

<template>
  <article class="post-card">
    <header class="post-header">
      <router-link :to="authorLocation" class="author-link" :aria-label="`View ${authorName}'s profile`">
        <img
          :src="authorAvatarUrl"
          class="author-avatar"
          :alt="`${authorName}'s profile photo`"
          @error="handleAvatarError"
        >
        <span class="author-details">
          <strong>{{ authorName }}</strong>
          <span v-if="author.username" class="author-username">@{{ author.username }}</span>
        </span>
      </router-link>
      <time v-if="post.created_at" :datetime="post.created_at" class="post-date">
        {{ formatDate(post.created_at) }}
      </time>
    </header>

    <router-link
      v-if="hasPostImage && postImageUrl && !postImageFailed"
      :to="postLocation"
      class="post-image-link"
      :aria-label="`Open post image by ${authorName}`"
    >
      <span class="post-image-frame" :class="{ 'post-image-frame-detail': imageVariant === 'detail' }">
        <img
          :src="postImageUrl"
          class="post-image"
          :alt="postImageAlt"
          @error="handlePostImageError"
        >
      </span>
    </router-link>

    <div v-else-if="hasPostImage" class="post-image-unavailable" role="status">
      <i class="fa-regular fa-image" aria-hidden="true"></i>
      <span>Post image unavailable.</span>
    </div>

    <router-link
      v-if="post.caption || post.body"
      :to="postLocation"
      class="post-content-link"
      :aria-label="`Open post by ${authorName}`"
    >
      <h2 v-if="post.caption" class="post-caption">{{ post.caption }}</h2>
      <p v-if="post.body" class="post-body">{{ post.body }}</p>
    </router-link>

    <footer class="post-footer">
      <div class="post-actions" aria-label="Post actions">
        <button
          type="button"
          class="action-button"
          :class="{ liked: post.is_liked }"
          :aria-pressed="Boolean(post.is_liked)"
          :disabled="likeLoading"
          @click="handleLike"
        >
          <i :class="post.is_liked ? 'fa-solid fa-heart' : 'fa-regular fa-heart'" aria-hidden="true"></i>
          <span>{{ likesCount }} {{ likesCount === 1 ? 'like' : 'likes' }}</span>
        </button>
        <button type="button" class="action-button" @click="openComments">
          <i class="fa-regular fa-comment" aria-hidden="true"></i>
          <span>{{ commentsCount }} {{ commentsCount === 1 ? 'comment' : 'comments' }}</span>
        </button>
      </div>
      <button
        v-if="allowDelete && isAuthor"
        type="button"
        class="delete-button"
        :disabled="deleteLoading"
        @click="handleDelete"
      >
        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
        {{ deleteLoading ? 'Deleting…' : 'Delete' }}
      </button>
    </footer>

    <p v-if="actionError" class="action-error" role="alert">{{ actionError }}</p>
  </article>
</template>

<style scoped>
.post-card {
  padding: 1rem;
  border: 1px solid rgba(111, 92, 255, 0.32);
  border-radius: 16px;
  background: rgba(33, 25, 52, 0.68);
  color: #fff;
}

.post-header,
.post-footer,
.post-actions,
.author-link {
  display: flex;
  align-items: center;
}

.post-header,
.post-footer {
  justify-content: space-between;
  gap: 1rem;
}

.author-link {
  min-width: 0;
  gap: 0.7rem;
  color: inherit;
}

.author-avatar {
  width: 42px;
  height: 42px;
  flex: 0 0 auto;
  border: 1px solid rgba(255, 200, 87, 0.7);
  border-radius: 50%;
  object-fit: cover;
}

.author-details {
  display: grid;
  min-width: 0;
}

.author-details strong,
.author-username {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.author-details strong {
  color: #fff;
}

.author-username,
.post-date {
  color: #c9c2e8;
  font-size: 0.8rem;
}

.post-date {
  flex: 0 0 auto;
  text-align: right;
}

.post-content-link {
  display: block;
  margin: 1rem 0;
  color: inherit;
}

.post-image-link {
  display: block;
  margin: 1rem 0 0;
  color: inherit;
}

.post-image-frame {
  display: block;
  overflow: hidden;
  max-width: 100%;
  border: 1px solid rgba(201, 194, 232, 0.25);
  border-radius: 13px;
  background: rgba(12, 8, 24, 0.38);
}

.post-image {
  display: block !important;
  width: 100% !important;
  max-width: 100% !important;
  height: auto !important;
  max-height: 32rem;
  object-fit: cover;
  background: rgba(12, 8, 24, 0.4);
}

.post-image-frame-detail .post-image {
  max-height: 42rem;
}

.post-image-unavailable {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 8rem;
  margin-top: 1rem;
  border: 1px dashed rgba(201, 194, 232, 0.3);
  border-radius: 13px;
  background: rgba(12, 8, 24, 0.24);
  color: #c9c2e8;
  font-size: 0.88rem;
}

.post-content-link:hover .post-caption,
.post-content-link:hover .post-body {
  color: #e4dfff;
}

.post-caption {
  margin: 0 0 0.45rem;
  font-size: 1.2rem;
  line-height: 1.3;
}

.post-body {
  margin: 0;
  color: #e2ddef;
  line-height: 1.55;
  overflow-wrap: anywhere;
  white-space: pre-wrap;
}

.post-actions {
  gap: 0.5rem;
}

.action-button,
.delete-button {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: 0;
  border-radius: 8px;
  padding: 0.45rem 0.55rem;
  background: transparent;
  color: #c9c2e8;
  cursor: pointer;
  font-size: 0.9rem;
}

.action-button:hover:not(:disabled),
.action-button.liked {
  color: #ff7a9e;
  background: rgba(255, 122, 158, 0.1);
}

.delete-button {
  color: #ff9393;
}

.delete-button:hover:not(:disabled) {
  background: rgba(255, 93, 93, 0.12);
}

.action-button:disabled,
.delete-button:disabled {
  cursor: wait;
  opacity: 0.65;
}

.action-error {
  margin: 0.75rem 0 0;
  color: #ff9e9e;
  font-size: 0.88rem;
}

@media (max-width: 480px) {
  .post-header {
    align-items: flex-start;
  }

  .post-date {
    max-width: 8rem;
  }

  .post-footer {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>

