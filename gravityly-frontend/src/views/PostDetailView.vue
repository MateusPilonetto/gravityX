<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PostCard from '../components/PostCard.vue';
import { getFallbackAvatarUrl, getProfileAvatarUrl } from '../services/api';
import { createPostComment, fetchPost } from '../services/posts';

const route = useRoute();
const router = useRouter();
const post = ref(null);
const comments = ref([]);
const loading = ref(true);
const errorMessage = ref('');
const commentBody = ref('');
const commentError = ref('');
const submittingComment = ref(false);
let activeRequestId = 0;

const postId = computed(() => route.params.postId);
const commentsCount = computed(() => Number(post.value?.comments_count) || comments.value.length);

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

function getCommentAuthorName(comment) {
  return comment?.user?.name || comment?.user?.username || 'Unknown user';
}

function getCommentAuthorLocation(comment) {
  const username = comment?.user?.username;

  return username
    ? { name: 'user-profile', params: { username } }
    : { name: 'home' };
}

function handleCommentAvatarError(event, user) {
  event.currentTarget.src = getFallbackAvatarUrl(user, 72);
}

async function loadPost() {
  const requestId = ++activeRequestId;
  const requestedPostId = postId.value;
  loading.value = true;
  errorMessage.value = '';
  commentError.value = '';
  post.value = null;
  comments.value = [];

  if (typeof requestedPostId !== 'string' || !/^\d+$/.test(requestedPostId)) {
    errorMessage.value = 'Post not found.';
    loading.value = false;
    return;
  }

  try {
    const response = await fetchPost(requestedPostId);

    if (requestId !== activeRequestId) {
      return;
    }

    if (!response.post?.id || !response.post.user) {
      throw new Error('The server returned an invalid post response.');
    }

    post.value = response.post;
    comments.value = response.comments;
  } catch (errorResponse) {
    if (requestId !== activeRequestId || errorResponse.status === 401) {
      return;
    }

    console.error('Failed to load post:', errorResponse);
    errorMessage.value = errorResponse.status === 404
      ? 'Post not found.'
      : errorResponse.firstMessage?.() || errorResponse.message || 'Could not load the post.';
  } finally {
    if (requestId === activeRequestId) {
      loading.value = false;
    }
  }
}

function handlePostUpdated(updatedPost) {
  post.value = { ...post.value, ...updatedPost };
}

function handlePostDeleted() {
  router.replace({ name: 'home' });
}

async function handleCommentSubmit() {
  const body = commentBody.value.trim();

  if (!body || submittingComment.value || !post.value) {
    if (!body) {
      commentError.value = 'Write a comment before submitting.';
    }
    return;
  }

  submittingComment.value = true;
  commentError.value = '';

  try {
    const { comment, commentsCount: updatedCommentsCount } = await createPostComment(post.value.id, body);
    comments.value = [...comments.value, comment];
    post.value = {
      ...post.value,
      comments_count: updatedCommentsCount ?? comments.value.length,
    };
    commentBody.value = '';
  } catch (errorResponse) {
    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to create comment:', errorResponse);
    commentError.value = errorResponse.firstMessage?.() || errorResponse.message || 'Could not add the comment.';
  } finally {
    submittingComment.value = false;
  }
}

watch(postId, () => {
  void loadPost();
}, { immediate: true });
</script>

<template>
  <div class="post-detail-container">
    <header class="detail-header">
      <router-link to="/" class="back-link">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to feed
      </router-link>
      <h1>Post</h1>
    </header>

    <div v-if="loading" class="center-message" aria-live="polite">
      <i class="fa-solid fa-spinner fa-spin fa-2xl" aria-hidden="true"></i>
      <span>Loading post…</span>
    </div>

    <section v-else-if="errorMessage" class="state-card error-card" role="alert">
      <i class="fa-solid fa-triangle-exclamation fa-xl" aria-hidden="true"></i>
      <p>{{ errorMessage }}</p>
      <button type="button" class="retry-button" @click="loadPost">Try again</button>
    </section>

    <template v-else-if="post">
      <PostCard
        :post="post"
        allow-delete
        @updated="handlePostUpdated"
        @deleted="handlePostDeleted"
      />

      <section class="comments-section" aria-labelledby="comments-heading">
        <h2 id="comments-heading">Comments ({{ commentsCount }})</h2>

        <form class="comment-form" @submit.prevent="handleCommentSubmit">
          <label for="comment-body">Add a comment</label>
          <textarea
            id="comment-body"
            v-model="commentBody"
            rows="3"
            maxlength="3000"
            placeholder="Join the conversation…"
            :disabled="submittingComment"
          ></textarea>
          <div class="comment-form-footer">
            <p class="character-count">{{ commentBody.length }}/3000 characters</p>
            <button type="submit" class="comment-submit-button" :disabled="submittingComment || !commentBody.trim()">
              <i v-if="submittingComment" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
              {{ submittingComment ? 'Posting…' : 'Post comment' }}
            </button>
          </div>
          <p v-if="commentError" class="comment-error" role="alert">{{ commentError }}</p>
        </form>

        <div v-if="comments.length" class="comment-list">
          <article v-for="comment in comments" :key="comment.id" class="comment-card">
            <router-link :to="getCommentAuthorLocation(comment)" class="comment-avatar-link">
              <img
                :src="getProfileAvatarUrl(comment.user, 72)"
                class="comment-avatar"
                :alt="`${getCommentAuthorName(comment)}'s profile photo`"
                @error="handleCommentAvatarError($event, comment.user)"
              >
            </router-link>
            <div class="comment-content">
              <div class="comment-meta">
                <router-link :to="getCommentAuthorLocation(comment)" class="comment-author">
                  {{ getCommentAuthorName(comment) }}
                </router-link>
                <span v-if="comment.user?.username" class="comment-username">@{{ comment.user.username }}</span>
                <time v-if="comment.created_at" :datetime="comment.created_at">{{ formatDate(comment.created_at) }}</time>
              </div>
              <p>{{ comment.body }}</p>
            </div>
          </article>
        </div>

        <div v-else class="empty-comments">
          <i class="fa-regular fa-comment fa-xl" aria-hidden="true"></i>
          <p>No comments yet. Start the conversation.</p>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.post-detail-container {
  max-width: 680px;
  margin: 0 auto;
  padding: 1.25rem 1.25rem 7rem;
  color: #fff;
}

.detail-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.detail-header h1 {
  margin: 0;
  color: #c9c2e8;
  font-size: 1.5rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #c9c2e8;
}

.center-message,
.state-card,
.empty-comments {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.8rem;
  border: 1px solid rgba(111, 92, 255, 0.3);
  border-radius: 16px;
  background: rgba(33, 25, 52, 0.65);
  text-align: center;
}

.center-message {
  min-height: 260px;
  color: #c9c2e8;
}

.state-card {
  min-height: 220px;
  padding: 1.5rem;
}

.error-card {
  border-color: rgba(255, 93, 93, 0.45);
  color: #ff9e9e;
}

.retry-button,
.comment-submit-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  border: 1px solid rgba(255, 200, 87, 0.7);
  border-radius: 9px;
  padding: 0.6rem 0.85rem;
  background: #ffc857;
  color: #211934;
  cursor: pointer;
  font-weight: 700;
}

.comments-section {
  margin-top: 1.2rem;
}

.comments-section h2 {
  margin: 0 0 0.75rem;
  color: #c9c2e8;
  font-size: 1.25rem;
}

.comment-form {
  display: grid;
  gap: 0.6rem;
  border: 1px solid rgba(111, 92, 255, 0.32);
  border-radius: 16px;
  padding: 1rem;
  background: rgba(33, 25, 52, 0.68);
}

.comment-form label {
  color: #e5e0f2;
  font-weight: 700;
}

.comment-form textarea {
  box-sizing: border-box;
  width: 100%;
  min-height: 5.5rem;
  resize: vertical;
  border: 1px solid rgba(201, 194, 232, 0.35);
  border-radius: 9px;
  padding: 0.75rem;
  background: rgba(0, 0, 0, 0.18);
  color: #fff;
  font: inherit;
}

.comment-form textarea:focus {
  outline: 2px solid #6f5cff;
  outline-offset: 1px;
}

.comment-form-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.character-count {
  margin: 0;
  color: #aaa2bf;
  font-size: 0.82rem;
}

.comment-submit-button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.comment-error {
  margin: 0;
  color: #ff9e9e;
}

.comment-list {
  display: grid;
  gap: 0.7rem;
  margin-top: 0.9rem;
}

.comment-card {
  display: flex;
  gap: 0.75rem;
  padding: 0.9rem;
  border: 1px solid rgba(111, 92, 255, 0.22);
  border-radius: 12px;
  background: rgba(33, 25, 52, 0.48);
}

.comment-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid rgba(255, 200, 87, 0.7);
  object-fit: cover;
}

.comment-content {
  min-width: 0;
  flex: 1;
}

.comment-meta {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 0.35rem 0.55rem;
}

.comment-author {
  color: #fff;
  font-weight: 700;
}

.comment-username,
.comment-meta time {
  color: #b7afcb;
  font-size: 0.8rem;
}

.comment-content p {
  margin: 0.35rem 0 0;
  color: #e2ddef;
  line-height: 1.5;
  overflow-wrap: anywhere;
  white-space: pre-wrap;
}

.empty-comments {
  min-height: 140px;
  margin-top: 0.9rem;
  color: #c9c2e8;
}

.empty-comments p {
  margin: 0;
}

@media (max-width: 480px) {
  .detail-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>
