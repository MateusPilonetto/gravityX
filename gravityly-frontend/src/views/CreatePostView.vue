<script setup>
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { getFallbackAvatarUrl, getProfileAvatarUrl } from '../services/api';
import { createPost } from '../services/posts';
import { userStore } from '../store';

const router = useRouter();
const form = reactive({
  caption: '',
  body: '',
});
const submitting = ref(false);
const errorMessage = ref('');

const displayName = computed(() => userStore.currentUser?.name || userStore.currentUser?.username || 'Gravityly member');
const username = computed(() => userStore.currentUser?.username || 'you');
const avatarUrl = computed(() => getProfileAvatarUrl(userStore.currentUser, 112));
const hasPostContent = computed(() => Boolean(form.caption.trim() || form.body.trim()));

function handleAvatarError(event) {
  event.currentTarget.src = getFallbackAvatarUrl(userStore.currentUser, 112);
}

async function handleSubmit() {
  const caption = form.caption.trim();
  const body = form.body.trim();

  if (!caption && !body) {
    errorMessage.value = 'Write a caption or post content before publishing.';
    return;
  }

  submitting.value = true;
  errorMessage.value = '';

  try {
    const post = await createPost({
      caption: caption || null,
      body: body || null,
    });

    await router.push({
      name: 'post-detail',
      params: { postId: String(post.id) },
    });
  } catch (errorResponse) {
    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to create post:', errorResponse);
    errorMessage.value = errorResponse.firstMessage?.() || errorResponse.message || 'Could not publish the post.';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="create-page">
    <span class="ambient-orb ambient-orb-primary" aria-hidden="true"></span>
    <span class="ambient-orb ambient-orb-secondary" aria-hidden="true"></span>

    <header class="create-header">
      <router-link to="/" class="back-button" aria-label="Back to feed">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      </router-link>

      <div class="header-copy">
        <p class="eyebrow"><span class="eyebrow-dot"></span>NEW TRANSMISSION</p>
        <h1>Start a conversation.</h1>
        <p>Share an update, a thought, or the first line of something bigger.</p>
      </div>

      <span class="draft-pill"><i class="fa-regular fa-file-lines" aria-hidden="true"></i> Draft</span>
    </header>

    <form class="post-composer" :aria-busy="submitting" @submit.prevent="handleSubmit">
      <section class="author-strip" aria-label="Post author">
        <span class="author-avatar-frame">
          <img
            :src="avatarUrl"
            class="create-post-author-avatar"
            width="48"
            height="48"
            alt="Your profile photo"
            @error="handleAvatarError"
          >
        </span>
        <div class="author-copy">
          <span>Posting as</span>
          <strong>{{ displayName }}</strong>
          <small>@{{ username }}</small>
        </div>
        <span class="audience-chip"><i class="fa-solid fa-earth-americas" aria-hidden="true"></i> Community</span>
      </section>

      <div class="composer-divider" aria-hidden="true"></div>

      <section class="field-group">
        <div class="field-heading">
          <label for="post-caption">Give it a title <span>Optional</span></label>
          <span class="character-counter">{{ form.caption.length }}/255</span>
        </div>
        <input
          id="post-caption"
          v-model="form.caption"
          type="text"
          maxlength="255"
          autocomplete="off"
          placeholder="A short headline that draws people in"
          :disabled="submitting"
        >
      </section>

      <section class="field-group primary-field">
        <div class="field-heading">
          <label for="post-body">Tell your story <span>Optional</span></label>
          <span class="character-counter">{{ form.body.length }}/5000</span>
        </div>
        <div class="editor-shell">
          <textarea
            id="post-body"
            v-model="form.body"
            rows="8"
            maxlength="5000"
            placeholder="What would you like to share with the community?"
            aria-describedby="post-body-hint"
            :disabled="submitting"
          ></textarea>
          <div class="editor-footer">
            <p id="post-body-hint"><i class="fa-solid fa-sparkles" aria-hidden="true"></i> A title, content, or both is enough.</p>
            <span>Be kind. Be curious.</span>
          </div>
        </div>
      </section>

      <p v-if="errorMessage" class="form-error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        {{ errorMessage }}
      </p>

      <footer class="composer-footer">
        <p class="publication-note">
          <i class="fa-solid fa-lock-open" aria-hidden="true"></i>
          Your post will be shared with the Gravityly community.
        </p>
        <div class="form-actions">
          <router-link to="/" class="cancel-button">Discard</router-link>
          <button type="submit" class="publish-button" :disabled="submitting || !hasPostContent">
            <i v-if="submitting" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
            <i v-else class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            {{ submitting ? 'Publishing…' : 'Publish post' }}
          </button>
        </div>
      </footer>
    </form>
  </div>
</template>

<style scoped>
.create-page {
  --surface: rgba(35, 27, 56, 0.84);
  --surface-raised: rgba(47, 37, 75, 0.92);
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
  pointer-events: none;
}

.ambient-orb-primary {
  top: -7rem;
  left: -7rem;
  width: 19rem;
  height: 19rem;
  background: radial-gradient(circle, rgba(111, 92, 255, 0.3), rgba(111, 92, 255, 0));
}

.ambient-orb-secondary {
  right: -8rem;
  bottom: 3rem;
  width: 18rem;
  height: 18rem;
  background: radial-gradient(circle, rgba(255, 200, 87, 0.12), rgba(255, 200, 87, 0));
}

.create-header {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: start;
  gap: 1rem;
  margin-bottom: 1.35rem;
}

.back-button {
  display: grid;
  width: 2.9rem;
  height: 2.9rem;
  place-items: center;
  border: 1px solid rgba(201, 194, 232, 0.24);
  border-radius: 12px;
  background: rgba(47, 37, 75, 0.56);
  color: var(--purple-soft);
  transition: transform 180ms ease, border-color 180ms ease, color 180ms ease;
}

.back-button:hover {
  border-color: rgba(255, 200, 87, 0.65);
  color: var(--gold);
  transform: translateX(-2px);
}

.header-copy {
  min-width: 0;
}

.eyebrow {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin: 0;
  color: var(--gold);
  font-family: var(--font-mono);
  font-size: 0.67rem;
  font-weight: 700;
  letter-spacing: 0.14em;
}

.eyebrow-dot {
  width: 0.42rem;
  height: 0.42rem;
  border-radius: 50%;
  background: var(--gold);
  box-shadow: 0 0 0 4px rgba(255, 200, 87, 0.12);
}

.header-copy h1 {
  margin: 0.35rem 0 0;
  color: #fff;
  font-size: clamp(1.85rem, 5vw, 2.65rem);
  line-height: 1;
  letter-spacing: -0.035em;
}

.header-copy p:last-child {
  margin: 0.5rem 0 0;
  color: var(--muted);
  line-height: 1.45;
}

.draft-pill,
.audience-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}

.draft-pill {
  margin-top: 0.15rem;
  padding: 0.52rem 0.7rem;
  border: 1px solid rgba(201, 194, 232, 0.2);
  background: rgba(47, 37, 75, 0.56);
  color: var(--purple-soft);
}

.post-composer {
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(139, 124, 255, 0.3);
  border-radius: 24px;
  padding: clamp(1rem, 3vw, 1.65rem);
  background:
    linear-gradient(145deg, rgba(53, 42, 83, 0.88), rgba(35, 27, 56, 0.94) 58%),
    var(--surface);
  box-shadow: 0 24px 55px rgba(8, 5, 17, 0.3);
}

.post-composer::before {
  position: absolute;
  top: 0;
  left: 1.4rem;
  width: 5.2rem;
  height: 2px;
  border-radius: 99px;
  background: linear-gradient(90deg, var(--gold), rgba(255, 200, 87, 0));
  content: '';
}

.author-strip {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.author-avatar-frame {
  display: block;
  width: 3rem;
  height: 3rem;
  flex: 0 0 3rem;
  overflow: hidden;
  border: 2px solid rgba(255, 200, 87, 0.75);
  border-radius: 50%;
  background: rgba(111, 92, 255, 0.18);
  box-shadow: 0 0 0 4px rgba(255, 200, 87, 0.08);
}

.create-post-author-avatar {
  display: block;
  width: 100% !important;
  height: 100% !important;
  max-width: 100% !important;
  max-height: 100% !important;
  object-fit: cover;
}

.author-copy {
  display: grid;
  min-width: 0;
  gap: 0.05rem;
}

.author-copy span,
.author-copy small {
  color: var(--muted);
  font-size: 0.74rem;
}

.author-copy strong {
  overflow: hidden;
  color: #fff;
  font-size: 0.96rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.audience-chip {
  margin-left: auto;
  padding: 0.48rem 0.65rem;
  background: rgba(111, 92, 255, 0.16);
  color: var(--purple-soft);
}

.audience-chip i {
  color: var(--gold);
}

.composer-divider {
  height: 1px;
  margin: 1.15rem 0;
  background: linear-gradient(90deg, rgba(201, 194, 232, 0.18), rgba(201, 194, 232, 0.03));
}

.field-group {
  display: grid;
  gap: 0.5rem;
}

.primary-field {
  margin-top: 1.2rem;
}

.field-heading {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
}

.field-heading label {
  color: #f4f1ff;
  font-size: 0.92rem;
  font-weight: 700;
}

.field-heading label span {
  margin-left: 0.3rem;
  color: var(--muted);
  font-size: 0.74rem;
  font-weight: 500;
}

.character-counter {
  color: var(--muted);
  font-family: var(--font-mono);
  font-size: 0.68rem;
}

input,
textarea {
  box-sizing: border-box;
  width: 100%;
  border: 1px solid rgba(201, 194, 232, 0.25);
  border-radius: 13px;
  background: rgba(12, 8, 24, 0.26);
  color: #fff;
  font: inherit;
  transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
}

input {
  min-height: 3.2rem;
  padding: 0 0.9rem;
}

input::placeholder,
textarea::placeholder {
  color: rgba(201, 194, 232, 0.48);
}

input:focus,
textarea:focus {
  border-color: rgba(255, 200, 87, 0.8);
  outline: none;
  background: rgba(12, 8, 24, 0.38);
  box-shadow: 0 0 0 4px rgba(255, 200, 87, 0.1);
}

input:disabled,
textarea:disabled {
  cursor: wait;
  opacity: 0.7;
}

.editor-shell {
  overflow: hidden;
  border: 1px solid rgba(201, 194, 232, 0.25);
  border-radius: 15px;
  background: rgba(12, 8, 24, 0.26);
  transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
}

.editor-shell:focus-within {
  border-color: rgba(255, 200, 87, 0.8);
  background: rgba(12, 8, 24, 0.38);
  box-shadow: 0 0 0 4px rgba(255, 200, 87, 0.1);
}

.editor-shell textarea {
  min-height: 12rem;
  padding: 0.95rem;
  border: 0;
  border-radius: 0;
  background: transparent;
  box-shadow: none;
  line-height: 1.55;
  resize: vertical;
}

.editor-shell textarea:focus {
  border: 0;
  background: transparent;
  box-shadow: none;
}

.editor-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.65rem 0.9rem;
  border-top: 1px solid rgba(201, 194, 232, 0.12);
  color: var(--muted);
  font-size: 0.72rem;
}

.editor-footer p {
  margin: 0;
}

.editor-footer i {
  margin-right: 0.25rem;
  color: var(--gold);
}

.form-error {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 1rem 0 0;
  padding: 0.75rem 0.85rem;
  border: 1px solid rgba(255, 93, 93, 0.35);
  border-radius: 11px;
  background: rgba(255, 93, 93, 0.1);
  color: #ffb2b2;
  font-size: 0.88rem;
}

.composer-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1.35rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(201, 194, 232, 0.13);
}

.publication-note {
  max-width: 18rem;
  margin: 0;
  color: var(--muted);
  font-size: 0.75rem;
  line-height: 1.35;
}

.publication-note i {
  margin-right: 0.28rem;
  color: var(--purple);
}

.form-actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.cancel-button,
.publish-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 2.9rem;
  border-radius: 11px;
  padding: 0 0.9rem;
  font-weight: 700;
  transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease;
}

.cancel-button {
  border: 1px solid rgba(201, 194, 232, 0.2);
  color: var(--purple-soft);
}

.cancel-button:hover {
  border-color: rgba(201, 194, 232, 0.48);
  color: #fff;
}

.publish-button {
  border: 1px solid var(--gold);
  background: var(--gold);
  color: #211934;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(255, 200, 87, 0.18);
}

.publish-button:hover:not(:disabled) {
  background: #ffda79;
  transform: translateY(-2px);
}

.publish-button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
  box-shadow: none;
}

@media (max-width: 600px) {
  .create-page {
    padding-top: 1rem;
  }

  .create-header {
    grid-template-columns: auto 1fr;
  }

  .draft-pill {
    grid-column: 2;
    justify-self: start;
    margin: -0.4rem 0 0;
  }

  .composer-footer {
    align-items: stretch;
    flex-direction: column;
  }

  .publication-note {
    max-width: none;
  }

  .form-actions {
    width: 100%;
  }

  .cancel-button,
  .publish-button {
    flex: 1;
  }

  .editor-footer span {
    display: none;
  }
}

@media (max-width: 390px) {
  .audience-chip {
    display: none;
  }

  .header-copy h1 {
    font-size: 1.7rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .back-button,
  .publish-button,
  input,
  textarea,
  .editor-shell {
    transition: none;
  }
}
</style>
