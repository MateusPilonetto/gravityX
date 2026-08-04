import { api } from './api';

function getObjectPayload(responsePayload, key) {
  const value = responsePayload?.[key] ?? responsePayload?.data;

  return value && typeof value === 'object' && !Array.isArray(value) ? value : null;
}

function getArrayPayload(responsePayload, key) {
  const value = responsePayload?.[key] ?? responsePayload?.data;

  return Array.isArray(value) ? value : [];
}

export async function fetchPosts() {
  const responsePayload = await api.get('/posts');

  return getArrayPayload(responsePayload, 'posts');
}

export async function fetchPostsByUsername(username) {
  const normalizedUsername = typeof username === 'string' ? username.trim() : '';

  if (!normalizedUsername) {
    throw new Error('A username is required to load profile posts.');
  }

  const responsePayload = await api.get(`/users/${encodeURIComponent(normalizedUsername)}/posts`);

  return getArrayPayload(responsePayload, 'posts');
}

export async function fetchPost(postId) {
  const responsePayload = await api.get(`/posts/${encodeURIComponent(postId)}`);
  const post = getObjectPayload(responsePayload, 'post');

  return {
    post,
    comments: Array.isArray(post?.comments) ? post.comments : [],
  };
}

function createPostFormData({ caption = null, body = null, image = null } = {}) {
  const formData = new FormData();

  if (typeof caption === 'string' && caption) {
    formData.append('caption', caption);
  }

  if (typeof body === 'string' && body) {
    formData.append('body', body);
  }

  if (image !== null && image !== undefined) {
    if (!(image instanceof File)) {
      throw new TypeError('The post image must be a file.');
    }

    formData.append('image', image);
  }

  return formData;
}

export async function createPost(attributes) {
  const responsePayload = await api.post('/posts', createPostFormData(attributes));
  const post = getObjectPayload(responsePayload, 'post');

  if (!post?.id) {
    throw new Error('The server did not return the created post.');
  }

  return post;
}

export async function deletePost(postId) {
  await api.delete(`/posts/${encodeURIComponent(postId)}`);
}

export async function updatePostLike(post) {
  const postId = post?.id;

  if (!postId) {
    throw new Error('A post identifier is required.');
  }

  const responsePayload = post.is_liked
    ? await api.delete(`/posts/${encodeURIComponent(postId)}/likes`)
    : await api.post(`/posts/${encodeURIComponent(postId)}/likes`);
  const updatedPost = getObjectPayload(responsePayload, 'post');

  if (!updatedPost) {
    throw new Error('The server did not return the updated post.');
  }

  return updatedPost;
}

export async function createPostComment(postId, body) {
  const responsePayload = await api.post(`/posts/${encodeURIComponent(postId)}/comments`, { body });
  const comment = getObjectPayload(responsePayload, 'comment');

  if (!comment) {
    throw new Error('The server did not return the created comment.');
  }

  return {
    comment,
    commentsCount: Number.isInteger(responsePayload?.comments_count)
      ? responsePayload.comments_count
      : null,
  };
}

