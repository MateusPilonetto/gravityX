import { api } from './api';

function getObjectPayload(responsePayload, key) {
  const value = responsePayload?.[key] ?? responsePayload?.data;

  return value && typeof value === 'object' && !Array.isArray(value) ? value : null;
}
function getArrayPayload(responsePayload, key) {
  const value = responsePayload?.[key] ?? responsePayload?.data?.[key] ?? responsePayload?.data;

  return Array.isArray(value) ? value : [];
}

function getPaginationPayload(responsePayload, key = 'pagination') {
  const pagination = responsePayload?.[key] ?? responsePayload?.data?.[key];

  return pagination && typeof pagination === 'object'
    ? pagination
    : {
      current_page: 1,
      last_page: 1,
      per_page: 0,
      total: 0,
      has_more_pages: false,
    };
}

function withPage(path, page) {
  const normalizedPage = Math.max(1, Number.parseInt(page, 10) || 1);
  const separator = path.includes('?') ? '&' : '?';

  return `${path}${separator}page=${normalizedPage}`;
}

export async function fetchFeed({ page = 1 } = {}) {
  const responsePayload = await api.get(withPage('/posts', page));

  return {
    posts: getArrayPayload(responsePayload, 'posts'),
    stories: getArrayPayload(responsePayload, 'stories'),
    pagination: getPaginationPayload(responsePayload),
  };
}

export async function fetchPosts() {
  const { posts } = await fetchFeed();

  return posts;
}

export async function fetchPostsByUsername(username, { page = 1 } = {}) {
  const normalizedUsername = typeof username === 'string' ? username.trim() : '';

  if (!normalizedUsername) {
    throw new Error('A username is required to load profile posts.');
  }

  const responsePayload = await api.get(withPage(
    `/users/${encodeURIComponent(normalizedUsername)}/posts`,
    page,
  ));

  return {
    posts: getArrayPayload(responsePayload, 'posts'),
    pagination: getPaginationPayload(responsePayload),
  };
}

export async function fetchPost(postId) {
  const responsePayload = await api.get(`/posts/${encodeURIComponent(postId)}`);
  const post = getObjectPayload(responsePayload, 'post');
  const commentsPagination = responsePayload?.comments_pagination
    ?? responsePayload?.data?.comments_pagination;

  return {
    post,
    comments: Array.isArray(post?.comments) ? post.comments : null,
    commentsPagination: commentsPagination
      ? getPaginationPayload(responsePayload, 'comments_pagination')
      : null,
  };
}

export async function fetchPostComments(postId, { page = 1 } = {}) {
  const responsePayload = await api.get(withPage(
    `/posts/${encodeURIComponent(postId)}/comments`,
    page,
  ));

  return {
    comments: getArrayPayload(responsePayload, 'comments'),
    pagination: getPaginationPayload(responsePayload),
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
