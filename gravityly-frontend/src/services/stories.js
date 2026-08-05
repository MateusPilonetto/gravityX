import { api } from './api';

export async function uploadStory(media) {
  if (!(media instanceof File)) {
    throw new TypeError('A media file is required to publish a story.');
  }

  const formData = new FormData();
  formData.append('media', media);

  const responsePayload = await api.post('/stories', formData);
  const story = responsePayload?.story;

  if (!story?.id) {
    throw new Error('The server did not return the published story.');
  }

  return story;
}

export async function deleteStory(storyId) {
  if (storyId === null || storyId === undefined || storyId === '') {
    throw new TypeError('A story identifier is required to delete a story.');
  }

  await api.delete(`/stories/${encodeURIComponent(storyId)}`);
}
